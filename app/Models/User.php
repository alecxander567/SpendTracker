<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'currency',
        'timezone',
        'monthly_income',
        'budget_cycle_start',
        'preferences',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'preferences' => 'array',
        'monthly_income' => 'decimal:2',
        'budget_cycle_start' => 'date',
    ];

    /**
     * The model's default values for attributes.
     */
    protected $attributes = [
        'currency' => 'USD',
        'timezone' => 'UTC',
    ];

    /**
     * Get the categories for the user.
     */
    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    /**
     * Get the budgets for the user.
     */
    public function budgets()
    {
        return $this->hasMany(Budget::class);
    }

    // Commented out - Expense model doesn't exist yet
    // public function expenses()
    // {
    //     return $this->hasMany(Expense::class);
    // }

    // Commented out - SavingsGoal model doesn't exist yet
    // public function savingsGoals()
    // {
    //     return $this->hasMany(SavingsGoal::class);
    // }

    // public function getTotalExpensesForMonth($month, $year): float
    // {
    //     return $this->expenses()
    //         ->whereMonth('date', $month)
    //         ->whereYear('date', $year)
    //         ->whereHas('category', function ($query) {
    //             $query->where('type', 'expense');
    //         })
    //         ->sum('amount');
    // }

    // public function getTotalIncomeForMonth($month, $year): float
    // {
    //     return $this->expenses()
    //         ->whereMonth('date', $month)
    //         ->whereYear('date', $year)
    //         ->whereHas('category', function ($query) {
    //             $query->where('type', 'income');
    //         })
    //         ->sum('amount');
    // }

    // public function getNetCashFlow($month, $year): float
    // {
    //     return $this->getTotalIncomeForMonth($month, $year) - $this->getTotalExpensesForMonth($month, $year);
    // }

    // public function getTotalSavings(): float
    // {
    //     return $this->savingsGoals()->sum('current_amount');
    // }

    // public function getTotalBudgeted(): float
    // {
    //     return $this->budgets()
    //         ->where('is_active', true)
    //         ->sum('amount');
    // }

    // public function getDashboardSummary(): array
    // {
    //     $currentMonth = now()->month;
    //     $currentYear = now()->year;

    //     return [
    //         'monthly_income' => $this->getTotalIncomeForMonth($currentMonth, $currentYear),
    //         'monthly_expenses' => $this->getTotalExpensesForMonth($currentMonth, $currentYear),
    //         'net_cash_flow' => $this->getNetCashFlow($currentMonth, $currentYear),
    //         'total_savings' => $this->getTotalSavings(),
    //         'total_budgeted' => $this->getTotalBudgeted(),
    //     ];
    // }

    // public function getRecentExpenses($limit = 10)
    // {
    //     return $this->expenses()
    //         ->with('category')
    //         ->orderBy('date', 'desc')
    //         ->limit($limit)
    //         ->get();
    // }

    public function getCurrencySymbol(): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'PHP' => '₱',
            'AUD' => 'A$',
            'CAD' => 'C$',
        ];
        return $symbols[$this->currency] ?? '$';
    }

    public function formatCurrency($amount): string
    {
        return $this->getCurrencySymbol() . number_format($amount, 2);
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted()
    {
        static::created(function ($user) {
            $user->createDefaultCategories();
        });
    }

    /**
     * Create default categories for a new user.
     */
    public function createDefaultCategories()
    {
        $defaultExpenseCategories = [
            ['name' => 'Food & Dining', 'type' => 'expense', 'color' => '#FF6B6B'],
            ['name' => 'Transportation', 'type' => 'expense', 'color' => '#4ECDC4'],
            ['name' => 'Shopping', 'type' => 'expense', 'color' => '#45B7D1'],
            ['name' => 'Entertainment', 'type' => 'expense', 'color' => '#96CEB4'],
            ['name' => 'Housing', 'type' => 'expense', 'color' => '#FFEAA7'],
            ['name' => 'Utilities', 'type' => 'expense', 'color' => '#DDA0DD'],
            ['name' => 'Healthcare', 'type' => 'expense', 'color' => '#FF6B6B'],
            ['name' => 'Education', 'type' => 'expense', 'color' => '#74B9FF'],
            ['name' => 'Insurance', 'type' => 'expense', 'color' => '#A29BFE'],
            ['name' => 'Personal Care', 'type' => 'expense', 'color' => '#FD79A8'],
            ['name' => 'Gifts', 'type' => 'expense', 'color' => '#FDCB6E'],
            ['name' => 'Travel', 'type' => 'expense', 'color' => '#00CEC9'],
        ];

        $defaultIncomeCategories = [
            ['name' => 'Salary', 'type' => 'income', 'color' => '#00B894'],
            ['name' => 'Freelance', 'type' => 'income', 'color' => '#0984E3'],
            ['name' => 'Investments', 'type' => 'income', 'color' => '#6C5CE7'],
            ['name' => 'Gifts Received', 'type' => 'income', 'color' => '#FDCB6E'],
            ['name' => 'Refunds', 'type' => 'income', 'color' => '#E17055'],
            ['name' => 'Other Income', 'type' => 'income', 'color' => '#636E72'],
        ];

        foreach (array_merge($defaultExpenseCategories, $defaultIncomeCategories) as $category) {
            $this->categories()->create([
                'name' => $category['name'],
                'type' => $category['type'],
                'color' => $category['color'],
                'is_default' => true,
            ]);
        }
    }
}
