<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LoginController;
use Illuminate\Support\Facades\Route;

// NOTE: Expense, Budget, and Category API routes were removed from this file.
// They are already defined in routes/web.php under Route::prefix('api/expenses'),
// Route::prefix('api/budgets'), and Route::prefix('api/categories'). Having
// them in BOTH files caused route collisions — whichever file's "/{id}"
// wildcard route loaded first was swallowing requests like "/api/expenses/weekly"
// and treating "weekly" as a numeric ID, which is what caused the Postgres
// "invalid input syntax for type bigint" error.
//
// Going forward: all expense/budget/category routes belong in web.php only.
// This file (api.php) should only hold things that don't already exist there.

// Public API routes with web middleware (for session support)
Route::middleware(['web'])->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [LoginController::class, 'login']);
});

// Protected API routes - use both web and auth
Route::middleware(['web', 'auth:sanctum'])->group(function () {
    Route::post('/logout', [LoginController::class, 'logout']);
    Route::get('/user', [LoginController::class, 'user']);
});
