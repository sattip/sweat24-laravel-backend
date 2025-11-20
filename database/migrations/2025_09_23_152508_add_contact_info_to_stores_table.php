<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('address');
            $table->text('description')->nullable()->after('phone');
            $table->string('email')->nullable()->after('description');
            
            // Add index for email for faster searches
            $table->index(['email']);
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropIndex(['email']);
            $table->dropColumn(['phone', 'description', 'email']);
        });
    }
};