<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\LoginController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['guest'])->group(function () {
    Route::get('/', function () {
        return redirect()->route('login');
    });

    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::get('/register', function () {
        return view('auth.register');
    })->name('register');

    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [LoginController::class, 'login']);
});

Route::middleware(['auth'])->group(function () {
    // Dashboard with no-cache headers
    Route::get('/dashboard', function (Request $request) {
        $user = $request->user();

        $hour = now()->hour;
        $greeting = match (true) {
            $hour < 12 => 'Good morning',
            $hour < 18 => 'Good afternoon',
            default => 'Good evening',
        };

        $nameParts = explode(' ', trim($user->name));
        $initials = strtoupper(
            substr($nameParts[0], 0, 1) . substr(end($nameParts), 0, 1)
        );

        return response()
            ->view('dashboard', [
                'greeting' => $greeting,
                'userInitials' => $initials,
            ])
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    })->name('dashboard');

    // Logout
    Route::post('/logout', [LoginController::class, 'logout']);

    // Category Page (view) with no-cache
    Route::get('/categories', function () {
        return response()
            ->view('categories.index')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    })->name('categories.index');

    // Category API routes (for AJAX requests)
    Route::prefix('api/categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::get('/type/{type}', [CategoryController::class, 'getByType']);
        Route::post('/', [CategoryController::class, 'store']);
        Route::get('/{id}', [CategoryController::class, 'show']);
        Route::put('/{id}', [CategoryController::class, 'update']);
        Route::delete('/{id}', [CategoryController::class, 'destroy']);
    });

    // Budget Page (view) with no-cache
    Route::get('/budgets', function () {
        return response()
            ->view('budgets.index')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    })->name('budgets.index');

    // Budget API routes (for AJAX requests)
    Route::prefix('api/budgets')->group(function () {
        Route::get('/', [BudgetController::class, 'index']);
        Route::get('/active', [BudgetController::class, 'getActive']);
        Route::get('/summary', [BudgetController::class, 'getSummary']);
        Route::post('/', [BudgetController::class, 'store']);
        Route::get('/{id}', [BudgetController::class, 'show']);
        Route::put('/{id}', [BudgetController::class, 'update']);
        Route::delete('/{id}', [BudgetController::class, 'destroy']);
    });
});
