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
        Schema::create('owner_notifications', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['graceful_cancellation', 'package_extension', 'general']);
            $table->string('title');
            $table->text('message');
            $table->string('trainer_name');
            $table->string('customer_name');
            $table->string('booking_id')->nullable();
            $table->string('package_id')->nullable();
            $table->boolean('is_read')->default(false);
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('owner_notifications');
    }
};
