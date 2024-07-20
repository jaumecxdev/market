<?php

use Illuminate\Http\Request;

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

/*Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});*/
Route::middleware(['APIToken', 'throttle:60,1'])->group(function () {

    Route::post('/telegram/{access_token}', 'TelegramController@api')->name('api.telegram');

    Route::post('/twitter', 'TwitterController@api')->name('api.twitter');

});


Route::middleware(['TokenAuth', 'throttle:60,1'])->group(function () {

    Route::get('/token/{token}', 'TokenController@token')->name('api.token');
});
