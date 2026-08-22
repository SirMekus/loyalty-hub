<?php

namespace App\Console\Commands;

use App\Interfaces\MoneyTransfer;
use Illuminate\Console\Command;

class ListBanks extends Command
{
    protected $signature = 'bank:list {search? : Filter banks by (partial) name}';

    protected $description = "List the payment gateway's recognized banks and their codes";

    public function handle(MoneyTransfer $paymentProvider): int
    {
        $banks = collect($paymentProvider->listBanks());

        $search = $this->argument('search');

        if ($search) {
            $banks = $banks->filter(fn (array $bank) => stripos($bank['bank_name'], $search) !== false);
        }

        if ($banks->isEmpty()) {
            $this->info('No banks matched.');

            return self::SUCCESS;
        }

        $this->table(
            ['Bank Name', 'Bank Code'],
            $banks->map(fn (array $bank) => [$bank['bank_name'], $bank['bank_code']])->values()->all(),
        );

        return self::SUCCESS;
    }
}
