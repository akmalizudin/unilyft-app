<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CarpoolController;
use App\Http\Controllers\CarpoolPassengerController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RideOfferController;
use App\Http\Controllers\RideRequestController;
use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

Route::group(["middleware" => ["auth:sanctum"]], function () {

    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/test-csrf', fn() => [1, 2, 3]);
    Route::post('/logout', function (Request $request) {
        $request->user()->currentAccessToken()->delete();
        return response()->noContent();
    });
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/email/resend', [AuthController::class, 'resendEmail']);

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// authenticated only
Route::middleware(['auth:sanctum'])->group(function () {

    Route::get('/me', [AuthController::class, 'me']);

    // for carpool
    Route::get('/carpool', [CarpoolController::class, 'index']);
    Route::get('/carpool/getOffers', [CarpoolController::class, 'getOffers']);
    Route::get('/carpool/getRequests', [CarpoolController::class, 'getRequests']);

    Route::get('/carpool/{id}', [CarpoolController::class, 'showRequested']);
    Route::get('/carpool/offers/{id}', [CarpoolController::class, 'showOffer']);
    Route::get('/carpool/requests/{id}', [CarpoolController::class, 'showRequest']);
    Route::get('/carpool/show/{id}', [CarpoolController::class, 'showCarpool']);

    Route::post('/carpool/offers', [CarpoolController::class, 'createOffer']);
    Route::post('/carpool/requests', [CarpoolController::class, 'createRequest']);
    Route::put('/carpool/{id}', [CarpoolController::class, 'update']);
    Route::delete('/carpool/{id}', [CarpoolController::class, 'destroy']);
    Route::post('/carpool/{id}/join', [CarpoolController::class, 'joinRide']);
    Route::post('/carpool/{id}/provide', [CarpoolController::class, 'provideRide']);
    Route::get('/user/rides', [CarpoolController::class, 'getUserRides']);
    Route::post('/carpools/{carpoolId}/rate', [CarpoolController::class, 'rateCarpool']);
    Route::get('/carpool/feedback/check', [CarpoolController::class, 'hasRated']);

    Route::get('carpool/{id}/passenger-list', [CarpoolController::class, 'getPassengerList']);
    Route::delete('carpool/{id}/passenger', [CarpoolController::class, 'removePassenger']);

    // new seperated rides
    Route::get('/user/rides/joined', [CarpoolController::class, 'getUserJoinedRides']);
    Route::get('/user/rides/published', [CarpoolController::class, 'getUserPublishedRides']);
    Route::get('/user/rides/provided', [CarpoolController::class, 'getUserProvidedRides']);

    // join ride new
    // Route to request to join a carpool
    Route::post('/carpools/{carpoolId}/request-join', [CarpoolController::class, 'requestJoinRide']);

    // Route for the driver to respond to a join request
    Route::post('/carpools/{carpoolId}/respond-join/{userId}', [CarpoolController::class, 'respondJoinRequest']);

    // for feedback
    Route::get('/feedback', [FeedbackController::class, 'index']);
    Route::get('/feedback/{id}', [FeedbackController::class, 'show']);
    Route::post('/feedback', [FeedbackController::class, 'store']);
    Route::put('/feedback/{id}', [FeedbackController::class, 'update']);
    Route::delete('/feedback/{id}', [FeedbackController::class, 'destroy']);

    // update user
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::get('/users', [UserController::class, 'index']);
    Route::patch('/users/{id}', [UserController::class, 'update']);
    Route::put('/users/{id}/verify', [UserController::class, 'verify']);
    Route::patch('/users/{id}/vehicle-details', [UserController::class, 'updateVehicleDetails']);
    Route::get('/users/{id}/detail', [UserController::class, 'viewUserDetail']);

    Route::post('/driver/{driverId}/update-rating', [CarpoolController::class, 'updateDriverRating']);
    Route::get('/driver/{driverId}/feedback', [CarpoolController::class, 'getDriverFeedback']);

    Route::post('/users/{id}/driving-license', [UserController::class, 'uploadDrivingLicense']);
    Route::get('/get-driving-licenses', [UserController::class, 'getDrivingLicenses']);
    Route::get('/show-driving-license/{id}', [UserController::class, 'showDrivingLicense']);

    // admin functions
    Route::get('/admin/get-users', [UserController::class, 'getUsersData']);
    Route::get('/admin/carpool-stats', [CarpoolController::class, 'getCarpoolStats']);
    Route::delete('/admin/delete-user/{id}', [UserController::class, 'destroy']);
    Route::post('/user/{id}/unverify', [UserController::class, 'unverify']);

    // for notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
});