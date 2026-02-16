<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('churn_feedback', function (Blueprint $table) {
            $table->unsignedBigInteger('user_package_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // No need to reverse - user_package_id can stay nullable
    }
};
