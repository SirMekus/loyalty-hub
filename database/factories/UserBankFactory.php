<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserBank>
 */
class UserBankFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * Paystack resolves the account number against real NUBAN data even with a test
     * secret key, so an arbitrary account_number/bank_code pair will fail transfer
     * recipient creation with "Cannot resolve account". `0000000000` at Zenith Bank
     * (057) is Paystack's documented sandbox account that resolves successfully.
     * Use the bank:set-real-account command to attach a real, resolvable account to
     * a specific user for manual/browser testing.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bank_name' => 'Zenith Bank',
            'bank_code' => '057',
            'account_number' => '0000000000',
            'account_name' => $this->faker->name(),
        ];
    }
}
