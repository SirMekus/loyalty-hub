<?php


namespace App\Models;

use App\Enums\Currency;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Wallet extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'currency' => Currency::class,
            'transaction_type' => TransactionType::class,
        ];
    }
    protected static function booted()
    {
        static::creating(function ($model) {
            $model->setAttribute('currency', $model?->currency ?? Currency::NGN);
        });
    }
    
    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    protected function balance(): Attribute
    {
        return Attribute::make(
            get: function (): int|float {
                return $this->transactions()->latest()->first()?->net_balance;
            }
        );
    }

    protected function latestTransaction(): Attribute
    {
        return Attribute::make(
            get: function (): ?WalletTransaction {
                return $this->transactions()->latest()->first();
            }
        );
    }

    protected function balanceFormatted(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                return number_format($this->balance, 2);
            },
        );
    }

    protected function balanceFormattedWithCurrency(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                return $this->currency->symbol() . ' ' . number_format($this->balance, 2);
            },
        );
    }
    
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }
}
