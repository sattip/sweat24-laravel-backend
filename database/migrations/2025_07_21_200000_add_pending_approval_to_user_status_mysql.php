<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for MySQL - uses proper Laravel Schema builder
     */
    public function up(): void
    {
        // Change status column to string to support more flexible values
        Schema::table('users', function (Blueprint $table) {
            $table->string('status')->default('pending_approval')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Update any pending_approval users to inactive before reverting
        \DB::table('users')
            ->where('status', 'pending_approval')
            ->update(['status' => 'inactive']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('status')->default('inactive')->change();
        });
    }
};