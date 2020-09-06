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

Route::get('/', function () {
    return view('welcome');
});
//////// ZAHEER AHMAD ////////////////////
// updatepics app // 
Route::get('/zaahmad/updatepics', 'zaahmad\commandcontroller@updatepics')->name('zaahmad.updatepics');   

// supportmatric app //
Route::get('/zaahmad/supportmatric/update', 'zaahmad\commandcontroller@updatsupportmatric')->name('zaahmad.supportmatric.update');
Route::get('/zaahmad/supportmatric/getgraphdata/{product}', 'zaahmad\commandcontroller@getgraphdata')->name('zaahmad.supportmatric.getgraphdata');
Route::get('/zaahmad/supportmatric', 'zaahmad\commandcontroller@showgraphdata')->name('zaahmad.supportmatric.showgraphdata');
