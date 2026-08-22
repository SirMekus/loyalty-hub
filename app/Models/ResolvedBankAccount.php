<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResolvedBankAccount extends Model
{
    protected $fillable = [
        'account_number',
        'bank_code',
        'account_name',
    ];
}
