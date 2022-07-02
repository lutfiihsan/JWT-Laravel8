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

Route::group([

    'middleware' => 'api',
    'prefix' => 'v1'

], function ($router) {

    Route::post('login', [App\Http\Controllers\AuthController::class, 'login']);
    Route::post('logout', [App\Http\Controllers\AuthController::class, 'logout']);
    Route::post('logoutall', [App\Http\Controllers\AuthController::class, 'logoutall']);
    Route::post('refresh', [App\Http\Controllers\AuthController::class, 'refresh']);
    Route::post('me', [App\Http\Controllers\AuthController::class, 'me']);
    Route::post('register', [App\Http\Controllers\AuthController::class, 'register']);
    Route::post('token/issue', [App\Http\Controllers\AuthController::class, 'tokenIssue']);
});

Route::group([

    'middleware' => 'api',
    'prefix' => 'v1'

], function ($router) {

    Route::get('resource/user', [App\Http\Controllers\ResController::class, 'user']);

});

Route::any('{any}', function(){
    return response()->json([
        'status' => 'error',
        'message' => 'Resource not found'], 404);
})->where('any', '.*');
