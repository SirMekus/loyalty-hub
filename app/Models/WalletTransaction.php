<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Currency;
use App\Services\Money;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

final class WalletTransaction extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $visible = [
        'balance', 'balance_formatted', 'currency', 'description', 'balance_formatted_with_currency'
    ];

    protected function casts(): array
    {
        return [
            'currency' => Currency::class,
            'transaction_type' => TransactionType::class
        ];
    }

    public function type(): string
    {
        return 'wallet_transactions';
    }
    
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'wallet_id', 'id');
    }
    
    public function balance(): Attribute
	{
        return Attribute::make(
            get: fn ($value) => Money::toOfficialDenomination($value),
            set: fn ($value) => Money::toBaseDenomination($value),
        );
	}
    public function netBalance(): Attribute
	{
        return Attribute::make(
            get: fn ($value) => Money::toOfficialDenomination($value),
            set: fn ($value) => Money::toBaseDenomination($value),
        );
	}
    public function openingBalance(): Attribute
	{
        return Attribute::make(
            get: fn ($value) => Money::toOfficialDenomination($value),
            set: fn ($value) => Money::toBaseDenomination($value),
        );
	}
    public function closingBalance(): Attribute
	{
        return Attribute::make(
            get: fn ($value) => Money::toOfficialDenomination($value),
            set: fn ($value) => Money::toBaseDenomination($value),
        );
	}
    public function amount(): Attribute
	{
        return Attribute::make(
            get: fn ($value) => Money::toOfficialDenomination($value),
            set: fn ($value) => Money::toBaseDenomination($value),
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
    protected function netBalanceFormatted(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                return number_format($this->balance, 2);
            },
        );
    }
    protected function amountFormatted(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                return number_format($this->amount, 2);
            },
        );
    }
    protected function amountFormattedWithCurrency(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                return $this->currency->symbol().number_format($this->amount, 2);
            },
        );
    }
    protected function balanceFormattedWithCurrency(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                return $this->currency->symbol().number_format($this->balance, 2);
            },
        );
    }

    protected function netBalanceFormattedWithCurrency(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                return $this->currency->symbol().number_format($this->balance, 2);
            },
        );
    }
    protected function commissionFormattedWithCurrency(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                return $this->currency->symbol().number_format($this->commission, 2);
            },
        );
    }
    protected function openingBalanceFormatted(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                return number_format($this->opening_balance, 2);
            },
        );
    }
    protected function closingBalanceFormatted(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                return number_format($this->closing_balance, 2);
            },
        );
    }
    public function fee(): Attribute
    {
        return Attribute::make(
            get: fn($value) => Money::toOfficialDenomination($value),
            set: fn($value) => Money::toBaseDenomination($value),
        );
    }
    public function feeFormatted(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->currency->symbol() . " " . number_format($this->fee ?? 0, 2),
        );
    }

    public function commission(): Attribute
    {
        return Attribute::make(
            get: fn($value) => Money::toOfficialDenomination($value),
            set: fn($value) => Money::toBaseDenomination($value),
        );
    }
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
    
    public function isCredit(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->transaction_type?->name === TransactionType::CREDIT->name
        );
    }
    
    public function scopeUser($query, ?User $user = null)
    {
        $user ??= request()->user();
        return $query->whereHas('wallet', function ($q) use ($user) {
            $q->where('owner_id', $user->id)
                        ->where('owner_type', get_class($user));
        });
    }

    protected  function scopeDebit(Builder $query, string $walletId): void
    {
        $query->whereHas('wallet', function($query) use ($walletId){
            $query->where('uuid', $walletId);
        })
        ->whereIn('transaction_type', [
                TransactionType::DEBIT->name,
                TransactionType::TRANSFER->name,
            ]);
    }

    protected function scopeCredit(Builder $query, string $walletId): void
    {
        $query->whereHas('wallet', function($query) use ($walletId){
            $query->where('uuid', $walletId);
        })
        ->whereIn('transaction_type', [
                TransactionType::CREDIT->name,
            ]);
    }
}
