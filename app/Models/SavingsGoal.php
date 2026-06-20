<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavingsGoal extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'target_amount',
        'target_date',
        'category',
        'priority',
        'description',
        'is_completed',
        'completed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'target_amount' => 'decimal:2',
        'target_date' => 'date',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    /**
     * The model's default values for attributes.
     */
    protected $attributes = [
        'priority' => 'medium',
        'is_completed' => false,
    ];

    /**
     * Get the user that owns the savings goal.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include active (not completed) goals.
     */
    public function scopeActive($query)
    {
        return $query->where('is_completed', false);
    }

    /**
     * Scope a query to only include completed goals.
     */
    public function scopeCompleted($query)
    {
        return $query->where('is_completed', true);
    }

    /**
     * Scope a query to only include goals by priority.
     */
    public function scopePriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope a query to only include goals by category.
     */
    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Check if the goal can be afforded based on monthly income.
     */
    public function isAffordable($monthlyIncome): bool
    {
        return $monthlyIncome >= $this->target_amount;
    }

    /**
     * Get the amount needed to afford this item.
     */
    public function getAmountNeeded($monthlyIncome): float
    {
        return max(0, $this->target_amount - $monthlyIncome);
    }

    /**
     * Get the status label.
     */
    public function getStatusLabel(): string
    {
        if ($this->is_completed) {
            return 'Completed';
        }
        if ($this->target_date->isPast()) {
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
            'Completed' => 'success',
            'Expired' => 'danger',
            'Active' => 'info',
            default => 'secondary',
        };
    }

    /**
     * Get the priority label.
     */
    public function getPriorityLabel(): string
    {
        $labels = [
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
        ];
        return $labels[$this->priority] ?? ucfirst($this->priority);
    }

    /**
     * Get the priority badge class.
     */
    public function getPriorityBadgeClass(): string
    {
        return match ($this->priority) {
            'high' => 'danger',
            'medium' => 'warning',
            'low' => 'info',
            default => 'secondary',
        };
    }

    /**
     * Get the category label.
     */
    public function getCategoryLabel(): string
    {
        $labels = [
            'emergency' => 'Emergency Fund',
            'vacation' => 'Vacation',
            'education' => 'Education',
            'home' => 'Home',
            'vehicle' => 'Vehicle',
            'retirement' => 'Retirement',
            'other' => 'Other',
        ];
        return $labels[$this->category] ?? ucfirst($this->category);
    }

    /**
     * Get the category icon.
     */
    public function getCategoryIcon(): string
    {
        $icons = [
            'emergency' => 'fa-shield-alt',
            'vacation' => 'fa-umbrella-beach',
            'education' => 'fa-graduation-cap',
            'home' => 'fa-home',
            'vehicle' => 'fa-car',
            'retirement' => 'fa-rocket',
            'other' => 'fa-box',
        ];
        return $icons[$this->category] ?? 'fa-box';
    }

    /**
     * Get formatted target amount.
     */
    public function getFormattedTargetAmount(): string
    {
        return $this->user->formatCurrency($this->target_amount);
    }

    /**
     * Boot the model.
     */
    protected static function booted()
    {
        static::creating(function ($goal) {
            // If no target date is set, default to 3 months from now
            if (!$goal->target_date) {
                $goal->target_date = now()->addMonths(3);
            }
        });
    }
}
