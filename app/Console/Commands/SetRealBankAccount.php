<?php

namespace App\Console\Commands;

use App\Interfaces\MoneyTransfer;
use App\Models\ResolvedBankAccount;
use App\Models\User;
use Illuminate\Console\Command;

class SetRealBankAccount extends Command
{
    protected $signature = 'bank:set-real-account {userId} {accountNumber} {bankCode}';

    protected $description = 'Attach a real, gateway-resolvable bank account to a user, for manual/browser cashback testing';

    public function handle(MoneyTransfer $paymentProvider): int
    {
        $user = User::find($this->argument('userId'));

        if (! $user) {
            $this->error("User with ID [{$this->argument('userId')}] not found.");

            return self::FAILURE;
        }

        $accountNumber = $this->argument('accountNumber');
        $bankCode = $this->argument('bankCode');

        $bank = collect($paymentProvider->listBanks())->firstWhere('bank_code', $bankCode);

        if (! $bank) {
            $this->error("Bank code [{$bankCode}] was not found in the gateway's bank list.");

            return self::FAILURE;
        }

        $isCached = ResolvedBankAccount::where('account_number', $accountNumber)
            ->where('bank_code', $bankCode)
            ->exists();

        $this->info(
            $isCached
                ? "Resolving {$accountNumber} at {$bank['bank_name']} from the local cache (no live API call)..."
                : "Resolving {$accountNumber} at {$bank['bank_name']} via the live API..."
        );

        // PaystackRepository::verifyAccountNumber() checks resolved_bank_accounts before
        // ever calling the live API — Paystack caps live account resolutions at 3 in test mode.
        try {
            $resolved = $paymentProvider->verifyAccountNumber([
                'account_number' => $accountNumber,
                'bank_code' => $bankCode,
            ]);
        } catch (\Throwable $e) {
            $this->error("Could not resolve this account: {$e->getMessage()}");

            return self::FAILURE;
        }

        $user->bank()->updateOrCreate([], [
            'account_number' => $accountNumber,
            'account_name' => $resolved['data']['account_name'],
            'bank_name' => $bank['bank_name'],
            'bank_code' => $bankCode,
        ]);

        $this->info("Bank account set for {$user->name}: {$resolved['data']['account_name']} — {$bank['bank_name']} ({$accountNumber})");

        return self::SUCCESS;
    }
}
