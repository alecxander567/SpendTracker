<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Expense;

class Budget extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'amount',
        'period',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'amount' => 'decimal:2',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the expenses linked to this budget.
     */
    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopePeriod($query, $period)
    {
        return $query->where('period', $period);
    }

    /**
     * Get the total spent amount for this budget (sum of linked expenses).
     */
    public function getSpentAmount(): float
    {
        return (float) $this->expenses()->sum('amount');
    }

    /**
     * Get the total spent amount within the budget period (legacy method).
     */
    public function getSpentAmountByPeriod(): float
    {
        return (float) Expense::where('user_id', $this->user_id)
            ->where('category_id', $this->category_id)
            ->whereBetween('date', [$this->start_date, $this->getEffectiveEndDate()])
            ->sum('amount');
    }

    protected function getEffectiveEndDate()
    {
        if ($this->end_date) {
            return $this->end_date;
        }

        return match ($this->period) {
            'weekly'  => $this->start_date->copy()->addWeek()->subDay(),
            'monthly' => $this->start_date->copy()->addMonthNoOverflow()->subDay(),
            'yearly'  => $this->start_date->copy()->addYear()->subDay(),
            default   => now(),
        };
    }

    public function getRemainingAmount(): float
    {
        return $this->amount - $this->getSpentAmount();
    }

    public function getPercentageUsed(): float
    {
        if ($this->amount == 0) {
            return 0;
        }
        return min(($this->getSpentAmount() / $this->amount) * 100, 100);
    }

    public function getStatus(): string
    {
        $percentage = $this->getPercentageUsed();

        if ($percentage >= 100) {
            return 'exceeded';
        }
        if ($percentage >= 80) {
            return 'warning';
        }
        if ($percentage >= 50) {
            return 'moderate';
        }
        return 'good';
    }

    public function getStatusColor(): string
    {
        return match ($this->getStatus()) {
            'exceeded' => 'danger',
            'warning' => 'warning',
            'moderate' => 'info',
            'good' => 'success',
        };
    }

    public function getPeriodLabel(): string
    {
        return ucfirst($this->period);
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    public function isExpired(): bool
    {
        if (!$this->end_date) {
            return false;
        }
        return $this->end_date->isPast();
    }
}
