<?php

namespace App\Http\Controllers;

use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Services\AuthenticationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $authentication = AuthenticationService::verifyTokenValidity($request->bearerToken());

        if ($authentication['personnalAccessToken'] != null && $authentication['personnalAccessToken']['expired_at']->gt(now()) && in_array('ROLE_ADMIN', json_decode($authentication['currentUser']['roles']))) {
            $request->merge(['password' => bcrypt($request->input('password'))]);
            $user = User::create($request->all());
            $user = User::select(['id', 'email', 'roles', 'created_at', 'updated_at'])->find($user->id);
            $user['roles'] = json_decode($user['roles']);

            return response()->json(
                [
                    'data' => $user,
                    'message' => 'User created successfully'
                ],
                201
            );
        } else if ($authentication['personnalAccessToken'] == null || $authentication['personnalAccessToken']['expired_at']->lt(now())) {
            return response()->json(
                [
                    'message' => 'Error Not Logged In'
                ],
                401
            );
        } else if (!in_array('ROLE_ADMIN', json_decode($authentication['currentUser']['roles']))) {
            return response()->json(
                [
                    'message' => 'User is not an admin'
                ],
                403
            );
        } else {
            return response()->json(
                [
                    'message' => 'Admin Token missing or invalid'
                ],
                422
            );
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id, Request $request)
    {
        $authentication = AuthenticationService::verifyTokenValidity($request->bearerToken());

        if ($id == 'me' && $authentication['personnalAccessToken'] != null && $authentication['personnalAccessToken']['expired_at']->gt(now())) {
            $id = $authentication['personnalAccessToken']['tokenable_id'];
        }

        if ($authentication['personnalAccessToken'] != null && $authentication['personnalAccessToken']['expired_at']->gt(now())) {
            if (in_array('ROLE_ADMIN', json_decode($authentication['currentUser']['roles']))) {
                $user = User::select(['id', 'email', 'roles', 'created_at', 'updated_at'])->find($id);
            } else if ($authentication['personnalAccessToken']['tokenable_id'] == $id) {
            } else {
                return response()->json(
                    ['message' => 'It is necessary to be Admin or the account owner'],
                    403
                );
            }
            if ($user != null) {
                return response()->json(
                    ['data' => $user, 'message' => 'User informations'],
                    200
                );
            } else {
                return response()->json(
                    ['message' => 'User not found with this id'],
                    404
                );
            }
        } else if ($authentication['personnalAccessToken'] == null || $authentication['personnalAccessToken']['expired_at']->lt(now())) {
            return response()->json(
                [
                    'message' => 'Error Not Logged In'
                ],
                401
            );
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $authentication = AuthenticationService::verifyTokenValidity($request->bearerToken());

        if ($id == 'me' && $authentication['personnalAccessToken'] != null && $authentication['personnalAccessToken']['expired_at']->gt(now())) {
            $id = $authentication['personnalAccessToken']['tokenable_id'];
        }

        if ($authentication['personnalAccessToken'] != null && $authentication['personnalAccessToken']['expired_at']->gt(now())) {
            if ($request->has('password')) {
                $request->merge(['password' => bcrypt($request->input('password'))]);
            }
            if (in_array('ROLE_ADMIN', json_decode($authentication['currentUser']['roles']))) {
                if ($request->has('roles') && in_array('ROLE_ADMIN', json_decode($authentication['currentUser']['roles']))) {
                    $request->merge(['roles' => ['ROLE_USER', 'ROLE_ADMIN']]);
                }
                $user = User::find($id)->update($request->all());
                $user = User::select(['id', 'email', 'roles', 'created_at', 'updated_at'])->find($id);
            } else if ($authentication['personnalAccessToken']['tokenable_id'] == $id) {
                if ($request->has('roles')) {
                    $request->merge(['roles' => ['ROLE_USER']]);
                }
                $user = User::find($id)->update($request->all());
                $user = User::select(['id', 'email', 'roles', 'created_at', 'updated_at'])->find($id);
            } else {
                return response()->json(
                    ['message' => 'It is necessary to be Admin or the account owner'],
                    403
                );
            }
            if ($user != null) {
                return response()->json(
                    ['data' => $user, 'message' => 'User updated'],
                    201
                );
            } else {
                return response()->json(
                    ['message' => 'User not found with this id'],
                    404
                );
            }
        } else if ($authentication['personnalAccessToken'] == null || $authentication['personnalAccessToken']['expired_at']->lt(now())) {
            return response()->json(
                [
                    'message' => 'Error Not Logged In'
                ],
                401
            );
        }
    }
}
