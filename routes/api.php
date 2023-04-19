<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['middleware' => ['jwt.verify']], function() {
	Route::post('add_pizza','PizzaController@insert');
	Route::post('update_pizza','PizzaController@update');
	Route::post('delete_pizza','PizzaController@delete');
	Route::post('update_order','OrderController@update');
	Route::post('delete_order','OrderController@delete');
	Route::post('get_order','OrderController@get');
	Route::post('get_order_list','OrderController@getList');
});
	
Route::post('add_order','OrderController@insert');
Route::post('get_pizza','PizzaController@get');
Route::post('get_pizza_list','PizzaController@getList');

Route::post('login','UserController@authenticate');
Route::post('register','UserController@register');