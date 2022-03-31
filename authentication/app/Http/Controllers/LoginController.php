<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
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

        if ($user != null && (password_verify($request['password'], $user->password))) {
            $token = $user->createToken('inscription');

            return response()->json(
                [
                    'accessToken' => $token->plainTextToken,
                    'accessTokenExpiresAt' => $token->accessToken->expired_at,
                    'message' => 'Logged successfully'
                ],
                201
            );
        }

        return response()->json(
            [
                'message' => 'The user do not match our records'
            ],
            404
        );
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
