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
| RUTAS PÚBLICAS (SIN AUTENTICACIÓN)
|--------------------------------------------------------------------------
*/
Route::get('/test', function () {
    return response()->json(['message' => 'API funcionando']);
});

/*
|--------------------------------------------------------------------------
| TICKETS PÚBLICOS (SIN LOGIN)
|--------------------------------------------------------------------------
| Permite crear tickets sin sesión Moodle
*/
Route::post('/tickets', [TicketController::class, 'createTicket']);
Route::get('/categories', [CategoryController::class, 'getCategories']);
Route::get('/priorities', [PriorityController::class, 'getPriorities']);
Route::get('/tags', [TagController::class, 'getTags']);
Route::get('/ticket-statuses', [TicketStatusController::class, 'getStatuses']);

/*
|--------------------------------------------------------------------------
| AUTH MOODLE (usuarios Moodle)
|--------------------------------------------------------------------------
*/
Route::prefix('auth/moodle')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth.moodle');
    Route::get('/check', [AuthController::class, 'checkAuth'])->middleware('auth.moodle');
    Route::get('/profile/{userId}', [AuthController::class, 'profile'])->middleware('auth.moodle');
});

/*
|--------------------------------------------------------------------------
| AUTH LOCAL (usuarios administradores/agentes)
|--------------------------------------------------------------------------
*/
Route::prefix('auth/local')->group(function () {
    Route::post('/login', [UserController::class, 'login']);
});

/*
|--------------------------------------------------------------------------
| RUTAS PROTEGIDAS PARA USUARIOS LOCALES (admin/agentes)
|--------------------------------------------------------------------------
| TODAS las rutas que estaban en auth:sanctum ahora van aquí
*/
Route::middleware('auth.local')->group(function () {

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
    | DEPARTMENTS (solo locales)
    |--------------------------------------------------------------------------
    */
    Route::get('/departments', [DepartmentController::class, 'getDepartments']);
    Route::post('/departments', [DepartmentController::class, 'createDepartment']);

    /*
    |--------------------------------------------------------------------------
    | CATEGORIES (crear - solo locales)
    |--------------------------------------------------------------------------
    */
    Route::post('/categories', [CategoryController::class, 'createCategory']);

    /*
    |--------------------------------------------------------------------------
    | TAGS (solo locales)
    |--------------------------------------------------------------------------
    */
    Route::post('/tags', [TagController::class, 'createTag']);


    /*
    |--------------------------------------------------------------------------
    | TICKETS (solo usuarios locales - admin/agentes)
    |--------------------------------------------------------------------------
    */
    Route::get('/tickets', [TicketController::class, 'getTickets']);
    Route::get('/tickets/assigned-to/{userId}', [TicketController::class, 'getTicketsAssignedToLocalUser']);
    Route::get('/tickets/unassigned', [TicketController::class, 'getUnassignedTickets']);
    Route::get('/tickets/{id}', [TicketController::class, 'getTicket']);
    Route::put('/tickets/{id}', [TicketController::class, 'updateTicket']);
    Route::patch('/tickets/{id}', [TicketController::class, 'updateTicket']);
    Route::delete('/tickets/{id}', [TicketController::class, 'deleteTicket']);

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
| RUTAS PROTEGIDAS PARA USUARIOS MOODLE
|--------------------------------------------------------------------------
| Agrega aquí las rutas que los usuarios Moodle necesitan
*/
Route::middleware('auth.moodle')->group(function () {
    // Ejemplo: Si quieres que los usuarios Moodle puedan ver SUS tickets
    Route::get('/my-tickets', [TicketController::class, 'getMyTickets']);
    
    // Si necesitas otras rutas específicas para Moodle, agrégalas aquí
    // Route::get('/my-profile', [AuthController::class, 'getMyProfile']);
    // Route::get('/tickets/{id}', [TicketController::class, 'getMyTicket']);
});

/*
|--------------------------------------------------------------------------
| MOODLE API (solo consultas - públicas)
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




Route::middleware('auth.any')->get('/tickets/moodle-user/{moodleUserId}', [TicketController::class, 'getTicketsByMoodleUser']);