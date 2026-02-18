<?php

declare(strict_types=1);

namespace App\Services;

// use App\Enums\TransactionType;

use App\Enums\PaymentStatus;
use App\Events\PurchaseMade;
use App\Models\Order;
use App\Models\User;

class OrderService
{
    public function createOrder(User $user): Order
    {
        $order = Order::create([
            'user_id' => $user->id,
            'amount' => rand(1000, 50000) * 100,
            'status' => PaymentStatus::COMPLETED,
        ]);

        event(new PurchaseMade($user, $order));

        return $order;
    }
}
