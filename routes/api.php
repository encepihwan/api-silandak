<?php

use App\Models\ConditionRoadBridge;
use App\Models\RoadActivities;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConditionRoadBridgeController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\KorwilController;
use App\Http\Controllers\RoadActivitiesController;
use App\Models\Korwil;

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
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::prefix('korwil')->middleware('auth:api')->group(function ($router){
    Route::post('/', [KorwilController::class, 'index']);
    Route::get('/resume', [KorwilController::class, 'resume']);
    Route::get('/sumary', [KorwilController::class, 'sumary']);
    Route::post('/create', [KorwilController::class, 'store']);
    Route::post('/import', [KorwilController::class, 'import']);
    Route::post('/stack-chart', [KorwilController::class, 'stackchart']);
    Route::post('/pie-chart', [KorwilController::class, 'piechart']);
});

Route::prefix('condition')->middleware('auth:api')->group(function ($router){
    Route::get('/', [ConditionRoadBridgeController::class, 'index']);
    Route::post('/import', [ConditionRoadBridgeController::class, 'import']);
});

Route::prefix('activity')->middleware('auth:api')->group(function ($router){
    Route::post('/', [RoadActivitiesController::class, 'index']);
    Route::post('/import', [RoadActivitiesController::class, 'import']);
});

Route::delete('/korwil/delete-all', function () {
    Korwil::truncate(); // Menghapus semua data dari tabel korwil
    return response()->json(['message' => 'Semua data korwil berhasil dihapus'], 200);
});

Route::delete('/condition/delete-all', function () {
    ConditionRoadBridge::truncate(); // Menghapus semua data dari tabel 
    return response()->json(['message' => 'Semua data korwil berhasil dihapus'], 200);
});

Route::delete('/road-activities/delete-all', function () {
    RoadActivities::truncate(); // Menghapus semua data dari tabel 
    return response()->json(['message' => 'Semua data korwil berhasil dihapus'], 200);
});

// Route::prefix('download')->middleware('auth:api')->group(function ($router){
    Route::get('/download/{filename}', [DownloadController::class, 'download']);
// });


