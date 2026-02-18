<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\OrderService;
use Illuminate\Console\Command;

class MakeOrder extends Command
{
    protected $signature = 'app:make-order {userId} {count=1 : Number of orders to simulate}';

    protected $description = 'Simulate orders for a user, triggering the full order lifecycle';

    public function handle(OrderService $orderService): int
    {
        $user = User::find($this->argument('userId'));

        if (! $user) {
            $this->error("User with ID [{$this->argument('userId')}] not found.");

            return self::FAILURE;
        }

        $count = (int) $this->argument('count');

        if ($count < 1) {
            $this->error('Count must be at least 1.');

            return self::FAILURE;
        }

        $this->info("Simulating {$count} order(s) for user: {$user->name} (ID: {$user->id})");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        for ($i = 0; $i < $count; $i++) {
            $order = $orderService->createOrder($user);
            $this->line('');
            $this->line("  Order #{$order->id} created — amount: ₦".number_format($order->amount / 100, 2));
            $bar->advance();
        }

        $bar->finish();
        $this->line('');
        $this->info('Done. Don\'t forget to run the queue to process the order(s).');

        return self::SUCCESS;
    }
}
