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
        Schema::create('exercise_equipment', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('name_en')->nullable();
            $table->string('icon')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Pivot table for exercises and equipment (many-to-many)
        Schema::create('exercise_equipment_pivot', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exercise_id')->constrained('exercises')->onDelete('cascade');
            $table->foreignId('equipment_id')->constrained('exercise_equipment')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['exercise_id', 'equipment_id']);
        });

        // Insert default equipment
        $equipment = [
            ['name' => 'Μπάρα', 'name_en' => 'Barbell', 'sort_order' => 1],
            ['name' => 'Αλτήρες', 'name_en' => 'Dumbbells', 'sort_order' => 2],
            ['name' => 'Kettlebell', 'name_en' => 'Kettlebell', 'sort_order' => 3],
            ['name' => 'Καλώδιο/Ράγα', 'name_en' => 'Cable', 'sort_order' => 4],
            ['name' => 'Μηχάνημα', 'name_en' => 'Machine', 'sort_order' => 5],
            ['name' => 'Σωματικό Βάρος', 'name_en' => 'Bodyweight', 'sort_order' => 6],
            ['name' => 'Πάγκος', 'name_en' => 'Bench', 'sort_order' => 7],
            ['name' => 'TRX', 'name_en' => 'TRX', 'sort_order' => 8],
            ['name' => 'Λάστιχο Αντίστασης', 'name_en' => 'Resistance Band', 'sort_order' => 9],
            ['name' => 'Μπάλα Ισορροπίας (Bosu)', 'name_en' => 'Bosu Ball', 'sort_order' => 10],
            ['name' => 'Ιατρική Μπάλα', 'name_en' => 'Medicine Ball', 'sort_order' => 11],
            ['name' => 'Άλλος Εξοπλισμός', 'name_en' => 'Other Equipment', 'sort_order' => 99],
        ];

        foreach ($equipment as $item) {
            DB::table('exercise_equipment')->insert(array_merge($item, [
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exercise_equipment_pivot');
        Schema::dropIfExists('exercise_equipment');
    }
};
