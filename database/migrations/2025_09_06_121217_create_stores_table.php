<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('color', 7)->default('#3B82F6'); // Hex color code
            $table->timestamps();
        });

        // Seed the stores
        DB::table('stores')->insert([
            ['name' => 'Βάρη', 'address' => null, 'is_active' => true, 'color' => '#10B981', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Λαγονήσι', 'address' => null, 'is_active' => true, 'color' => '#F59E0B', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
