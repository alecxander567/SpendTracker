<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Income extends Model
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
        'source',
        'amount',
        'description',
        'date',
        'payment_method',
        'is_recurring',
        'recurring_frequency',
        'recurring_end_date',
        'receipt_image',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'recurring_end_date' => 'date',
        'is_recurring' => 'boolean',
        'is_active' => 'boolean',
        'amount' => 'decimal:2',
    ];

    /**
     * The model's default values for attributes.
     */
    protected $attributes = [
        'is_recurring' => false,
        'is_active' => true,
    ];

    /**
     * Get the user that owns the income.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category that this income belongs to.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Scope a query to only include active incomes.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include inactive incomes.
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope a query to only include incomes for a specific month.
     */
    public function scopeForMonth($query, $month, $year)
    {
        return $query->whereMonth('date', $month)
            ->whereYear('date', $year);
    }

    /**
     * Scope a query to only include incomes for a date range.
     */
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope a query to only include recurring incomes.
     */
    public function scopeRecurring($query)
    {
        return $query->where('is_recurring', true);
    }

    /**
     * Scope a query to only include non-recurring incomes.
     */
    public function scopeNonRecurring($query)
    {
        return $query->where('is_recurring', false);
    }

    /**
     * Scope a query to only include incomes by payment method.
     */
    public function scopePaymentMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    /**
     * Scope a query to only include incomes by source.
     */
    public function scopeSource($query, $source)
    {
        return $query->where('source', 'LIKE', "%{$source}%");
    }

    /**
     * Check if this is a recurring income.
     */
    public function isRecurring(): bool
    {
        return (bool) $this->is_recurring;
    }

    /**
     * Check if this income is active.
     */
    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    /**
     * Check if recurring income has expired.
     */
    public function isExpired(): bool
    {
        if (!$this->is_recurring || !$this->recurring_end_date) {
            return false;
        }
        return $this->recurring_end_date->isPast();
    }

    /**
     * Get the payment method label.
     */
    public function getPaymentMethodLabel(): string
    {
        $labels = [
            'cash' => 'Cash',
            'bank_transfer' => 'Bank Transfer',
            'direct_deposit' => 'Direct Deposit',
            'check' => 'Check',
            'mobile_money' => 'Mobile Money',
            'crypto' => 'Cryptocurrency',
            'other' => 'Other',
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
            'bank_transfer' => 'fa-university',
            'direct_deposit' => 'fa-building-columns',
            'check' => 'fa-money-check',
            'mobile_money' => 'fa-mobile-alt',
            'crypto' => 'fa-coins',
            'other' => 'fa-credit-card',
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
            'biweekly' => 'Bi-weekly',
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
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
     * Get the status label (for recurring income).
     */
    public function getStatusLabel(): string
    {
        if (!$this->is_active) {
            return 'Inactive';
        }

        if ($this->is_recurring && $this->isExpired()) {
            return 'Expired';
        }

        return 'Active';
    }

    /**
     * Get the status badge class.
     */
    public function getStatusBadgeClass(): string
    {
        return match ($this->getStatusLabel()) {
            'Active' => 'success',
            'Inactive' => 'secondary',
            'Expired' => 'warning',
            default => 'info',
        };
    }

    /**
     * Calculate total income for a specific period.
     */
    public static function getTotalForPeriod($userId, $startDate, $endDate): float
    {
        return static::where('user_id', $userId)
            ->where('is_active', true)
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');
    }

    /**
     * Get monthly income breakdown by source.
     */
    public static function getMonthlyBreakdown($userId, $month, $year): array
    {
        return static::where('user_id', $userId)
            ->where('is_active', true)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->select('source', DB::raw('SUM(amount) as total'))
            ->groupBy('source')
            ->orderBy('total', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Boot the model.
     */
    protected static function booted()
    {
        static::creating(function ($income) {
            // If no date is set, use today
            if (!$income->date) {
                $income->date = now()->toDateString();
            }
        });
    }
}
