<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\SavingsGoalController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\PingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ============================================
// PING / HEALTH CHECK (for uptime monitoring)
// ============================================
Route::get('/ping', [PingController::class, 'ping'])->name('ping');
Route::get('/ping/simple', [PingController::class, 'simplePing'])->name('ping.simple');
Route::get('/health', [PingController::class, 'healthCheck'])->name('health');

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
    Route::post('/forgot-password/check-email', [AuthController::class, 'checkEmail']);
    Route::post('/forgot-password/reset', [AuthController::class, 'resetPassword']);
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

    // ============================================
    // CATEGORIES
    // ============================================
    Route::get('/categories', function () {
        return response()
            ->view('categories.index')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    })->name('categories.index');

    Route::prefix('api/categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::get('/type/{type}', [CategoryController::class, 'getByType']);
        Route::post('/', [CategoryController::class, 'store']);
        Route::get('/{id}', [CategoryController::class, 'show']);
        Route::put('/{id}', [CategoryController::class, 'update']);
        Route::delete('/{id}', [CategoryController::class, 'destroy']);
    });

    // ============================================
    // BUDGETS
    // ============================================
    Route::get('/budgets', function () {
        return response()
            ->view('budgets.index')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    })->name('budgets.index');

    Route::prefix('api/budgets')->group(function () {
        Route::get('/', [BudgetController::class, 'index']);
        Route::get('/active', [BudgetController::class, 'getActive']);
        Route::get('/active-budgets', [BudgetController::class, 'getActiveBudgets']);
        Route::get('/summary', [BudgetController::class, 'getSummary']);
        Route::post('/', [BudgetController::class, 'store']);
        Route::get('/{id}', [BudgetController::class, 'show']);
        Route::put('/{id}', [BudgetController::class, 'update']);
        Route::delete('/{id}', [BudgetController::class, 'destroy']);
    });

    // ============================================
    // EXPENSES
    // ============================================
    // Expense Web Routes (for views)
    Route::get('/expenses', function () {
        return response()
            ->view('expenses.index')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    })->name('expenses.index');

    Route::get('/expenses/create', function () {
        return view('expenses.create');
    })->name('expenses.create');

    Route::get('/expenses/{id}/edit', function ($id) {
        return view('expenses.edit', ['id' => $id]);
    })->name('expenses.edit');

    // Expense API routes (for AJAX requests)
    Route::prefix('api/expenses')->group(function () {
        Route::get('/', [ExpenseController::class, 'index']);
        Route::get('/type/{type}', [ExpenseController::class, 'getByType']);
        Route::get('/month', [ExpenseController::class, 'getByMonth']);
        Route::get('/summary', [ExpenseController::class, 'getSummary']);
        Route::get('/recurring', [ExpenseController::class, 'getRecurring']);
        Route::get('/weekly', [ExpenseController::class, 'getWeeklySpending']);
        Route::get('/top-categories-weekly', [ExpenseController::class, 'getTopCategoriesWeekly']);
        Route::post('/', [ExpenseController::class, 'store']);
        Route::get('/{id}', [ExpenseController::class, 'show']);
        Route::put('/{id}', [ExpenseController::class, 'update']);
        Route::delete('/{id}', [ExpenseController::class, 'destroy']);
    });

    // ============================================
    // INCOMES
    // ============================================
    // Income Web Routes (for views)
    Route::get('/incomes', function () {
        return response()
            ->view('incomes.index')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    })->name('incomes.index');

    Route::get('/incomes/create', function () {
        return view('incomes.create');
    })->name('incomes.create');

    Route::get('/incomes/{id}/edit', function ($id) {
        return view('incomes.edit', ['id' => $id]);
    })->name('incomes.edit');

    // Income API routes (for AJAX requests)
    Route::prefix('api/incomes')->group(function () {
        Route::get('/', [IncomeController::class, 'index']);
        Route::get('/summary', [IncomeController::class, 'summary']);
        Route::get('/recurring', [IncomeController::class, 'recurring']);
        Route::get('/sources', [IncomeController::class, 'sources']);
        Route::get('/statistics', [IncomeController::class, 'statistics']);
        Route::get('/export', [IncomeController::class, 'export']);
        Route::post('/', [IncomeController::class, 'store']);
        Route::post('/bulk-delete', [IncomeController::class, 'bulkDelete']);
        Route::get('/{id}', [IncomeController::class, 'show']);
        Route::put('/{id}', [IncomeController::class, 'update']);
        Route::delete('/{id}', [IncomeController::class, 'destroy']);
        Route::patch('/{id}/toggle-active', [IncomeController::class, 'toggleActive']);
    });

    // ============================================
    // SAVINGS GOALS
    // ============================================
    // Savings Goals Web Routes (for views)
    Route::get('/savings', function () {
        return response()
            ->view('savings.index')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    })->name('savings.index');

    Route::get('/savings/create', function () {
        return view('savings.create');
    })->name('savings.create');

    Route::get('/savings/{id}/edit', function ($id) {
        return view('savings.edit', ['id' => $id]);
    })->name('savings.edit');

    // Savings Goals API routes (for AJAX requests)
    Route::prefix('api/savings')->group(function () {
        Route::get('/', [SavingsGoalController::class, 'index']);
        Route::get('/statistics', [SavingsGoalController::class, 'statistics']);
        Route::post('/', [SavingsGoalController::class, 'store']);
        Route::post('/{id}/add-funds', [SavingsGoalController::class, 'addFunds']);
        Route::post('/{id}/withdraw-funds', [SavingsGoalController::class, 'withdrawFunds']);
        Route::get('/{id}', [SavingsGoalController::class, 'show']);
        Route::put('/{id}', [SavingsGoalController::class, 'update']);
        Route::delete('/{id}', [SavingsGoalController::class, 'destroy']);
    });

    // ============================================
    // SETTINGS
    // ============================================
    // Settings Web Routes (for views)
    Route::get('/settings', function () {
        return response()
            ->view('settings.index')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    })->name('settings.index');

    Route::get('/settings/profile', function () {
        return response()
            ->view('settings.profile')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    })->name('settings.profile');

    Route::get('/settings/preferences', function () {
        return response()
            ->view('settings.preferences')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    })->name('settings.preferences');

    // Settings API routes (for AJAX requests)
    Route::prefix('api/settings')->group(function () {
        // Profile
        Route::get('/profile', [SettingsController::class, 'profile']);
        Route::put('/profile', [SettingsController::class, 'updateProfile']);

        // Password
        Route::put('/password', [SettingsController::class, 'updatePassword']);

        // Preferences
        Route::put('/preferences', [SettingsController::class, 'updatePreferences']);

        // Account
        Route::delete('/account', [SettingsController::class, 'deleteAccount']);

        // Data
        Route::get('/currencies', [SettingsController::class, 'getCurrencies']);
        Route::get('/timezones', [SettingsController::class, 'getTimezones']);
    });
});
