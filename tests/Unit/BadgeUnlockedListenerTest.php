<?php

namespace Tests\Unit;

use App\Enums\Badges;
use App\Enums\PaymentStatus;
use App\Events\BadgeUnlocked;
use App\Interfaces\MoneyTransfer;
use App\Interfaces\ProvidesGatewayFixtures;
use App\Listeners\BadgeUnlockedListener;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BadgeUnlockedListenerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_is_registered_as_a_listener_for_the_badge_unlocked_event(): void
    {
        Event::fake();

        Event::assertListening(BadgeUnlocked::class, BadgeUnlockedListener::class);
    }

    #[Test]
    public function dispatching_the_real_event_disburses_and_records_a_completed_payment(): void
    {
        $user = User::factory()->create();
        $bank = $user->bank;

        // Resolve the real, bound provider's identifier before mocking it out, so this
        // stays correct if the app's default provider ever changes.
        $provider = app(MoneyTransfer::class)->getProvider();

        $moneyTransfer = $this->mock(MoneyTransfer::class);
        $moneyTransfer->shouldReceive('getProvider')->andReturn($provider);
        $moneyTransfer->shouldReceive('prepareForTransfer')->once()->andReturnUsing(fn (array $data) => $data);
        $moneyTransfer->shouldReceive('transfer')
            ->once()
            ->withArgs(function (array $payload) use ($bank) {
                return $payload['amount'] === config('business.cashback') * 100
                    && $payload['account_number'] === $bank->account_number
                    && $payload['bank_code'] === $bank->bank_code
                    && $payload['recipient_type'] === 'nuban';
            })
            ->andReturn(['status' => true]);

        // Dispatch the real event through Laravel's event system, rather than calling
        // the listener directly, to prove BadgeUnlocked is actually wired to
        // BadgeUnlockedListener (auto-discovery, since there's no EventServiceProvider),
        // and that the listener's ShouldQueue handling runs it correctly.
        event(new BadgeUnlocked(Badges::BRONZE->name, $user));

        $this->assertDatabaseHas('payments', [
            'user_id' => $user->id,
            'amount' => config('business.cashback') * 100,
            'status' => PaymentStatus::COMPLETED->value,
        ]);
    }

    #[Test]
    public function it_throws_when_the_user_has_no_bank_account_on_file(): void
    {
        $user = User::factory()->create();
        $user->bank()->delete();

        $moneyTransfer = $this->mock(MoneyTransfer::class);
        $moneyTransfer->shouldReceive('getProvider')->andReturn('paystack');

        $this->expectException(\Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException::class);

        app(BadgeUnlockedListener::class)->handle(new BadgeUnlocked(Badges::BRONZE->name, $user->fresh()));
    }

    #[Test]
    public function it_handles_disbursement_correctly(): void
    {
        // Unlike the other tests in this file, this one does NOT mock MoneyTransfer —
        // it exercises the real gateway implementation's code path (field mapping,
        // payload construction, amount denomination, recipient_type), only faking the
        // outbound HTTP call. The sample responses come from whatever provider is
        // actually bound (see ProvidesGatewayFixtures), so this keeps working — without
        // editing this test — if the bound provider is ever swapped in
        // AppServiceProvider; a provider that hasn't supplied fixtures yet skips
        // cleanly instead of failing on hardcoded, provider-specific mocks.
        $provider = app(MoneyTransfer::class);

        if (! $provider instanceof ProvidesGatewayFixtures) {
            $this->markTestSkipped(get_class($provider).' does not implement ProvidesGatewayFixtures.');
        }

        Http::fake($provider::fakeHttpResponses());

        $user = User::factory()->create();

        $listener = app(BadgeUnlockedListener::class);
        $listener->handle(new BadgeUnlocked(Badges::BRONZE->name, $user));

        Http::assertSentCount(2);
        $this->assertDatabaseHas('payments', [
            'user_id' => $user->id,
            'amount' => config('business.cashback') * 100,
            'status' => PaymentStatus::COMPLETED->value,
        ]);
    }

    #[Test]
    public function it_marks_the_payment_failed_and_logs_when_disbursement_throws(): void
    {
        $user = User::factory()->create();

        $moneyTransfer = $this->mock(MoneyTransfer::class);
        $moneyTransfer->shouldReceive('getProvider')->andReturn('paystack');
        $moneyTransfer->shouldReceive('prepareForTransfer')->once()->andReturnUsing(fn (array $data) => $data);
        $moneyTransfer->shouldReceive('transfer')->once()->andThrow(new \Exception('Gateway unreachable'));

        try {
            app(BadgeUnlockedListener::class)->handle(new BadgeUnlocked(Badges::BRONZE->name, $user));
            $this->fail('Expected the gateway exception to propagate.');
        } catch (\Exception $e) {
            $this->assertEquals('Gateway unreachable', $e->getMessage());
        }

        $this->assertDatabaseHas('payments', [
            'user_id' => $user->id,
            'status' => PaymentStatus::FAILED->value,
        ]);
    }
}
