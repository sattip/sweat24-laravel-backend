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
        Schema::create('wellness_thresholds', function (Blueprint $table) {
            $table->id();
            $table->string('metric'); // sleep, hydration, calories
            $table->string('level'); // green, orange, red

            // Threshold values
            $table->decimal('min_value', 8, 2)->nullable(); // minimum value for this level
            $table->decimal('max_value', 8, 2)->nullable(); // maximum value for this level

            $table->string('label_el'); // Greek label for display
            $table->string('label_en')->nullable(); // English label

            $table->text('tooltip_el')->nullable(); // Tooltip message in Greek
            $table->text('tooltip_en')->nullable(); // Tooltip message in English

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['metric', 'level']);
        });

        // Insert default thresholds
        $this->seedDefaultThresholds();
    }

    /**
     * Seed default threshold values.
     */
    private function seedDefaultThresholds(): void
    {
        $now = now();

        $thresholds = [
            // Sleep thresholds (hours)
            [
                'metric' => 'sleep',
                'level' => 'green',
                'min_value' => 6.0,
                'max_value' => null,
                'label_el' => 'Φυσιολογικό',
                'label_en' => 'Normal',
                'tooltip_el' => 'Καλός ύπνος',
                'tooltip_en' => 'Good sleep',
            ],
            [
                'metric' => 'sleep',
                'level' => 'orange',
                'min_value' => 5.0,
                'max_value' => 5.99,
                'label_el' => 'Προειδοποίηση',
                'label_en' => 'Warning',
                'tooltip_el' => 'Μέτριος ύπνος - προτείνουμε ξεκούραση',
                'tooltip_en' => 'Moderate sleep - rest recommended',
            ],
            [
                'metric' => 'sleep',
                'level' => 'red',
                'min_value' => null,
                'max_value' => 4.99,
                'label_el' => 'Σοβαρή έλλειψη ύπνου',
                'label_en' => 'Severe sleep deficit',
                'tooltip_el' => 'Σοβαρή έλλειψη ύπνου - προτείνουμε ελαφρύτερη προπόνηση',
                'tooltip_en' => 'Severe sleep deficit - lighter workout recommended',
            ],

            // Hydration thresholds (liters)
            [
                'metric' => 'hydration',
                'level' => 'green',
                'min_value' => 2.0,
                'max_value' => null,
                'label_el' => 'Φυσιολογικό',
                'label_en' => 'Normal',
                'tooltip_el' => 'Καλή ενυδάτωση',
                'tooltip_en' => 'Good hydration',
            ],
            [
                'metric' => 'hydration',
                'level' => 'orange',
                'min_value' => 1.2,
                'max_value' => 1.99,
                'label_el' => 'Προειδοποίηση',
                'label_en' => 'Warning',
                'tooltip_el' => 'Ελαφρώς χαμηλή ενυδάτωση',
                'tooltip_en' => 'Slightly low hydration',
            ],
            [
                'metric' => 'hydration',
                'level' => 'red',
                'min_value' => null,
                'max_value' => 1.19,
                'label_el' => 'Σοβαρή αφυδάτωση',
                'label_en' => 'Severe dehydration',
                'tooltip_el' => 'Σοβαρή αφυδάτωση - αύξησε την πρόσληψη νερού',
                'tooltip_en' => 'Severe dehydration - increase water intake',
            ],

            // Calories thresholds (% of TDEE)
            [
                'metric' => 'calories',
                'level' => 'green',
                'min_value' => 85,
                'max_value' => null,
                'label_el' => 'Φυσιολογικό',
                'label_en' => 'Normal',
                'tooltip_el' => 'Επαρκής πρόσληψη θερμίδων',
                'tooltip_en' => 'Adequate calorie intake',
            ],
            [
                'metric' => 'calories',
                'level' => 'orange',
                'min_value' => 60,
                'max_value' => 84.99,
                'label_el' => 'Ελαφρώς χαμηλή πρόσληψη',
                'label_en' => 'Slightly low intake',
                'tooltip_el' => 'Ελαφρώς χαμηλή πρόσληψη θερμίδων',
                'tooltip_en' => 'Slightly low calorie intake',
            ],
            [
                'metric' => 'calories',
                'level' => 'red',
                'min_value' => null,
                'max_value' => 59.99,
                'label_el' => 'Πολύ χαμηλή πρόσληψη θερμίδων',
                'label_en' => 'Very low calorie intake',
                'tooltip_el' => 'Πολύ χαμηλή πρόσληψη θερμίδων - προτείνουμε ελαφρύτερη προπόνηση',
                'tooltip_en' => 'Very low calorie intake - lighter workout recommended',
            ],
        ];

        foreach ($thresholds as $threshold) {
            DB::table('wellness_thresholds')->insert(array_merge($threshold, [
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wellness_thresholds');
    }
};
