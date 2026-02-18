<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\OrderService;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $orderService = app(OrderService::class);

        foreach ($users as $user) {
            $purchases = rand(1, 7);

            // Simulate purchases (events handled via service directly to avoid double-firing)
            for ($i = $user->orders()->count(); $i < $purchases; $i++) {
                $orderService->createOrder($user);
            }

            $this->command->info("Seeded {$user->name} with {$purchases} purchases.");
        }
    }
}
