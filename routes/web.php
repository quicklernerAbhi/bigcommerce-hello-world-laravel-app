<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BigComAuthController;
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


// Big Commerce Routes

// 1) Authentication callback route 
Route::get('/auth-callback', [BigComAuthController::class, 'authCallback']);

// 2) this will get called whenever a user opens the app in BigCommerce.
Route::get('/auth-load', [BigComAuthController::class, 'authLoad']);