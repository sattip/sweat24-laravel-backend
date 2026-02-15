<?php

namespace Tests\Unit\Models;

use App\Models\PackageHistory;
use App\Models\User;
use App\Models\UserPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_package_history(): void
    {
        $history = PackageHistory::factory()->create();

        $this->assertDatabaseHas('package_history', [
            'id' => $history->id,
            'user_package_id' => $history->user_package_id,
        ]);
    }

    public function test_belongs_to_user_package(): void
    {
        $userPackage = UserPackage::factory()->create();
        $history = PackageHistory::factory()->create(['user_package_id' => $userPackage->id]);

        $this->assertInstanceOf(UserPackage::class, $history->userPackage);
        $this->assertEquals($userPackage->id, $history->userPackage->id);
    }

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $history = PackageHistory::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $history->user);
        $this->assertEquals($user->id, $history->user->id);
    }

    public function test_belongs_to_performed_by(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $history = PackageHistory::factory()->create(['performed_by' => $admin->id]);

        $this->assertInstanceOf(User::class, $history->performedBy);
        $this->assertEquals($admin->id, $history->performedBy->id);
    }

    public function test_date_casts(): void
    {
        $history = PackageHistory::factory()->create([
            'expiry_date_before' => '2026-01-01',
            'expiry_date_after' => '2026-02-01',
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $history->expiry_date_before);
        $this->assertInstanceOf(\Carbon\Carbon::class, $history->expiry_date_after);
    }

    public function test_notes_cast_to_array(): void
    {
        $notes = ['reason' => 'Test reason', 'amount' => 100];
        // Pass array directly - model's cast will handle JSON encoding
        $history = PackageHistory::factory()->create(['notes' => $notes]);

        $this->assertIsArray($history->notes);
        $this->assertEquals('Test reason', $history->notes['reason']);
    }

    public function test_by_action_scope(): void
    {
        PackageHistory::factory()->purchased()->count(2)->create();
        PackageHistory::factory()->frozen()->create();

        $purchased = PackageHistory::byAction('purchased')->get();

        $this->assertCount(2, $purchased);
    }

    public function test_recent_scope(): void
    {
        PackageHistory::factory()->count(2)->create(['created_at' => now()->subDays(10)]);
        PackageHistory::factory()->create(['created_at' => now()->subDays(60)]);

        $recent = PackageHistory::recent(30)->get();

        $this->assertCount(2, $recent);
    }

    public function test_purchased_state(): void
    {
        $history = PackageHistory::factory()->purchased()->create();

        $this->assertEquals('purchased', $history->action);
        $this->assertEquals('active', $history->new_status);
    }

    public function test_renewed_state(): void
    {
        $history = PackageHistory::factory()->renewed()->create();

        $this->assertEquals('renewed', $history->action);
        $this->assertEquals('active', $history->previous_status);
        $this->assertEquals('active', $history->new_status);
    }

    public function test_frozen_state(): void
    {
        $history = PackageHistory::factory()->frozen()->create();

        $this->assertEquals('frozen', $history->action);
        $this->assertEquals('active', $history->previous_status);
        $this->assertEquals('frozen', $history->new_status);
    }

    public function test_unfrozen_state(): void
    {
        $history = PackageHistory::factory()->unfrozen()->create();

        $this->assertEquals('unfrozen', $history->action);
        $this->assertEquals('frozen', $history->previous_status);
        $this->assertEquals('active', $history->new_status);
    }

    public function test_cancelled_state(): void
    {
        $history = PackageHistory::factory()->cancelled()->create();

        $this->assertEquals('cancelled', $history->action);
        $this->assertEquals('active', $history->previous_status);
        $this->assertEquals('cancelled', $history->new_status);
    }

    public function test_expired_state(): void
    {
        $history = PackageHistory::factory()->expired()->create();

        $this->assertEquals('expired', $history->action);
        $this->assertEquals('expired', $history->new_status);
    }

    public function test_uses_correct_table(): void
    {
        $history = new PackageHistory();

        $this->assertEquals('package_history', $history->getTable());
    }
}
