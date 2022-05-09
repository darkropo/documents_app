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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::controller('App\Http\Controllers\Api\DocumentController'::class)->group(function () {
    Route::prefix('projects/get-documents')->group(function (){
        Route::get('/sorted', 'getProjectDocumentsorted');
        Route::get('/sortedfiles/{data}/{userData}', 'getsortedFiles');
    });
    
});


