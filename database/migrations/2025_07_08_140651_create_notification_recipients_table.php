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
        Schema::create('notification_recipients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('notification_id');
            $table->unsignedBigInteger('user_id');
            $table->json('delivery_channels')->nullable(); // Which channels were used for this recipient
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->enum('delivery_status', ['pending', 'delivered', 'failed'])->default('pending');
            $table->text('failure_reason')->nullable();
            $table->timestamps();
            
            $table->foreign('notification_id')->references('id')->on('notifications')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['notification_id', 'user_id']);
            $table->index(['user_id', 'read_at']);
            $table->index(['notification_id', 'delivery_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_recipients');
    }
};
