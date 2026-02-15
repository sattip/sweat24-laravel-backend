<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Payment method columns are already converted to string type by the _mysql migration.
     */
    public function up(): void
    {
        // No-op: the companion _mysql migration already converted payment_method to string
    }

    public function down(): void
    {
        // Nothing to reverse
    }
};
