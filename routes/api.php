<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ProjectTypeController;
use App\Http\Controllers\Api\UserTaskController;
use App\Http\Controllers\Api\NoteController;
use App\Http\Controllers\Api\FlashCardController;
use App\Http\Controllers\Api\FlashCardGroupController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/me', [AuthController::class, 'user']);
    Route::any('/logout', [AuthController::class, 'logout']);

    Route::apiResource('users', UserController::class);

    Route::get('/me/project-types', [ProjectTypeController::class, 'index']);
    Route::post('/me/project-types', [ProjectTypeController::class, 'store']);
    Route::put('/me/project-types/{projectType}', [ProjectTypeController::class, 'update']);
    Route::delete('/me/project-types/{projectType}', [ProjectTypeController::class, 'destroy']);

    Route::get('/me/projects', [ProjectController::class, 'index']);
    Route::post('/me/projects', [ProjectController::class, 'store']);
    Route::get('/me/projects/{project}', [ProjectController::class, 'show']);
    Route::put('/me/projects/{project}', [ProjectController::class, 'update']);
    Route::patch('/me/projects/{project}', [ProjectController::class, 'update']);
    Route::delete('/me/projects/{project}', [ProjectController::class, 'destroy']);

    Route::get('/me/tasks', [UserTaskController::class, 'index']);
    Route::get('/me/tasks/summary', [UserTaskController::class, 'summary']);
    Route::get('/me/tasks/calendar', [UserTaskController::class, 'calendar']);
    Route::post('/me/tasks', [UserTaskController::class, 'store']);
    Route::get('/me/tasks/{userTask}', [UserTaskController::class, 'show']);
    Route::put('/me/tasks/{userTask}', [UserTaskController::class, 'update']);
    Route::patch('/me/tasks/{userTask}', [UserTaskController::class, 'update']);
    Route::delete('/me/tasks/{userTask}', [UserTaskController::class, 'destroy']);

    Route::get('/me/notes', [NoteController::class, 'index']);
    Route::post('/me/notes', [NoteController::class, 'store']);
    Route::get('/me/notes/{note}', [NoteController::class, 'show']);
    Route::put('/me/notes/{note}', [NoteController::class, 'update']);
    Route::patch('/me/notes/{note}', [NoteController::class, 'update']);
    Route::delete('/me/notes/{note}', [NoteController::class, 'destroy']);

    Route::get('/me/flash-card-groups', [FlashCardGroupController::class, 'index']);
    Route::get('/me/flash-card-groups/all', [FlashCardGroupController::class, 'all']);
    Route::post('/me/flash-card-groups', [FlashCardGroupController::class, 'store']);
    Route::get('/me/flash-card-groups/{flashCardGroup}', [FlashCardGroupController::class, 'show']);
    Route::put('/me/flash-card-groups/{flashCardGroup}', [FlashCardGroupController::class, 'update']);
    Route::patch('/me/flash-card-groups/{flashCardGroup}', [FlashCardGroupController::class, 'update']);
    Route::delete('/me/flash-card-groups/{flashCardGroup}', [FlashCardGroupController::class, 'destroy']);

    Route::get('/me/flash-cards', [FlashCardController::class, 'index']);
    Route::get('/me/flash-cards/study', [FlashCardController::class, 'study']);
    Route::post('/me/flash-cards', [FlashCardController::class, 'store']);
    Route::get('/me/flash-cards/{flashCard}', [FlashCardController::class, 'show']);
    Route::get('/me/flash-cards/{flashCard}/image', [FlashCardController::class, 'image'])->name('api.flash-cards.image');
    Route::put('/me/flash-cards/{flashCard}', [FlashCardController::class, 'update']);
    Route::patch('/me/flash-cards/{flashCard}', [FlashCardController::class, 'update']);
    Route::delete('/me/flash-cards/{flashCard}', [FlashCardController::class, 'destroy']);
    Route::post('/me/flash-cards/{flashCard}/review', [FlashCardController::class, 'review']);
});
