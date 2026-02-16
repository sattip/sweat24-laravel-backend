<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // This was a SQLite-only migration. On MySQL the previous migration already handled
        // the payment_method enum update via ALTER TABLE MODIFY COLUMN.
    }

    public function down(): void
    {
        // Nothing to reverse
    }
};
