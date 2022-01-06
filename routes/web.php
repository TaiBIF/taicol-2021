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

Route::get('/', 'PageController@index');

Route::get('/result', 'PageController@index');

Route::get('/reference', 'PageController@index');

Route::get('/references', 'PageController@index');

Route::get('/references/{id}', 'PageController@index');

Route::get('/references/{id}/edit', 'PageController@index');

Route::get('/references/{id}/usages', 'PageController@index');

Route::get('/taxon-names/{id}', 'PageController@index');

Route::get('/taxon-names/{id}/edit', 'PageController@index');

Route::get('/persons/{id}', 'PageController@index');

Route::get('/persons/{id}/edit', 'PageController@index');

Route::get('/taxon-names', 'PageController@index');

Route::get('/taxon-names/{id}/compare', 'PageController@index');

Route::get('/taxon-name', 'PageController@index');

Route::get('/login', 'PageController@index')->name('login');

Route::get('/register', 'PageController@index');

Route::get('/namespaces', 'PageController@index');

Route::get('/namespaces/{id}/usages', 'PageController@index');

Route::get('/namespaces/{id}/usages/{usage_id}', 'PageController@index');

Route::get('/favorite-folders', 'PageController@index');

Route::get('/favorite-folders/{id}', 'PageController@index');

Route::prefix('admin')->group(function() {
    Route::get('/', 'PageController@index');
    Route::get('/users', 'PageController@index');
    Route::get('/users/{id}', 'PageController@index');
});
