<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::post('mollie/webhook',[App\Http\Controllers\PaymentController::class, 'handleWebhookNotification'])
    ->name('webhooks.mollie');


Route::post('/',[App\Http\Controllers\PaymentController::class, 'handleWebhookNotification'])
    ->name('webhooks.mollie');
