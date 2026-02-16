<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Μετακίνηση των υπαρχόντων δεδομένων από service_id στο pivot table
        $packages = DB::table('packages')
            ->whereNotNull('service_id')
            ->get();

        foreach ($packages as $package) {
            DB::table('package_service')->insert([
                'package_id' => $package->id,
                'service_id' => $package->service_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
