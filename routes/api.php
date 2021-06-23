<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\DeviceController;
use App\Http\Controllers\API\UploadController;
use App\Http\Controllers\API\AnalyticsController;
use App\Http\Controllers\API\SettingsController;
use App\Http\Controllers\API\TeamsController;
use App\Http\Controllers\API\CategoriesController;
use App\Http\Controllers\API\ApplicationsController;
use App\Http\Controllers\API\UsersController;
use App\Http\Controllers\API\ProfilesController;
use App\Http\Controllers\API\AutomationsController;
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

// accepnt invite to team
Route::get('accept-invite/{token}', [TeamsController::class, 'acceptInvite'])->name('teams.accept_invite');

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

    // adding burnout reports

    // adding invite route
    Route::prefix('teams')->group(function() {
        Route::get('invites', [TeamsController::class, 'invites']);
        Route::post('invite', [TeamsController::class, 'invite']);
        Route::post('resend', [TeamsController::class, 'resendInvite']);
    });

    // adding categories / applications
    Route::apiResource('categories', CategoriesController::class);
    Route::apiResource('applications', ApplicationsController::class);

    // adding automations
    Route::apiResource('automations', AutomationsController::class);
});
