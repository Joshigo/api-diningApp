<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Dining\DiningController;
use App\Http\Controllers\Studient\StudientController;
use App\Http\Controllers\User\UserController;
use App\Models\User;
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


Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::put('/auth/update', [AuthController::class, 'update']);
    Route::delete('/auth/delete', [AuthController::class, 'delete']);
    Route::post('/auth/register', [UserController::class, 'store']);

    Route::resource('users', UserController::class);
    Route::put('users/{id}/toggle-status', [UserController::class, 'toggleStatus']);

    Route::resource('studients', StudientController::class);
    Route::post('studients/search', [StudientController::class, 'search']);
    Route::prefix('dining')->group(function () {
        Route::get('/', [DiningController::class, 'index']);
        Route::get('/stats/today', [DiningController::class, 'todayStats']);
        Route::post('/mark-eaten', [DiningController::class, 'markAsEaten']);
        Route::post('/mark-not-eaten', [DiningController::class, 'markAsNotEaten']);
    });
});
Route::post('/auth/login', [AuthController::class, 'login']);
