<?php

use App\Http\Controllers\LoginController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/account/{id}', [UserController::class, 'show']);
Route::post('/account', [UserController::class, 'store']);
Route::put('/account/{id}', [UserController::class, 'update']);

Route::post('/token', [LoginController::class, 'authenticate']);
Route::get('/validate/{accessToken}', [LoginController::class, 'validateToken']);
Route::post('/refresh-token/{refreshToken}/token', [LoginController::class, 'refreshToken']);

Route::apiResources([
    'user' => UserController::class
]);
