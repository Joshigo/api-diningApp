<?php

use App\Http\Controllers\Dining\DiningController;
use App\Http\Controllers\Studient\StudientController;
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

Route::resource('studients', StudientController::class);

Route::prefix('dining')->group(function () {
    Route::get('/', [DiningController::class, 'index']);
    Route::get('/stats/today', [DiningController::class, 'todayStats']);
    Route::post('/mark-eaten', [DiningController::class, 'markAsEaten']);
    Route::post('/mark-not-eaten', [DiningController::class, 'markAsNotEaten']);
});
