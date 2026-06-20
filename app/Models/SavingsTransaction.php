<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavingsTransaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'savings_goal_id',
        'amount',
        'type',
        'source',
        'description',
        'balance_after',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    /**
     * Get the user that owns the transaction.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the savings goal for this transaction.
     */
    public function savingsGoal()
    {
        return $this->belongsTo(SavingsGoal::class);
    }

    /**
     * Get the transaction type label.
     */
    public function getTypeLabel(): string
    {
        $labels = [
            'deposit' => 'Deposit',
            'withdrawal' => 'Withdrawal',
        ];
        return $labels[$this->type] ?? ucfirst($this->type);
    }

    /**
     * Get the transaction type badge class.
     */
    public function getTypeBadgeClass(): string
    {
        return match ($this->type) {
            'deposit' => 'success',
            'withdrawal' => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Get the formatted amount with sign.
     */
    public function getFormattedAmount(): string
    {
        $sign = $this->type === 'deposit' ? '+' : '-';
        return $sign . $this->user->formatCurrency(abs($this->amount));
    }
}
