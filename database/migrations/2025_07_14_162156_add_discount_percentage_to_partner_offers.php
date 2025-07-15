<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('partner_offers', function (Blueprint $table) {
            $table->decimal('discount_percentage', 5, 2)->nullable()->after('discount_unit');
            $table->integer('usage_limit')->default(0)->after('usage_limit_per_user');
            $table->integer('used_count')->default(0)->after('current_usage_count');
        });
    }

    public function down()
    {
        Schema::table('partner_offers', function (Blueprint $table) {
            $table->dropColumn(['discount_percentage', 'usage_limit', 'used_count']);
        });
    }
};