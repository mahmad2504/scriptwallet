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
Route::get('/zaahmad/updateepics', 'zaahmad\commandcontroller@updatepics')->name('zaahmad.updateepics');   //--fields=update 

// supportmatric app //
Route::get('/zaahmad/supportmatric/update', 'zaahmad\commandcontroller@updatsupportmatric')->name('zaahmad.supportmatric.update'); //--fields=update --rebuild=1 
Route::get('/zaahmad/supportmatric/getgraphdata/{product}', 'zaahmad\commandcontroller@getgraphdata')->name('zaahmad.supportmatric.getgraphdata');
Route::get('/zaahmad/supportmatric', 'zaahmad\commandcontroller@showgraphdata')->name('zaahmad.supportmatric.showgraphdata');

/// Google SOS 
Route::get('/sos/{user}/{projectid}/sync', 'sos\commandcontroller@Sync')->name('sos.sync'); //configure=1, rebuild=1    
Route::get('/sos/{user}/{projectid}/gantt', 'sos\commandcontroller@Gantt')->name('sos.gantt');  
Route::get('/sos/{user}', 'sos\commandcontroller@Index')->name('sos.index');   //--fields=update  

// RMO
Route::get('/rmo/login', 'rmo\commandcontroller@Login')->name('rmo.login');
Route::get('/rmo/logout', 'rmo\commandcontroller@Logout')->name('rmo.logout');
Route::post('/rmo/authenticate', 'rmo\commandcontroller@Authenticate')->name('rmo.authenticate');
Route::get('/rmo/planner', 'rmo\commandcontroller@Planner')->name('rmo.planner');
Route::post('/rmo/save', 'rmo\commandcontroller@SaveRMO')->name('rmo.save'); 
Route::get('/rmo/projects', 'rmo\commandcontroller@Projects')->name('rmo.projects'); 
Route::get('/rmo/resources', 'rmo\commandcontroller@Resources')->name('rmo.resources'); 
