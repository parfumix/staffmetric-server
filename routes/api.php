<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\DeviceController;
use App\Http\Controllers\API\UploadController;
use App\Http\Controllers\API\AnalyticsController;
use App\Http\Controllers\API\SettingsController;
use App\Http\Controllers\API\InviteController;
use App\Http\Controllers\API\CategoriesController;
use App\Http\Controllers\API\ApplicationsController;
use App\Http\Controllers\API\UsersController;
use App\Http\Controllers\API\ProfilesController;
use App\Http\Controllers\AuthController;

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
Route::post('/token/auth', [AuthController::class, 'token']);
Route::post('/device/auth', [DeviceController::class, 'login']);

Route::group(['middleware' => ['auth:sanctum', 'apilogger']], function () {

    // get current authenticated user
    Route::get('/user', function() {
        return auth()->user();
    });

    // adding device routes
    Route::post('/devices/create', [DeviceController::class, 'create']);

    // adding upload routes
    Route::post('/upload/activities/{device}', [UploadController::class, 'activities']);

    // adding users controller
    Route::get('/users',  [UsersController::class, 'index'] );
    Route::get('/profiles',  [ProfilesController::class, 'index'] );

    // adding analytics reports
    Route::match(['get', 'post'], 'analytics/productivity', [AnalyticsController::class, 'productivity']);
    Route::match(['get', 'post'], 'analytics/productivity-by-employee', [AnalyticsController::class, 'employees']);
    Route::match(['get', 'post'], 'analytics/top-apps', [AnalyticsController::class, 'topApps']);
    Route::match(['get', 'post'], 'analytics/top-categories', [AnalyticsController::class, 'topCategories']);

    // adding invite route
    Route::get('invite', [InviteController::class, 'invite']);

    // adding categories / applications
    Route::apiResource('categories', CategoriesController::class);
    Route::apiResource('applications', ApplicationsController::class);
});
