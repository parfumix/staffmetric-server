<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\DeviceController;
use App\Http\Controllers\API\UploadController;
use App\Http\Controllers\API\AnalyticsController;
use App\Http\Controllers\API\TeamsController;
use App\Http\Controllers\API\InviteController;
use App\Http\Controllers\API\CategoriesController;
use App\Http\Controllers\API\ApplicationsController;
use App\Http\Controllers\API\UsersController;
use App\Http\Controllers\API\ProfilesController;
use App\Http\Controllers\API\GoalsController;
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
Route::post('/token/auth', [AuthController::class, 'token']); //used for local testing
Route::post('/device/auth', [DeviceController::class, 'login']); //used for client device auth

// accept team invite
Route::get('accept-invite/{token}', [InviteController::class, 'acceptInvite'])->name('teams.accept_invite');

Route::group(['middleware' => ['auth:sanctum', 'apilogger']], function () {

    // get current authenticated user
    Route::get('/user', function() {
        return new \App\Http\Resources\UserResource(auth()->user());
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
    Route::match(['get', 'post'], 'analytics/burnout-and-engagment', [AnalyticsController::class, 'burnout']);
    Route::match(['get', 'post'], 'analytics/engagment-by-employees', [AnalyticsController::class, 'engagmentEmployees']);
    Route::match(['get', 'post'], 'analytics/top-engaged-employees', [AnalyticsController::class, 'topEngagedEmployees']);
    Route::match(['get', 'post'], 'analytics/top-by-employees', [AnalyticsController::class, 'topByEmployees']);
    Route::match(['get', 'post'], 'analytics/attendace-and-overtime', [AnalyticsController::class, 'attendance']);

    // adding invite route
    Route::get('invites', [InviteController::class, 'invites']);
    Route::post('invite', [InviteController::class, 'invite']);
    Route::post('resend-invite/{invite_id}', [InviteController::class, 'resendInvite']);

    // adding categories / applications
    Route::apiResource('teams', TeamsController::class);
    Route::apiResource('goals', GoalsController::class);
    Route::apiResource('categories', CategoriesController::class);
    Route::apiResource('applications', ApplicationsController::class);

    // adding automations
    Route::apiResource('automations', AutomationsController::class);
});
