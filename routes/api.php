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

Route::post('/login', 'UserController@login');
Route::post('/users', 'UserController@store');

Route::get('/search', 'SearchController@index');
Route::get('/search-references', 'SearchController@reference');
Route::get('/search-taxon-names', 'SearchController@taxonName');

Route::get('/countries', 'CountryController@index');
Route::get('/nomenclatures', 'NomenclatureController@index');
Route::get('/ranks', 'RankController@index');

Route::get('/persons/{id}', 'PersonController@show');
Route::get('/persons/{id}/references', 'PersonController@references');
Route::get('/persons/{id}/taxon-names', 'PersonController@taxonNames');
Route::get('/persons/{id}/type-specimens', 'PersonController@typeSpecimens');

Route::resource('references', 'ReferenceController')->only('index', 'show');
Route::get('references/{id}/usages', 'ReferenceController@usages');

Route::get('simple-taxon-names', 'TaxonNameController@simpleIndex');
Route::get('taxon-names', 'TaxonNameController@index');
Route::get('taxon-names/{id}', 'TaxonNameController@show');
Route::get('taxon-names/{id}/references', 'TaxonNameController@references');
Route::get('taxon-names/{id}/accepted', 'TaxonNameController@accepted');
Route::get('taxon-names/{id}/homonyms', 'TaxonNameController@homonyms');
Route::get('taxon-names/{id}/synonyms', 'TaxonNameController@synonyms');
Route::get('taxon-names/{id}/sub-taxon-names', 'TaxonNameController@subTaxonNames');
Route::get('taxon-names/{id}/common-names', 'TaxonNameController@commonNames');
Route::get('taxon-names/{id}/parents', 'TaxonNameController@parents');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', 'UserController@index');
    Route::post('/logout', 'UserController@logout');

    // 人名
    Route::post('/persons', 'PersonController@store');
    Route::put('/persons/{id}', 'PersonController@update');
    Route::get('/persons', 'PersonController@index');

    Route::get('/books', 'BookController@index');

    Route::resource('references', 'ReferenceController')->except('index', 'show');

    Route::post('references/{id}/image', 'ReferenceController@uploadImage');
    Route::resource('taxon-names', 'TaxonNameController')->except('index', 'show');
    Route::get('taxon-names/{id}/info', 'TaxonNameController@info');

    // 我的名錄
    Route::resource('namespaces', 'MyNamespaceController');
    Route::post('namespaces/import/{referenceId}', 'MyNamespaceController@import');

    Route::get('namespaces/{namespaceId}/usages', 'MyNamespaceUsageController@index');
    Route::post('namespaces/{namespaceId}/usages', 'MyNamespaceUsageController@store');
    Route::get('namespaces/{namespaceId}/usages/{usageId}', 'MyNamespaceUsageController@show');
    Route::put('namespaces/{namespaceId}/usages/{usageId}', 'MyNamespaceUsageController@update');

    Route::middleware('auth.admin')->group(function() {
        Route::get('/users', 'UserController@list');
        Route::get('/users/{id}', 'UserController@getUser');
        Route::put('/users/{id}', 'UserController@update');
        Route::put('/users/{id}/status', 'UserController@updateStatus');
        Route::post('/users/{id}', 'UserController@store');
    });

    Route::resource('/favorite-folders', 'FavoriteFolderController');

    Route::get('/favorite-folders-status', 'FavoriteFolderController@getFolderWithItemStatus');

    Route::resource('/favorite-folders/{folderId}/items', 'FavoriteItemController');
});
