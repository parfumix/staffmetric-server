<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\DeviceController;
use App\Http\Controllers\API\UploadController;
use App\Http\Controllers\API\AnalyticsController;

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

// authenticate by device uuid
Route::post('/device/auth', [DeviceController::class, 'login']);

Route::group(['middleware' => ['auth:sanctum', 'apilogger']], function () {
    // adding device routes
    Route::post('/devices/create', [DeviceController::class, 'create']);

    // adding upload routes
    Route::post('/upload/activities/{device}', [UploadController::class, 'activities']);

    // adding analytics reports
    Route::get('analytics/productivity/{user?}', [AnalyticsController::class, 'productivity']);
    Route::get('analytics/burnout/{user?}', [AnalyticsController::class, 'burnout']);
});