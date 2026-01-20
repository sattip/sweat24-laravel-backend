<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\ParentConsent;
use App\Models\Signature;
use App\Notifications\Admin\NewRegistrationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Carbon\Carbon;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function getValidAdultRegistrationData(): array
    {
        return [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password123',
            'birthDate' => Carbon::now()->subYears(25)->format('Y-m-d'),
            'gender' => 'male',
            'phone' => '1234567890',
            'signature' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
            'signedAt' => now()->toISOString(),
            'documentType' => 'terms_and_conditions',
            'documentVersion' => '1.0',
            'medicalHistory' => [
                'liability_declaration_accepted' => true,
                'medical_conditions' => [],
                'current_health_problems' => [],
                'prescribed_medications' => [],
                'smoking' => [],
                'physical_activity' => [],
                'emergency_contact' => [
                    'name' => 'Emergency Contact',
                    'phone' => '9876543210',
                ],
            ],
        ];
    }

    private function getValidMinorRegistrationData(): array
    {
        return [
            'firstName' => 'Jane',
            'lastName' => 'Doe',
            'email' => 'jane.doe@example.com',
            'password' => 'password123',
            'birthDate' => Carbon::now()->subYears(15)->format('Y-m-d'),
            'gender' => 'female',
            'phone' => '1234567890',
            'signature' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
            'signedAt' => now()->toISOString(),
            'documentType' => 'terms_and_conditions',
            'documentVersion' => '1.0',
            'medicalHistory' => [
                'liability_declaration_accepted' => true,
                'medical_conditions' => [],
                'current_health_problems' => [],
                'prescribed_medications' => [],
                'smoking' => [],
                'physical_activity' => [],
                'emergency_contact' => [
                    'name' => 'Parent Name',
                    'phone' => '9876543210',
                ],
            ],
            'parentConsent' => [
                'parentFullName' => 'Parent Doe',
                'fatherFirstName' => 'Father',
                'fatherLastName' => 'Doe',
                'motherFirstName' => 'Mother',
                'motherLastName' => 'Doe',
                'parentBirthDate' => Carbon::now()->subYears(45)->format('Y-m-d'),
                'parentIdNumber' => 'AB123456',
                'parentPhone' => '1112223333',
                'parentLocation' => 'Athens',
                'parentStreet' => 'Main Street',
                'parentStreetNumber' => '123',
                'parentPostalCode' => '12345',
                'parentEmail' => 'parent@example.com',
                'consentAccepted' => true,
                'signature' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
            ],
        ];
    }

    // ========== ADULT REGISTRATION TESTS ==========

    public function test_adult_registration_creates_user_with_pending_status(): void
    {
        Notification::fake();

        $data = $this->getValidAdultRegistrationData();

        $response = $this->postJson('/api/v1/auth/register-with-consent', $data);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'user' => [
                    'status' => 'pending_approval',
                    'registration_status' => 'pending_approval',
                    'is_minor' => false,
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john.doe@example.com',
            'status' => 'pending_approval',
            'registration_status' => 'pending_approval',
            'is_minor' => false,
        ]);
    }

    public function test_adult_registration_stores_medical_history(): void
    {
        Notification::fake();

        $data = $this->getValidAdultRegistrationData();
        $data['medicalHistory']['ems_interest'] = true;

        $response = $this->postJson('/api/v1/auth/register-with-consent', $data);

        $response->assertStatus(201);

        $user = User::where('email', 'john.doe@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals(true, (bool) $user->ems_interest);
        $this->assertEquals(true, (bool) $user->liability_declaration_accepted);
    }

    public function test_adult_registration_creates_signature(): void
    {
        Notification::fake();

        $data = $this->getValidAdultRegistrationData();

        $response = $this->postJson('/api/v1/auth/register-with-consent', $data);

        $response->assertStatus(201);

        $user = User::where('email', 'john.doe@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->signatures()->exists());
        $this->assertEquals('terms_and_conditions', $user->signatures()->first()->document_type);
    }

    public function test_admin_notification_is_sent_on_registration(): void
    {
        Notification::fake();

        // Create an admin user to receive notifications
        $admin = User::factory()->admin()->create();

        $data = $this->getValidAdultRegistrationData();

        $response = $this->postJson('/api/v1/auth/register-with-consent', $data);

        $response->assertStatus(201);

        Notification::assertSentTo(
            $admin,
            NewRegistrationNotification::class
        );
    }

    // ========== MINOR REGISTRATION TESTS ==========

    public function test_minor_registration_requires_parent_consent(): void
    {
        Notification::fake();

        $data = $this->getValidAdultRegistrationData();
        // Set birth date to make user a minor
        $data['birthDate'] = Carbon::now()->subYears(15)->format('Y-m-d');
        // Remove parent consent
        unset($data['parentConsent']);

        $response = $this->postJson('/api/v1/auth/register-with-consent', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['parentConsent']);
    }

    public function test_minor_registration_with_parent_consent_succeeds(): void
    {
        Notification::fake();

        $data = $this->getValidMinorRegistrationData();

        $response = $this->postJson('/api/v1/auth/register-with-consent', $data);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'user' => [
                    'is_minor' => true,
                    'status' => 'pending_approval',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'jane.doe@example.com',
            'is_minor' => true,
        ]);

        // Verify parent consent was created
        $user = User::where('email', 'jane.doe@example.com')->first();
        $this->assertNotNull($user->parentConsent);
        $this->assertEquals('AB123456', $user->parentConsent->parent_id_number);
    }

    public function test_minor_age_is_calculated_correctly(): void
    {
        Notification::fake();

        $data = $this->getValidMinorRegistrationData();
        $data['birthDate'] = Carbon::now()->subYears(16)->subMonths(3)->format('Y-m-d');

        $response = $this->postJson('/api/v1/auth/register-with-consent', $data);

        $response->assertStatus(201);

        $user = User::where('email', 'jane.doe@example.com')->first();
        $this->assertEquals(16, $user->age_at_registration);
    }

    // ========== VALIDATION TESTS ==========

    public function test_duplicate_email_returns_validation_error(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $data = $this->getValidAdultRegistrationData();
        $data['email'] = 'existing@example.com';

        $response = $this->postJson('/api/v1/auth/register-with-consent', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_registration_requires_liability_declaration(): void
    {
        $data = $this->getValidAdultRegistrationData();
        $data['medicalHistory']['liability_declaration_accepted'] = false;

        $response = $this->postJson('/api/v1/auth/register-with-consent', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['medicalHistory.liability_declaration_accepted']);
    }

    public function test_registration_requires_signature(): void
    {
        $data = $this->getValidAdultRegistrationData();
        unset($data['signature']);

        $response = $this->postJson('/api/v1/auth/register-with-consent', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['signature']);
    }

    public function test_registration_requires_valid_email(): void
    {
        $data = $this->getValidAdultRegistrationData();
        $data['email'] = 'not-a-valid-email';

        $response = $this->postJson('/api/v1/auth/register-with-consent', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_registration_requires_minimum_password_length(): void
    {
        $data = $this->getValidAdultRegistrationData();
        $data['password'] = 'short';

        $response = $this->postJson('/api/v1/auth/register-with-consent', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    // ========== AGE VERIFICATION TESTS ==========

    public function test_check_age_endpoint_works_correctly(): void
    {
        $response = $this->postJson('/api/v1/auth/check-age', [
            'birth_date' => Carbon::now()->subYears(25)->format('Y-m-d'),
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'is_minor' => false,
                'age' => 25,
            ]);
    }

    public function test_check_age_identifies_minor(): void
    {
        $response = $this->postJson('/api/v1/auth/check-age', [
            'birth_date' => Carbon::now()->subYears(15)->format('Y-m-d'),
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'is_minor' => true,
                'age' => 15,
            ]);
    }

    public function test_check_age_boundary_at_18(): void
    {
        // Exactly 18 years old - should not be a minor
        $response = $this->postJson('/api/v1/auth/check-age', [
            'birth_date' => Carbon::now()->subYears(18)->format('Y-m-d'),
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'is_minor' => false,
                'age' => 18,
            ]);

        // One day before 18th birthday - should be a minor
        $response = $this->postJson('/api/v1/auth/check-age', [
            'birth_date' => Carbon::now()->subYears(18)->addDay()->format('Y-m-d'),
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'is_minor' => true,
                'age' => 17,
            ]);
    }

    public function test_check_age_requires_birth_date(): void
    {
        $response = $this->postJson('/api/v1/auth/check-age', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['birth_date']);
    }

    public function test_check_age_requires_date_in_past(): void
    {
        $response = $this->postJson('/api/v1/auth/check-age', [
            'birth_date' => Carbon::now()->addDay()->format('Y-m-d'),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['birth_date']);
    }

    // ========== EMERGENCY CONTACT TESTS ==========

    public function test_emergency_contact_is_stored(): void
    {
        Notification::fake();

        $data = $this->getValidAdultRegistrationData();
        $data['medicalHistory']['emergency_contact'] = [
            'name' => 'Emergency Person',
            'phone' => '5551234567',
        ];

        $response = $this->postJson('/api/v1/auth/register-with-consent', $data);

        $response->assertStatus(201);

        $user = User::where('email', 'john.doe@example.com')->first();
        $this->assertEquals('Emergency Person', $user->emergency_contact);
        $this->assertEquals('5551234567', $user->emergency_phone);
    }
}
