<?php

namespace Tests\Unit\Models;

use App\Models\SpecializedService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpecializedServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_specialized_service(): void
    {
        $service = SpecializedService::factory()->create();
        $this->assertDatabaseHas('specialized_services', ['id' => $service->id]);
    }

    public function test_active_state(): void
    {
        $service = SpecializedService::factory()->active()->create();
        $this->assertTrue($service->is_active);
    }

    public function test_inactive_state(): void
    {
        $service = SpecializedService::factory()->inactive()->create();
        $this->assertFalse($service->is_active);
    }

    public function test_auto_generates_slug(): void
    {
        $service = SpecializedService::factory()->create(['name' => 'Personal Training', 'slug' => null]);
        $this->assertEquals('personal-training', $service->slug);
    }

    public function test_has_display_order(): void
    {
        $service = SpecializedService::factory()->create();
        $this->assertNotNull($service->display_order);
    }

    public function test_is_active_cast_to_boolean(): void
    {
        $service = SpecializedService::factory()->create();
        $this->assertIsBool($service->is_active);
    }
}
