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
                // dd($this->transactions()->latest()->first());
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
    protected function ownedBy(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                $name = null;
                if ($this->owner instanceof User) {
                    $name = $this->owner->full_name;
                }

                return $name;
            }
        );
    }
   
    public function scopeUser($query, ?User $user = null)
    {
        $user ??= request()->user();
        return $query->where('owner_id', $user?->id)
                        ->where('owner_type', get_class($user));
    }

    public function scopeUsd($query)
    {
        return $query->where('currency', Currency::USD->value);
    }

    public function scopeNgn($query)
    {
        return $query->where('currency', Currency::NGN->value);
    }

    protected function currencySymbol(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                return $this->currency->symbol();
            }
        );
    }

    public function belongsToOwner(Model $model):bool
    {
        return $this->owner_type == $model->getMorphClass() && $this->owner_id == $model->id;
    }

    protected function currencyFormatted(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                return $this->currency->value;
            },
        );
    }

    protected function isActive(): Attribute
    {
        return Attribute::make(
            get: function (): bool {
                return $this->active;
            }
        );
    }

    protected function isUsd(): Attribute
    {
        return Attribute::make(
            get: function (): bool {
                return $this->currency == Currency::USD;
            }
        );
    }

    protected function isNgn(): Attribute
    {
        return Attribute::make(
            get: function (): bool {
                return $this->currency == Currency::NGN;
            }
        );
    }
}
