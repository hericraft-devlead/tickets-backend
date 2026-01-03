<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\PriorityController;
use App\Http\Controllers\TicketStatusController;
use App\Http\Controllers\TicketController;
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
| AUTH MOODLE (usuarios Moodle)
|--------------------------------------------------------------------------
*/
Route::prefix('auth/moodle')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/check', [AuthController::class, 'checkAuth']);
    Route::get('/profile', [AuthController::class, 'profile']);
});


/*
|--------------------------------------------------------------------------
| AUTH LOCAL
|--------------------------------------------------------------------------
*/
Route::prefix('auth/local')->group(function () {
    Route::post('/login', [UserController::class, 'login']);
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
    | CATEGORIES
    |--------------------------------------------------------------------------
    */
    Route::get('/categories', [CategoryController::class, 'getCategories']);
    Route::post('/categories', [CategoryController::class, 'createCategory']);


    /*
    |--------------------------------------------------------------------------
    | TAGS
    |--------------------------------------------------------------------------
    */
    Route::get('/tags', [TagController::class, 'getTags']);
    Route::post('/tags', [TagController::class, 'createTag']);


    /*
    |--------------------------------------------------------------------------
    | PRIORITIES (solo lectura)
    |--------------------------------------------------------------------------
    */
    Route::get('/priorities', [PriorityController::class, 'getPriorities']);


    /*
    |--------------------------------------------------------------------------
    | TICKET STATUS
    |--------------------------------------------------------------------------
    */
    Route::get('/ticket-statuses', [TicketStatusController::class, 'getStatuses']);


    /*
    |--------------------------------------------------------------------------
    | TICKETS
    |--------------------------------------------------------------------------
    */
    Route::get('/tickets', [TicketController::class, 'getTickets']);
    Route::post('/tickets', [TicketController::class, 'createTicket']);


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
| TICKETS PÚBLICOS (SIN LOGIN)
|--------------------------------------------------------------------------
| Permite crear tickets sin sesión Moodle
*/
Route::post('/public/tickets', [TicketController::class, 'createTicket']);


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
