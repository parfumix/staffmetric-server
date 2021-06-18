<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\DeviceController;
use App\Http\Controllers\API\UploadController;
use App\Http\Controllers\API\AnalyticsController;
use App\Http\Controllers\API\SettingsController;
use App\Http\Controllers\API\InviteController;
use App\Http\Controllers\API\CategoriesController;
use App\Http\Controllers\API\ApplicationsController;

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

    Route::get('/user', function() {
        return auth()->user();
    });

    // adding device routes
    Route::post('/devices/create', [DeviceController::class, 'create']);

    // adding upload routes
    Route::post('/upload/activities/{device}', [UploadController::class, 'activities']);

    // adding analytics reports
    Route::get('analytics/productivity/{user?}', [AnalyticsController::class, 'productivity']);
    Route::get('analytics/employees/{user?}', [AnalyticsController::class, 'employees']);
    Route::get('analytics/top-apps/{user?}', [AnalyticsController::class, 'topApps']);
    Route::get('analytics/top-categories/{user?}', [AnalyticsController::class, 'topCategories']);

    // adding invite route
    Route::get('invite', [InviteController::class, 'invite']);

    // adding categories / applications
    Route::apiResource('categories', CategoriesController::class);
    Route::apiResource('applications', ApplicationsController::class);
});