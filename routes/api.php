<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MoodleController;
use App\Http\Controllers\AuthController;


Route::get('/test', function () {
    return response()->json(['message' => 'API funcionando']);
});

// Rutas PÚBLICAS de autenticación
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::get('/check', [AuthController::class, 'checkAuth']);
});

// Rutas PÚBLICAS de Moodle
Route::prefix('moodle')->group(function () {
    Route::get('/user/{userId}', [MoodleController::class, 'getUser']);
    Route::get('/user/username/{username}', [MoodleController::class, 'getUserByUsername']);
    Route::get('/user/{userId}/courses', [MoodleController::class, 'getUserCourses']);
    Route::get('/courses', [MoodleController::class, 'getCourses']);
    Route::post('/call', [MoodleController::class, 'callFunction']);
    Route::get('/user/{userId}/info-data', [MoodleController::class, 'getUserInfoData']);
});