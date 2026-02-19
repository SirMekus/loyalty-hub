<?php

namespace Tests\Feature;

use App\Enums\Achievements;
use App\Enums\Badges;
use App\Enums\PaymentStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserAchievementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_returns_the_correct_json_structure(): void
    {
        $user = User::factory()->create();

        $this->getJson("/api/users/{$user->id}/achievements")
            ->assertOk()
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email'],
                'unlocked_achievements',
                'next_available_achievements',
                'current_badge',
                'next_badge',
                'remaining_to_unlock_next_badge',
                'total_purchases',
                'wallet_balance',
            ]);
    }

    #[Test]
    public function it_returns_correct_user_identity_fields(): void
    {
        $user = User::factory()->create();

        $this->getJson("/api/users/{$user->id}/achievements")
            ->assertOk()
            ->assertJsonFragment([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ]);
    }

    #[Test]
    public function it_returns_404_for_a_nonexistent_user(): void
    {
        $this->getJson('/api/users/99999/achievements')
            ->assertNotFound();
    }

    #[Test]
    public function it_returns_empty_achievements_and_unranked_badge_for_a_fresh_user(): void
    {
        $user = User::factory()->create();

        $this->getJson("/api/users/{$user->id}/achievements")
            ->assertOk()
            ->assertJsonFragment([
                'unlocked_achievements' => [],
                'current_badge' => Badges::UNRANKED->name,
                'next_badge' => Badges::BRONZE->name,
                'remaining_to_unlock_next_badge' => 1,
                'total_purchases' => 0,
            ]);
    }

    #[Test]
    public function it_lists_all_achievements_as_next_available_for_a_fresh_user(): void
    {
        $user = User::factory()->create();

        $nextAvailable = $this->getJson("/api/users/{$user->id}/achievements")
            ->assertOk()
            ->json('next_available_achievements');

        foreach (Achievements::cases() as $achievement) {
            $this->assertContains(str_replace('_', ' ', $achievement->name), $nextAvailable);
        }
    }

    #[Test]
    public function it_converts_achievement_names_from_underscores_to_spaces(): void
    {
        $user = User::factory()->create();
        $user->achievements()->create(['name' => Achievements::First_Purchase->name]);

        $this->getJson("/api/users/{$user->id}/achievements")
            ->assertOk()
            ->assertJsonFragment([
                'unlocked_achievements' => ['First Purchase'],
            ]);
    }

    #[Test]
    public function it_excludes_unlocked_achievements_from_next_available(): void
    {
        $user = User::factory()->create();
        $user->achievements()->create(['name' => Achievements::First_Purchase->name]);

        $nextAvailable = $this->getJson("/api/users/{$user->id}/achievements")
            ->assertOk()
            ->json('next_available_achievements');

        $this->assertNotContains('First Purchase', $nextAvailable);
        $this->assertContains('Purchase Streak', $nextAvailable);
    }

    #[Test]
    public function it_reflects_the_correct_badge_and_remaining_count_as_achievements_are_earned(): void
    {
        // SILVER requires 2 achievements; user has 1, so 1 more needed.
        $user = User::factory()->create(['current_badge' => Badges::BRONZE]);
        $user->achievements()->create(['name' => Achievements::First_Purchase->name]);

        $this->getJson("/api/users/{$user->id}/achievements")
            ->assertOk()
            ->assertJsonFragment([
                'current_badge' => Badges::BRONZE->name,
                'next_badge' => Badges::SILVER->name,
                'remaining_to_unlock_next_badge' => 1,
            ]);
    }

    #[Test]
    public function it_counts_placed_orders_as_total_purchases(): void
    {
        $user = User::factory()->create();
        $user->orders()->createMany([
            ['amount' => 5000, 'status' => PaymentStatus::COMPLETED],
            ['amount' => 3000, 'status' => PaymentStatus::COMPLETED],
            ['amount' => 7000, 'status' => PaymentStatus::COMPLETED],
        ]);

        $this->getJson("/api/users/{$user->id}/achievements")
            ->assertOk()
            ->assertJsonFragment(['total_purchases' => 3]);
    }

    #[Test]
    public function it_reports_no_next_badge_when_the_user_has_reached_platinum(): void
    {
        $user = User::factory()->create(['current_badge' => Badges::PLATINUM]);

        foreach (Achievements::cases() as $achievement) {
            $user->achievements()->create(['name' => $achievement->name]);
        }

        $this->getJson("/api/users/{$user->id}/achievements")
            ->assertOk()
            ->assertJsonFragment([
                'current_badge' => Badges::PLATINUM->name,
                'next_badge' => "None \u{2013} you've reached the top!",
                'remaining_to_unlock_next_badge' => 0,
                'next_available_achievements' => [],
            ]);
    }
}
