<?php

namespace App\Models;

use App\Enums\Currency;
use App\Enums\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Services\Money;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'reference',
        'status',
        'provider',
        'currency',
        'ip_address',
        'description',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'data' => 'array',
            'status' => PaymentStatus::class,
            'currency' => Currency::class,
            'provider' => PaymentGateway::class,
        ];
    }

    public function type(): string
    {
        return $this->table;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order(): HasOne
    {
        return $this->hasOne(Order::class);
    }

    public function amount(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Money::toOfficialDenomination($value),
            set: fn ($value) => Money::toBaseDenomination($value),
        );
    }

    public function amountFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->currency->symbol().number_format($this->amount, 2),
        );
    }
}
