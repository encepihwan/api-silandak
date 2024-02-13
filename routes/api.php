<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\KorwilController;

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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });


// Route::middleware(['auth:sanctum'])->group(function () {
//     Route::post('/auth/login', [AuthController::class, 'login']);
// });

Route::group([
    'middleware' => 'api',
], function ($router){
    $router->group(['prefix' => 'auth'], function ($router){
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
    });
    $router->group(['prefix' => 'korwil'], function ($router){
        Route::post('/', [KorwilController::class, 'store']);
    });
});


