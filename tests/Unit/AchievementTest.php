<?php

namespace Tests\Unit;

use App\Enums\Achievements;
use App\Enums\Badges;
use App\Enums\TransactionType;
use App\Events\AchievementUnlocked;
use App\Events\BadgeUnlocked;
use App\Events\PurchaseMade;
use App\Listeners\AchievementUnlockedListener;
use App\Listeners\BadgeUnlockedListener;
use App\Listeners\PurchaseMadeListener;
use App\Models\Order;
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

    public function runOrderLifeCycle(User $user, int $count=1):void
    {
        $orderService = app(OrderService::class);
        $purchaseListener = app(PurchaseMadeListener::class);
        $achievementListener = app(AchievementUnlockedListener::class);
        for($i=0;$i<$count;$i++){
            $order = $orderService->createOrder($user);

            $purchaseListener->handle(new PurchaseMade($user,$order));

            // We improvise timestamp difference so that our "latest" will really fetch the latest entry
            sleep(1);
            $achievement = $user->achievements()->latest()->first();
            $achievementListener->handle(new AchievementUnlocked($achievement));
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
        $purchaseListener->handle(new PurchaseMade($user,$order));

        
        Event::assertDispatched(AchievementUnlocked::class);

        // Now we want to confirm that the achievement is actually run and saved in the database
        $achievement = $user->achievements()->first();
        $achievementListener = app(AchievementUnlockedListener::class);
        $achievementListener->handle(new AchievementUnlocked($achievement));

        $this->assertDatabaseHas('achievements', [
            'user_id' => $user->id,
            'name'    => Achievements::First_Purchase->name,
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
        $this->assertEquals(Badges::BRONZE,   $this->badgeService->resolveBadge(1));
        $this->assertEquals(Badges::SILVER,   $this->badgeService->resolveBadge(2));
        $this->assertEquals(Badges::GOLD,     $this->badgeService->resolveBadge(3));
        $this->assertEquals(Badges::GOLD,     $this->badgeService->resolveBadge(4)); // no badge at 4, stays GOLD
        $this->assertEquals(Badges::PLATINUM, $this->badgeService->resolveBadge(5));
    }

    #[Test]
    public function it_credits_300_naira_cashback_when_badge_is_unlocked(): void
    {
        $user = User::factory()->create();

        $listener = app(BadgeUnlockedListener::class);
        $listener->handle(new BadgeUnlocked($user));

        $this->assertDatabaseHas('wallet_transactions', [
            'amount'           => config('business.cashback') * 100,
            'transaction_type' => TransactionType::CREDIT->name,
        ]);

        $this->assertTrue($user->wallet->balance == config('business.cashback'));
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
        $listener->handle(new AchievementUnlocked($firstAchievement));

        Event::assertDispatched(BadgeUnlocked::class, fn (BadgeUnlocked $e) => $e->user->is($user));
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
        $this->assertEquals(Badges::GOLD->name, $progress['next_badge']);
    }
}
