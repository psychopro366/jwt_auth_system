<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::controller(AuthController::class)->group(function() {

    // All public routes 
    Route::post('/register', 'register')->name('register.post');
    Route::post('/login', 'login')->name('login.post');

    // All protected Routes here 
    Route::middleware('auth:api')->group(function () {
        Route::post('/logout', 'logout')->name('logout.post');
    });
});


//Ensure jwt.auth middleware is registered in bootstrap/app.php as an alias.
//Route::middleware('jwt.auth')->post('/logout', [AuthController::class, 'logout']);


