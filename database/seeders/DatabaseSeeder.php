<?php

namespace Database\Seeders;

use App\Events\PurchaseMade;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory(10)->create();

        $users = User::all();

        foreach ($users as $user) {
            $purchases = rand(1, 20);

            // Simulate purchases (events handled via service directly to avoid double-firing)
            for ($i = $user->orders()->count(); $i < $purchases; $i++) {
                $order = Order::create([
                    'user_id' => $user->id,
                    'amount'  => rand(1000, 50000) / 100,
                    'status'  => 'completed',
                ]);

                event(new PurchaseMade($user, $order));
            }

            $this->command->info("Seeded {$user->name} with {$purchases} purchases.");
        }
    }
}
