<?php

namespace Tests\Unit;

use App\Interfaces\MoneyTransfer;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SetRealBankAccountTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_fails_when_the_user_does_not_exist(): void
    {
        $this->artisan('bank:set-real-account', [
            'userId' => 99999,
            'accountNumber' => '0000000000',
            'bankCode' => '057',
        ])->assertExitCode(Command::FAILURE);
    }

    #[Test]
    public function it_fails_when_the_bank_code_is_not_recognized(): void
    {
        $user = User::factory()->create();

        $moneyTransfer = $this->mock(MoneyTransfer::class);
        $moneyTransfer->shouldReceive('listBanks')->once()->andReturn([
            ['bank_name' => 'Zenith Bank', 'bank_code' => '057'],
        ]);
        $moneyTransfer->shouldNotReceive('verifyAccountNumber');

        $this->artisan('bank:set-real-account', [
            'userId' => $user->id,
            'accountNumber' => '0000000000',
            'bankCode' => '999',
        ])->assertExitCode(Command::FAILURE);
    }

    #[Test]
    public function it_fails_and_leaves_the_bank_account_untouched_when_resolution_throws(): void
    {
        $user = User::factory()->create();
        $originalAccountNumber = $user->bank->account_number;

        $moneyTransfer = $this->mock(MoneyTransfer::class);
        $moneyTransfer->shouldReceive('listBanks')->once()->andReturn([
            ['bank_name' => 'Zenith Bank', 'bank_code' => '057'],
        ]);
        $moneyTransfer->shouldReceive('verifyAccountNumber')->once()->andThrow(new \Exception('Cannot resolve account'));

        $this->artisan('bank:set-real-account', [
            'userId' => $user->id,
            'accountNumber' => '1234567890',
            'bankCode' => '057',
        ])->assertExitCode(Command::FAILURE);

        $this->assertEquals($originalAccountNumber, $user->bank()->first()->account_number);
    }

    #[Test]
    public function it_attaches_the_resolved_bank_account_on_success(): void
    {
        $user = User::factory()->create();

        $moneyTransfer = $this->mock(MoneyTransfer::class);
        $moneyTransfer->shouldReceive('listBanks')->once()->andReturn([
            ['bank_name' => 'Zenith Bank', 'bank_code' => '057'],
        ]);
        $moneyTransfer->shouldReceive('verifyAccountNumber')
            ->once()
            ->with(['account_number' => '1234567890', 'bank_code' => '057'])
            ->andReturn(['data' => ['account_name' => 'Jane Doe']]);

        $this->artisan('bank:set-real-account', [
            'userId' => $user->id,
            'accountNumber' => '1234567890',
            'bankCode' => '057',
        ])->assertExitCode(Command::SUCCESS);

        $bank = $user->bank()->first();
        $this->assertEquals('1234567890', $bank->account_number);
        $this->assertEquals('Jane Doe', $bank->account_name);
        $this->assertEquals('Zenith Bank', $bank->bank_name);
        $this->assertEquals('057', $bank->bank_code);
    }
}
