<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Tries;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\PersonalAccessToken;
use Illuminate\Support\Facades\Auth;
use App\Services\AuthenticationService;

class LoginController extends Controller
{
    /**
     * Handle an authentication attempt.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function authenticate(Request $request)
    {
        $user = User::where('email', $request['email'])->first();
        $tries = Tries::where('ip', $request->ip())->first();

        if ($user != null && (password_verify($request['password'], $user->password)) && ($tries == null || now() > $tries->next_try)) {
            $token = $user->createToken('inscription');


            if ($tries != null) {
                $tries->delete();
            }

            return response()->json(
                [
                    'accessToken' => $token->plainTextToken,
                    'accessTokenExpiresAt' => $token->accessToken->expired_at,
                    'message' => 'Logged successfully'
                ],
                201
            );
        } else if ($tries != null && $tries->next_try != null && now() <= $tries->next_try) {
            return response()->json(
                [
                    'message' => 'You have tried too much time, you have to wait ' . Carbon::parse($tries->next_try)->diffInMinutes(now()) . ' minutes'
                ],
                404
            );
        } else {
            $message = null;
            if ($tries == null || $tries->try == null || now() > Carbon::parse($tries->first_try)->addMinutes(5)) {
                Tries::updateOrCreate(['ip' => $request->ip()], ['try' => 1, 'first_try' => now(), 'ip' => $request->ip()]);

                $message = 'The user do not match our records';
            } else if ($tries->try < 3 && now() <= Carbon::parse($tries->first_try)->addMinutes(5)) {
                (int) $tries->try++;

                $message = 'The user do not match our records, you only have ' . 3 - $tries->try . ' tries left';
                if ($tries->try == 3) {
                    $tries->update(['try' => $tries->try, 'next_try' => now()->addMinutes(30)]);
                    $message = 'You have tried too much time, you have to wait 30 minutes';
                } else {
                    $tries->update(['try' => $tries->try]);
                }
            } else if ($tries->next_try != null && now() > $tries->next_try) {
                $tries->update(['try' => 1, 'first_try' => now(), 'next_try' => null]);

                $message = 'The user do not match our records';
            } else {
                $tries->update(['try' => (int) $tries->try++, 'next_try' => now()->addMinutes(30), 'ip' => $request->ip()]);

                $message = 'You have tried too much time, you have to wait 30 minutes';
            }

            return response()->json(
                [
                    'message' => $message
                ],
                404
            );
        }
    }

    public function validateToken($token)
    {
        $accessToken = PersonalAccessToken::findToken($token);

        if ($accessToken != null && $accessToken['expired_at']->gt(now())) {
            return response()->json(
                [
                    'accessToken' => $token,
                    'accessTokenExpiresAt' => $accessToken->expired_at,
                    'message' => 'Token validated'
                ],
                200
            );
        }

        return response()->json(
            [
                'message' => 'Token not found or invalid'
            ],
            404
        );
    }

    public function refreshToken($token)
    {
        $authentication = AuthenticationService::verifyTokenValidity($token);

        if ($authentication['personnalAccessToken'] != null && $authentication['personnalAccessToken']['expired_at']->gt(now())) {
            $authentication['currentUser']->tokens()->delete();
            $token = $authentication['currentUser']->createToken('inscription');

            return response()->json(
                [
                    'accessToken' => $token->plainTextToken,
                    'accessTokenExpiresAt' => $token->accessToken->expired_at,
                    'message' => 'Token validated'
                ],
                200
            );
        }

        return response()->json(
            [
                'message' => 'Token not found or invalid'
            ],
            404
        );
    }
}
