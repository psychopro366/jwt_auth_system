<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\PasswordController;
use App\Http\Controllers\API\RefreshTokenController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


// Auth controller routes 
Route::controller(AuthController::class)->group(function() {

    // All public routes 
    Route::post('/register', 'register')->name('register.post');
    Route::post('/login', 'login')->name('login.post');

});

// Refresh token when current token expired
Route::post('/refresh-token', [RefreshTokenController::class, 'refreshToken']);

// All protected Routes here 
Route::controller(UserController::class)->group(function () {
    Route::middleware('auth:api')->group(function () {
        Route::get('/show-users', 'getAllUsers')->name('show-users.get');
        Route::get('/current-user', 'getAuthenticatedUser')->name('current-user.get');
        Route::put('/profile', [UserController::class, 'updateProfile'])->name('profile.post');
        //or Route::match(['put', 'patch'], '/profile', ...);
        Route::post('/logout', 'logout')->name('logout.post');
    });
});


 Route::post('/change-passsword', [PasswordController::class, 'PasswordController'])
 ->middleware('auth:api');


 // Forgot Password 
 Route::post('/forgot-password', [PasswordController::class, 'forgotPassword'])
    ->middleware('throttle:3,1'); // prevent abuse

Route::post('/reset-password', [PasswordController::class, 'resetPassword'])->name('password.reset');

//Ensure jwt.auth middleware is registered in bootstrap/app.php as an alias.
//Route::middleware('jwt.auth')->post('/logout', [AuthController::class, 'logout']);


