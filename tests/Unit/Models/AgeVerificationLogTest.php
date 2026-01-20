<?php

namespace Tests\Unit\Models;

use App\Models\AgeVerificationLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgeVerificationLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_age_verification_log(): void
    {
        $log = AgeVerificationLog::factory()->create();
        $this->assertDatabaseHas('age_verification_logs', ['id' => $log->id]);
    }

    public function test_adult_state(): void
    {
        $log = AgeVerificationLog::factory()->adult()->create();

        $this->assertFalse($log->is_minor);
        $this->assertGreaterThanOrEqual(18, $log->calculated_age);
    }

    public function test_minor_state(): void
    {
        $log = AgeVerificationLog::factory()->minor()->create();

        $this->assertTrue($log->is_minor);
        $this->assertLessThan(18, $log->calculated_age);
    }

    public function test_birth_date_cast(): void
    {
        $log = AgeVerificationLog::factory()->create();

        $this->assertInstanceOf(\Carbon\Carbon::class, $log->birth_date);
    }

    public function test_is_minor_cast_to_boolean(): void
    {
        $log = AgeVerificationLog::factory()->create();

        $this->assertIsBool($log->is_minor);
    }

    public function test_calculated_age_cast_to_integer(): void
    {
        $log = AgeVerificationLog::factory()->create();

        $this->assertIsInt($log->calculated_age);
    }
}
