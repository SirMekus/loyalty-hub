<?php

namespace App\Models;

use App\Enums\Badges;
use App\Enums\Currency;
use App\Enums\PaymentStatus;
use App\Services\Money;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'current_badge',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'current_badge' => Badges::class,
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected static function booted()
    {
        static::created(function ($user) {
            app(WalletService::class)->createEmptyWallet($user);
            UserBank::factory()->for($user)->create();
        });
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function achievements(): HasMany
    {
        return $this->hasMany(Achievement::class);
    }

    public function wallet(): MorphOne
    {
        return $this->morphOne(Wallet::class, 'owner');
    }

    public function bank(): HasOne
    {
        return $this->hasOne(UserBank::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Total amount actually disbursed to this user so far, in kobo.
     * The payments table (completed payments only) is the source of truth for this.
     */
    protected function totalDisbursed(): Attribute
    {
        return Attribute::make(
            get: fn (): int => (int) $this->payments()
                ->where('status', PaymentStatus::COMPLETED)
                ->sum('amount'),
        );
    }

    protected function totalDisbursedFormatted(): Attribute
    {
        return Attribute::make(
            get: fn (): string => Currency::NGN->symbol().' '.number_format(Money::toOfficialDenomination($this->total_disbursed), 2),
        );
    }
}
