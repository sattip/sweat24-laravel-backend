<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->decimal('trial_price', 8, 2)->nullable(); // Κόστος δοκιμαστικού ραντεβού
            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(0);
            $table->boolean('allows_trial')->default(true); // Αν επιτρέπει δοκιμαστικό ραντεβού
            $table->integer('max_trial_per_user')->default(1); // Μέγιστος αριθμός δοκιμαστικών ανά χρήστη
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
