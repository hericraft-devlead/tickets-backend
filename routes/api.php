<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\MoodleController;

/*
|--------------------------------------------------------------------------
| RUTA TEST
|--------------------------------------------------------------------------
*/
Route::get('/test', function () {
    return response()->json(['message' => 'API funcionando']);
});


/*
|--------------------------------------------------------------------------
| AUTH MOODLE (usuarios de Moodle)
|--------------------------------------------------------------------------
*/
Route::prefix('auth/moodle')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);      // login Moodle
    Route::post('/logout', [AuthController::class, 'logout']);    // logout Moodle
    Route::get('/check', [AuthController::class, 'checkAuth']);   // token válido
    Route::get('/profile', [AuthController::class, 'profile']);  // perfil Moodle
});


/*
|--------------------------------------------------------------------------
| AUTH LOCAL (usuarios de tu BD)
|--------------------------------------------------------------------------
*/
Route::prefix('auth/local')->group(function () {
    Route::post('/login', [UserController::class, 'login']);      // login local
});


/*
|--------------------------------------------------------------------------
| RUTAS PROTEGIDAS (SANCTUM)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | PERFIL USUARIO LOCAL
    |--------------------------------------------------------------------------
    */
    Route::post('/logout', [UserController::class, 'logout']);
    Route::get('/profile', [UserController::class, 'profile']);
    Route::put('/profile', [UserController::class, 'updateProfile']);


    /*
    |--------------------------------------------------------------------------
    | DEPARTMENTS
    |--------------------------------------------------------------------------
    */
    Route::get('/departments', [DepartmentController::class, 'getDepartments']);
    Route::post('/departments', [DepartmentController::class, 'createDepartment']);


    /*
    |--------------------------------------------------------------------------
    | USERS (SOLO ADMIN)
    |--------------------------------------------------------------------------
    */
    Route::middleware('admin')->group(function () {
        Route::get('/users', [UserController::class, 'getUsers']);
        Route::get('/users/{id}', [UserController::class, 'getUsersById']);
        Route::post('/users', [UserController::class, 'createUser']);
        Route::put('/users/{id}', [UserController::class, 'updateUser']);
        Route::delete('/users/{id}', [UserController::class, 'deleteUser']);
    });
});


/*
|--------------------------------------------------------------------------
| MOODLE API (solo consultas)
|--------------------------------------------------------------------------
*/
Route::prefix('moodle')->group(function () {
    Route::get('/user/{userId}', [MoodleController::class, 'getUser']);
    Route::get('/user/username/{username}', [MoodleController::class, 'getUserByUsername']);
    Route::get('/user/{userId}/courses', [MoodleController::class, 'getUserCourses']);
    Route::get('/user/{userId}/info-data', [MoodleController::class, 'getUserInfoData']);
    Route::get('/courses', [MoodleController::class, 'getCourses']);
    Route::post('/call', [MoodleController::class, 'callFunction']);
});
