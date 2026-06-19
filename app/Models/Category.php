<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'type',
        'color',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    protected $attributes = [
        'is_default' => false,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the budgets for this category.
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

    public function scopeExpense($query)
    {
        return $query->where('type', 'expense');
    }

    public function scopeIncome($query)
    {
        return $query->where('type', 'income');
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeUserCreated($query)
    {
        return $query->where('is_default', false);
    }

    public function isExpense(): bool
    {
        return $this->type === 'expense';
    }

    public function isIncome(): bool
    {
        return $this->type === 'income';
    }

    public function isDefault(): bool
    {
        return (bool) $this->is_default;
    }

    public function getTypeLabel(): string
    {
        return ucfirst($this->type);
    }

    public function getTypeBadgeClass(): string
    {
        return $this->isExpense() ? 'danger' : 'success';
    }
}
