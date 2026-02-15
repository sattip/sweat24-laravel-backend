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
        Schema::create('parent_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('parent_full_name');
            $table->string('father_first_name', 100);
            $table->string('father_last_name', 100);
            $table->string('mother_first_name', 100);
            $table->string('mother_last_name', 100);
            $table->date('parent_birth_date');
            $table->string('parent_id_number', 20)->unique();
            $table->string('parent_phone', 20);
            $table->string('parent_location', 100);
            $table->string('parent_street');
            $table->string('parent_street_number', 20);
            $table->string('parent_postal_code', 10);
            $table->string('parent_email');
            $table->boolean('consent_accepted')->default(true);
            $table->longText('signature');
            $table->text('consent_text');
            $table->string('consent_version', 10)->default('1.0');
            $table->timestamp('server_timestamp')->useCurrent();
            $table->timestamps();
            
            $table->index('parent_id_number');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parent_consents');
    }
};