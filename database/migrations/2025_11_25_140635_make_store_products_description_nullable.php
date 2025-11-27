<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // SQLite doesn't support ALTER COLUMN, so we need to recreate the table
        // First, update any NULL descriptions to empty string to avoid issues
        DB::statement("UPDATE store_products SET description = '' WHERE description IS NULL");

        // Create a new table with the correct schema (description is now nullable)
        DB::statement('CREATE TABLE store_products_new (
            "id" integer primary key autoincrement not null,
            "name" varchar not null,
            "price" numeric not null,
            "description" text,
            "image_url" varchar,
            "category" varchar check ("category" in (\'supplements\', \'apparel\', \'accessories\', \'equipment\')) not null,
            "slug" varchar not null,
            "is_active" tinyint(1) not null default \'1\',
            "stock_quantity" integer not null default \'0\',
            "original_price" numeric,
            "display_order" integer not null default \'0\',
            "created_at" datetime,
            "updated_at" datetime,
            "is_preorder" tinyint(1) not null default \'0\'
        )');

        // Copy data from old table
        DB::statement('INSERT INTO store_products_new SELECT * FROM store_products');

        // Drop old table
        DB::statement('DROP TABLE store_products');

        // Rename new table
        DB::statement('ALTER TABLE store_products_new RENAME TO store_products');

        // Recreate the unique index
        DB::statement('CREATE UNIQUE INDEX "store_products_slug_unique" on "store_products" ("slug")');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse - description can stay nullable
    }
};
