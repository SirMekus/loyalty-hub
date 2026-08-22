<?php

namespace Database\Seeders;

use App\Models\ResolvedBankAccount;
use Illuminate\Database\Seeder;

class ResolvedBankAccountSeeder extends Seeder
{
    /**
     * Add your own known-good Paystack account_number/bank_code/account_name entries
     * here to pre-populate the local resolution cache. This is opt-in and never called
     * automatically by DatabaseSeeder — run it explicitly:
     *
     *   php artisan db:seed --class=ResolvedBankAccountSeeder
     *
     * Nothing here is verified against Paystack: getting the account number and bank
     * code right (use `php artisan bank:list` to find valid bank codes) is on you.
     * Once seeded, verifyAccountNumber() will resolve these locally forever, without
     * touching Paystack's 3-call live verification limit on test mode.
     *
     * @var array<int, array{account_number: string, bank_code: string, account_name: string}>
     */
    protected array $accounts = [
        ['account_number' => '0000000000', 'bank_code' => '057', 'account_name' => 'Test'],
    ];

    public function run(): void
    {
        foreach ($this->accounts as $account) {
            ResolvedBankAccount::updateOrCreate(
                ['account_number' => $account['account_number'], 'bank_code' => $account['bank_code']],
                ['account_name' => $account['account_name']],
            );
        }

        $this->command->info(count($this->accounts).' resolved bank account(s) seeded.');
    }
}
