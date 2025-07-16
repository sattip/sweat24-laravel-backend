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
        Schema::create('package_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_package_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('action'); // purchased, renewed, upgraded, frozen, unfrozen, expired
            $table->string('previous_status')->nullable();
            $table->string('new_status');
            $table->integer('sessions_before')->nullable();
            $table->integer('sessions_after')->nullable();
            $table->date('expiry_date_before')->nullable();
            $table->date('expiry_date_after')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users');
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
            $table->index(['user_package_id', 'action']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_history');
    }
};