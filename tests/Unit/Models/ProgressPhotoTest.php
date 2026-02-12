<?php

namespace Tests\Unit\Models;

use App\Models\ProgressPhoto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgressPhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_progress_photo(): void
    {
        $photo = ProgressPhoto::factory()->create();

        $this->assertDatabaseHas('progress_photos', [
            'id' => $photo->id,
            'user_id' => $photo->user_id,
        ]);
    }

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $photo = ProgressPhoto::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $photo->user);
        $this->assertEquals($user->id, $photo->user->id);
    }

    public function test_uploaded_at_cast_to_datetime(): void
    {
        $photo = ProgressPhoto::factory()->create();

        $this->assertInstanceOf(\Carbon\Carbon::class, $photo->uploaded_at);
    }

    public function test_image_url_accessor(): void
    {
        $photo = ProgressPhoto::factory()->create(['image_path' => 'progress/test.jpg']);

        $this->assertStringContainsString('storage/progress/test.jpg', $photo->image_url);
    }

    public function test_image_url_returns_null_without_path(): void
    {
        $photo = ProgressPhoto::factory()->make(['image_path' => null]);

        $this->assertNull($photo->image_url);
    }

    public function test_formatted_date_accessor(): void
    {
        $photo = ProgressPhoto::factory()->create(['uploaded_at' => '2026-01-15']);

        $this->assertNotNull($photo->formatted_date);
    }

    public function test_to_api_array(): void
    {
        $photo = ProgressPhoto::factory()->create(['image_path' => 'progress/test.jpg']);

        $array = $photo->toApiArray();

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('imageUrl', $array);
        $this->assertArrayHasKey('date', $array);
        $this->assertArrayHasKey('caption', $array);
    }

    public function test_with_caption_state(): void
    {
        $photo = ProgressPhoto::factory()->withCaption()->create();

        $this->assertNotNull($photo->caption);
    }

    public function test_today_state(): void
    {
        $photo = ProgressPhoto::factory()->today()->create();

        $this->assertEquals(now()->toDateString(), $photo->uploaded_at->toDateString());
    }
}
