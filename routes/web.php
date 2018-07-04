<?php

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
Route::any('/console', 'Console\IndexController@index');
Route::any('/files/preview', 'FilesController@preview');
Route::any('/webdav{argv}', 'WebDavController@index')->where('argv', '[\s\S]*');
Route::resource('/api/files', 'Api\FilesController')->except(['create', 'edit']);
