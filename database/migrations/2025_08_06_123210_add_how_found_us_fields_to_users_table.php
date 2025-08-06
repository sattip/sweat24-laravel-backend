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
        Schema::table('users', function (Blueprint $table) {
            $table->string('found_us_via')->nullable()->after('password');
            $table->unsignedBigInteger('referrer_id')->nullable()->after('found_us_via');
            $table->string('social_platform')->nullable()->after('referrer_id');
            $table->string('referral_code_or_name')->nullable()->after('social_platform');
            $table->boolean('referral_validated')->default(false)->after('referral_code_or_name');
            $table->timestamp('referral_validated_at')->nullable()->after('referral_validated');
            
            $table->foreign('referrer_id')->references('id')->on('users')->onDelete('set null');
            $table->index('found_us_via');
            $table->index('referrer_id');
            $table->index('referral_validated');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referrer_id']);
            $table->dropIndex(['found_us_via']);
            $table->dropIndex(['referrer_id']);
            $table->dropIndex(['referral_validated']);
            
            $table->dropColumn([
                'found_us_via',
                'referrer_id',
                'social_platform',
                'referral_code_or_name',
                'referral_validated',
                'referral_validated_at'
            ]);
        });
    }
};
