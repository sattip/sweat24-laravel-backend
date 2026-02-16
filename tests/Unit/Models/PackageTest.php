<?php

namespace Tests\Unit\Models;

use App\Models\Package;
use App\Models\UserPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageTest extends TestCase
{
    use RefreshDatabase;

    public function test_package_has_fillable_attributes(): void
    {
        $package = new Package();
        $fillable = $package->getFillable();

        $this->assertContains('name', $fillable);
        $this->assertContains('price', $fillable);
        $this->assertContains('sessions', $fillable);
        $this->assertContains('duration', $fillable);
        $this->assertContains('status', $fillable);
    }

    public function test_package_casts_correctly(): void
    {
        $package = new Package();
        $casts = $package->getCasts();

        $this->assertEquals('decimal:2', $casts['price']);
        $this->assertEquals('boolean', $casts['time_restriction_enabled']);
        $this->assertEquals('array', $casts['class_types']);
    }

    public function test_package_has_user_packages_relationship(): void
    {
        $package = Package::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $package->userPackages());
    }

    public function test_package_has_services_relationship(): void
    {
        $package = Package::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $package->services());
    }

    public function test_package_can_be_created_with_factory(): void
    {
        $package = Package::factory()->create();

        $this->assertInstanceOf(Package::class, $package);
        $this->assertDatabaseHas('packages', ['id' => $package->id]);
    }

    public function test_package_price_is_cast_to_decimal(): void
    {
        $package = Package::factory()->create(['price' => 99.99]);

        $this->assertIsString($package->price);
        $this->assertEquals('99.99', $package->price);
    }

    public function test_package_class_types_is_cast_to_array(): void
    {
        $package = Package::factory()->create(['class_types' => ['yoga', 'pilates']]);

        $this->assertIsArray($package->class_types);
        $this->assertContains('yoga', $package->class_types);
    }
}
