<?php

namespace Tests\Unit;

use App\Models\ResolvedBankAccount;
use App\Repositories\PaystackRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaystackRepositoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_caches_a_resolved_account_and_does_not_re_verify_it_against_the_api(): void
    {
        Http::fake([
            'api.paystack.co/bank/resolve*' => Http::response([
                'status' => true,
                'data' => ['account_number' => '0000000000', 'account_name' => 'Test'],
            ]),
        ]);

        $repository = app(PaystackRepository::class);

        // First call: nothing cached yet, so it must hit the API and persist the result.
        $first = $repository->verifyAccountNumber(['account_number' => '0000000000', 'bank_code' => '057']);

        Http::assertSentCount(1);
        $this->assertEquals('Test', $first['data']['account_name']);
        $this->assertDatabaseHas('resolved_bank_accounts', [
            'account_number' => '0000000000',
            'bank_code' => '057',
            'account_name' => 'Test',
        ]);

        // Second call for the same account/bank pair: Paystack caps live verifications
        // at 3, so this must be served from the cache without another API request.
        $second = $repository->verifyAccountNumber(['account_number' => '0000000000', 'bank_code' => '057']);

        Http::assertSentCount(1);
        $this->assertEquals('Test', $second['data']['account_name']);
        $this->assertEquals(1, ResolvedBankAccount::count());
    }

    #[Test]
    public function it_verifies_different_account_bank_pairs_independently(): void
    {
        Http::fake([
            'api.paystack.co/bank/resolve*' => Http::response([
                'status' => true,
                'data' => ['account_number' => '1111111111', 'account_name' => 'Someone Else'],
            ]),
        ]);

        ResolvedBankAccount::create([
            'account_number' => '0000000000',
            'bank_code' => '057',
            'account_name' => 'Test',
        ]);

        $repository = app(PaystackRepository::class);

        // Same account number, different bank code — must not be served from the cached
        // row above, since a NUBAN account number only resolves within its own bank.
        $repository->verifyAccountNumber(['account_number' => '0000000000', 'bank_code' => '044']);

        Http::assertSentCount(1);
        $this->assertEquals(2, ResolvedBankAccount::count());
    }
}
