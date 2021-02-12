<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::group(['middelware' => ['api'], 'prefix' => 'v1'], function() {
    Route::post('sign-in', [App\Http\Controllers\AuthController::class, 'signIn']);
    Route::post('validate-sign-in', [App\Http\Controllers\AuthController::class, 'validateSignIn']);
});
