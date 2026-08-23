<?php

namespace Tests\Unit;

use App\Enums\Achievements;
use App\Enums\Badges;
use App\Enums\PaymentStatus;
use App\Events\AchievementUnlocked;
use App\Events\BadgeUnlocked;
use App\Events\PurchaseMade;
use App\Interfaces\MoneyTransfer;
use App\Listeners\AchievementUnlockedListener;
use App\Listeners\BadgeUnlockedListener;
use App\Listeners\PurchaseMadeListener;
use App\Models\User;
use App\Services\AchievementService;
use App\Services\BadgeService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AchievementTest extends TestCase
{
    use RefreshDatabase;

    private AchievementService $service;

    private BadgeService $badgeService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AchievementService::class);
        $this->badgeService = app(BadgeService::class);
    }

    public function runOrderLifeCycle(User $user, int $count = 1): void
    {
        $orderService = app(OrderService::class);
        $purchaseListener = app(PurchaseMadeListener::class);
        $achievementListener = app(AchievementUnlockedListener::class);
        for ($i = 0; $i < $count; $i++) {
            $order = $orderService->createOrder($user);

            $purchaseListener->handle(new PurchaseMade($user, $order));

            // We improvise timestamp difference so that our "latest" will really fetch the latest entry
            sleep(1);
            $achievement = $user->achievements()->latest()->first();
            $achievementListener->handle(new AchievementUnlocked($achievement->name, $user));
        }
    }

    #[Test]
    public function it_unlocks_first_purchase_achievement_after_one_order(): void
    {
        Event::fake();

        $user = User::factory()->create();

        $orderService = app(OrderService::class);
        $order = $orderService->createOrder($user);

        Event::assertDispatched(PurchaseMade::class);

        // Now we run the listener to confirm the content runs correctly
        $purchaseListener = app(PurchaseMadeListener::class);
        $purchaseListener->handle(new PurchaseMade($user, $order));

        Event::assertDispatched(AchievementUnlocked::class);

        // Now we want to confirm that the achievement is actually run and saved in the database
        $achievement = $user->achievements()->first();
        $achievementListener = app(AchievementUnlockedListener::class);
        $achievementListener->handle(new AchievementUnlocked($achievement->name, $user));

        $this->assertDatabaseHas('achievements', [
            'user_id' => $user->id,
            'name' => Achievements::First_Purchase->name,
        ]);
    }

    #[Test]
    public function it_does_not_unlock_same_achievement_twice(): void
    {
        Event::fake();

        $user = User::factory()->create();

        // First purchase
        $this->runOrderLifeCycle($user, 1);

        // Second purchase – should NOT re-fire First Purchase
        $this->runOrderLifeCycle($user, 1);

        Event::assertDispatchedTimes(AchievementUnlocked::class, 1);
    }

    #[Test]
    public function it_unlocks_bronze_badge_after_one_achievement(): void
    {
        Event::fake();

        $user = User::factory()->create();

        // One order triggers First_Purchase (the 1st achievement), crossing the BRONZE threshold.
        $this->runOrderLifeCycle($user, 1);

        $this->assertTrue($user->fresh()->current_badge == Badges::BRONZE);
    }

    #[Test]
    public function it_returns_correct_next_available_achievements(): void
    {
        Event::fake();
        $user = User::factory()->create();
        $this->runOrderLifeCycle($user, 1);

        $next = $this->service->getNextAchievements($user);

        $this->assertContains(Achievements::Purchase_Streak->name, $next);
        $this->assertNotContains(Achievements::First_Purchase->name, $next);
    }

    #[Test]
    public function it_resolves_correct_badge_for_achievement_count(): void
    {
        $this->assertEquals(Badges::UNRANKED, $this->badgeService->resolveBadge(0));
        $this->assertEquals(Badges::BRONZE, $this->badgeService->resolveBadge(1));
        $this->assertEquals(Badges::SILVER, $this->badgeService->resolveBadge(2));
        $this->assertEquals(Badges::GOLD, $this->badgeService->resolveBadge(3));
        $this->assertEquals(Badges::GOLD, $this->badgeService->resolveBadge(4)); // no badge at 4, stays GOLD
        $this->assertEquals(Badges::PLATINUM, $this->badgeService->resolveBadge(5));
    }

    #[Test]
    public function it_disburses_and_records_a_completed_payment_when_badge_is_unlocked(): void
    {
        $user = User::factory()->create();

        // Stub the external gateway so no real network call is made, while letting
        // PaymentService's own start/disburse/complete logic run for real.
        $moneyTransfer = $this->mock(MoneyTransfer::class);
        $moneyTransfer->shouldReceive('getProvider')->andReturn('paystack');
        $moneyTransfer->shouldReceive('prepareForTransfer')->once()->andReturnUsing(fn (array $data) => $data);
        $moneyTransfer->shouldReceive('transfer')->once()->andReturn(['status' => true]);

        $listener = app(BadgeUnlockedListener::class);
        $listener->handle(new BadgeUnlocked(Badges::BRONZE->name, $user));

        // The payments table (completed payments only) is the source of truth for what's
        // been disbursed to the user so far.
        $this->assertDatabaseHas('payments', [
            'user_id' => $user->id,
            'amount' => config('business.cashback') * 100,
            'status' => PaymentStatus::COMPLETED->value,
        ]);

        $this->assertEquals(config('business.cashback') * 100, $user->total_disbursed);
    }

    #[Test]
    public function it_actually_sends_the_disbursement_to_the_payment_provider_with_correct_details(): void
    {
        $user = User::factory()->create();
        $bank = $user->bank;

        // Resolve the real, bound provider's identifier before we mock it out, so this
        // stays correct if the app's default provider ever changes.
        $provider = app(MoneyTransfer::class)->getProvider();

        // Assert on the payload the provider is actually asked to transfer, so this fails
        // if the transfer is skipped, or if the wrong amount/recipient reaches the gateway.
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

        $listener = app(BadgeUnlockedListener::class);
        $listener->handle(new BadgeUnlocked(Badges::BRONZE->name, $user));

        // If disbursement never reached the provider, the mock expectations above
        // will have failed the test already; this is the corresponding DB-side check.
        $this->assertDatabaseHas('payments', [
            'user_id' => $user->id,
            'status' => PaymentStatus::COMPLETED->value,
        ]);
    }

    #[Test]
    public function it_fires_badge_unlocked_event_when_user_reaches_new_badge_threshold(): void
    {
        // Only intercept BadgeUnlocked; let everything else run normally.
        Event::fake([BadgeUnlocked::class]);

        $user = User::factory()->create();

        // No prior achievements – BRONZE requires exactly 1.
        // The 1st achievement pushes the count to 1, crossing the BRONZE threshold.
        $firstAchievement = $user->achievements()->create([
            'name' => Achievements::First_Purchase->name,
        ]);

        $listener = app(AchievementUnlockedListener::class);
        $listener->handle(new AchievementUnlocked($firstAchievement->name, $user));

        Event::assertDispatched(BadgeUnlocked::class, fn (BadgeUnlocked $e) => $e->user->is($user));
    }

    #[Test]
    public function it_awards_every_skipped_badge_tier_when_achievements_unlock_in_a_burst(): void
    {
        // Achievements can unlock in a burst: e.g. several orders get created before
        // the queue catches up, so by the time a single AchievementUnlocked job runs,
        // the achievement count may already qualify for several badge tiers at once
        // (not just the very next one). All of them must still be credited — jumping
        // straight to the highest tier would silently skip the ones in between.
        Event::fake([BadgeUnlocked::class]);

        $user = User::factory()->create();

        // Unlock all 5 achievements up front, simulating the queue lagging behind a
        // burst of orders, then process only the FIRST resulting AchievementUnlocked
        // event — the worst case, where a single listener run must catch everything.
        foreach (Achievements::cases() as $achievement) {
            $user->achievements()->create(['name' => $achievement->name]);
        }

        $firstAchievement = $user->achievements()->first();
        $listener = app(AchievementUnlockedListener::class);
        $listener->handle(new AchievementUnlocked($firstAchievement->name, $user));

        $badgesAwarded = [];
        Event::assertDispatched(BadgeUnlocked::class, function (BadgeUnlocked $e) use (&$badgesAwarded) {
            $badgesAwarded[] = $e->earnedBadge;

            return true;
        });

        $this->assertEquals(
            [Badges::BRONZE->name, Badges::SILVER->name, Badges::GOLD->name, Badges::PLATINUM->name],
            $badgesAwarded,
        );
        $this->assertEquals(Badges::PLATINUM, $user->fresh()->current_badge);
    }

    #[Test]
    public function it_calculates_remaining_achievements_for_next_badge(): void
    {
        Event::fake();
        $user = User::factory()->create();
        $this->runOrderLifeCycle($user, 5);

        $progress = $this->badgeService->getBadgeProgress($user->fresh());

        // After 5 purchases: unlocked "First Purchase" + "Purchase Streak" = 2 achievements
        // User is now SILVER (requires 2). GOLD requires 3, so remaining = 1.
        $this->assertEquals(1, $progress['remaining']);
        $this->assertEquals(ucfirst(strtolower(Badges::GOLD->name)), $progress['next_badge']);
    }
}
