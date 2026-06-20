<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

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

    /**
     * Get the expenses for the user.
     */
    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Get the incomes for the user.
     */
    public function incomes()
    {
        return $this->hasMany(Income::class);
    }

    /**
     * Get the savings goals for the user.
     */
    // public function savingsGoals()
    // {
    //     return $this->hasMany(SavingsGoal::class);
    // }

    /**
     * Get total expenses for a specific month and year.
     */
    public function getTotalExpensesForMonth($month, $year): float
    {
        return $this->expenses()
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->whereHas('category', function ($query) {
                $query->where('type', 'expense');
            })
            ->sum('amount');
    }

    /**
     * Get total income for a specific month and year.
     */
    public function getTotalIncomeForMonth($month, $year): float
    {
        return $this->incomes()
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->where('is_active', true)
            ->sum('amount');
    }

    /**
     * Get total income for a specific period.
     */
    public function getTotalIncomeForPeriod($startDate, $endDate): float
    {
        return $this->incomes()
            ->whereBetween('date', [$startDate, $endDate])
            ->where('is_active', true)
            ->sum('amount');
    }

    /**
     * Get total expenses for a specific period.
     */
    public function getTotalExpensesForPeriod($startDate, $endDate): float
    {
        return $this->expenses()
            ->whereBetween('date', [$startDate, $endDate])
            ->whereHas('category', function ($query) {
                $query->where('type', 'expense');
            })
            ->sum('amount');
    }

    /**
     * Get net cash flow for a specific month and year.
     */
    public function getNetCashFlow($month, $year): float
    {
        return $this->getTotalIncomeForMonth($month, $year) - $this->getTotalExpensesForMonth($month, $year);
    }

    /**
     * Get net cash flow for a specific period.
     */
    public function getNetCashFlowForPeriod($startDate, $endDate): float
    {
        return $this->getTotalIncomeForPeriod($startDate, $endDate) - $this->getTotalExpensesForPeriod($startDate, $endDate);
    }

    /**
     * Get total savings across all goals.
     */
    public function getTotalSavings(): float
    {
        // Temporarily return 0 until SavingsGoal model exists
        return 0;
        // return $this->savingsGoals()->sum('current_amount');
    }

    /**
     * Get total budgeted amount for active budgets.
     */
    public function getTotalBudgeted(): float
    {
        return $this->budgets()
            ->where('is_active', true)
            ->sum('amount');
    }

    /**
     * Get dashboard summary data.
     */
    public function getDashboardSummary(): array
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;

        return [
            'monthly_income' => $this->getTotalIncomeForMonth($currentMonth, $currentYear),
            'monthly_expenses' => $this->getTotalExpensesForMonth($currentMonth, $currentYear),
            'net_cash_flow' => $this->getNetCashFlow($currentMonth, $currentYear),
            'total_savings' => $this->getTotalSavings(),
            'total_budgeted' => $this->getTotalBudgeted(),
        ];
    }

    /**
     * Get recent expenses.
     */
    public function getRecentExpenses($limit = 10)
    {
        return $this->expenses()
            ->with('category')
            ->orderBy('date', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recent incomes.
     */
    public function getRecentIncomes($limit = 10)
    {
        return $this->incomes()
            ->with('category')
            ->orderBy('date', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get the current month's income breakdown by source.
     */
    public function getMonthlyIncomeBreakdown($month = null, $year = null)
    {
        $month = $month ?? now()->month;
        $year = $year ?? now()->year;

        return $this->incomes()
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->where('is_active', true)
            ->select('source', DB::raw('SUM(amount) as total'))
            ->groupBy('source')
            ->orderBy('total', 'desc')
            ->get();
    }

    /**
     * Get income sources for dropdown/select.
     */
    public function getIncomeSources(): array
    {
        return $this->incomes()
            ->where('is_active', true)
            ->distinct()
            ->pluck('source')
            ->toArray();
    }

    /**
     * Get currency symbol for the user.
     */
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

    /**
     * Format currency amount.
     */
    public function formatCurrency($amount): string
    {
        return $this->getCurrencySymbol() . number_format($amount, 2);
    }

    /**
     * Get the user's active recurring incomes.
     */
    public function getActiveRecurringIncomes()
    {
        return $this->incomes()
            ->where('is_recurring', true)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('recurring_end_date')
                    ->orWhere('recurring_end_date', '>=', now());
            })
            ->orderBy('date', 'desc')
            ->get();
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
