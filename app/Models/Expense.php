<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'category_id',
        'amount',
        'description',
        'date',
        'payment_method',
        'receipt_image',
        'is_recurring',
        'recurring_frequency',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'is_recurring' => 'boolean',
        'amount' => 'decimal:2',
    ];

    /**
     * The model's default values for attributes.
     */
    protected $attributes = [
        'is_recurring' => false,
    ];

    /**
     * Get the user that owns the expense.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category that this expense belongs to.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Scope a query to only include expenses for a specific month.
     */
    public function scopeForMonth($query, $month, $year)
    {
        return $query->whereMonth('date', $month)
            ->whereYear('date', $year);
    }

    /**
     * Scope a query to only include expenses for a date range.
     */
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope a query to only include income (category type = income).
     */
    public function scopeIncome($query)
    {
        return $query->whereHas('category', function ($q) {
            $q->where('type', 'income');
        });
    }

    /**
     * Scope a query to only include expenses (category type = expense).
     */
    public function scopeExpense($query)
    {
        return $query->whereHas('category', function ($q) {
            $q->where('type', 'expense');
        });
    }

    /**
     * Scope a query to only include recurring expenses.
     */
    public function scopeRecurring($query)
    {
        return $query->where('is_recurring', true);
    }

    /**
     * Scope a query to only include non-recurring expenses.
     */
    public function scopeNonRecurring($query)
    {
        return $query->where('is_recurring', false);
    }

    /**
     * Scope a query to only include expenses by payment method.
     */
    public function scopePaymentMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    /**
     * Check if this is an income transaction.
     */
    public function isIncome(): bool
    {
        return $this->category && $this->category->type === 'income';
    }

    /**
     * Check if this is an expense transaction.
     */
    public function isExpense(): bool
    {
        return $this->category && $this->category->type === 'expense';
    }

    /**
     * Check if this is a recurring transaction.
     */
    public function isRecurring(): bool
    {
        return (bool) $this->is_recurring;
    }

    /**
     * Get the payment method label.
     */
    public function getPaymentMethodLabel(): string
    {
        $labels = [
            'cash' => 'Cash',
            'credit_card' => 'Credit Card',
            'debit_card' => 'Debit Card',
            'bank_transfer' => 'Bank Transfer',
            'mobile_money' => 'Mobile Money',
        ];
        return $labels[$this->payment_method] ?? ucfirst($this->payment_method);
    }

    /**
     * Get the payment method icon.
     */
    public function getPaymentMethodIcon(): string
    {
        $icons = [
            'cash' => 'fa-money-bill-wave',
            'credit_card' => 'fa-credit-card',
            'debit_card' => 'fa-credit-card',
            'bank_transfer' => 'fa-university',
            'mobile_money' => 'fa-mobile-alt',
        ];
        return $icons[$this->payment_method] ?? 'fa-credit-card';
    }

    /**
     * Get the recurring frequency label.
     */
    public function getRecurringFrequencyLabel(): string
    {
        if (!$this->is_recurring || !$this->recurring_frequency) {
            return 'Not recurring';
        }

        $labels = [
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            'yearly' => 'Yearly',
        ];
        return $labels[$this->recurring_frequency] ?? ucfirst($this->recurring_frequency);
    }

    /**
     * Get the formatted amount with currency symbol.
     */
    public function getFormattedAmount(): string
    {
        return $this->user->formatCurrency($this->amount);
    }

    /**
     * Get the type badge class.
     */
    public function getTypeBadgeClass(): string
    {
        return $this->isIncome() ? 'success' : 'danger';
    }

    /**
     * Get the type label.
     */
    public function getTypeLabel(): string
    {
        return $this->isIncome() ? 'Income' : 'Expense';
    }
}
