<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create package types based on client app mock data
        $packages = [
            [
                'name' => 'Πακέτο 10 EMS',
                'price' => 450.00,
                'sessions' => 10,
                'duration' => 60, // minutes per session
                'type' => 'EMS',
                'status' => 'active',
                'description' => 'Πακέτο 10 συνεδριών EMS για γρήγορα αποτελέσματα στη μυϊκή ενδυνάμωση και τη διαμόρφωση του σώματος.',
            ],
            [
                'name' => 'Πακέτο 20 Ομαδικών',
                'price' => 200.00,
                'sessions' => 20,
                'duration' => 45,
                'type' => 'Ομαδικά',
                'status' => 'active',
                'description' => 'Πακέτο 20 ομαδικών μαθημάτων για ποικιλία και παρέα στην προπόνησή σας.',
            ],
            [
                'name' => 'Πακέτο 5 Yoga',
                'price' => 75.00,
                'sessions' => 5,
                'duration' => 60,
                'type' => 'Yoga',
                'status' => 'active',
                'description' => 'Πακέτο 5 συνεδριών Yoga για χαλάρωση, ευελιξία και εσωτερική ισορροπία.',
            ],
            [
                'name' => 'Πακέτο 8 Personal Training',
                'price' => 560.00,
                'sessions' => 8,
                'duration' => 60,
                'type' => 'Personal Training',
                'status' => 'active',
                'description' => 'Εξατομικευμένη προπόνηση με πιστοποιημένο προσωπικό προπονητή για μέγιστα αποτελέσματα.',
            ],
            [
                'name' => 'Μηνιαίο Unlimited',
                'price' => 89.00,
                'sessions' => null, // unlimited
                'duration' => 45,
                'type' => 'Unlimited',
                'status' => 'active',
                'description' => 'Απεριόριστη πρόσβαση σε όλα τα ομαδικά μαθήματα για έναν μήνα.',
            ],
            [
                'name' => 'Τριμηνιαίο Unlimited',
                'price' => 240.00,
                'sessions' => null,
                'duration' => 45,
                'type' => 'Unlimited',
                'status' => 'active',
                'description' => 'Απεριόριστη πρόσβαση σε όλα τα ομαδικά μαθήματα για τρεις μήνες με έκπτωση.',
            ],
            [
                'name' => 'Πακέτο 12 CrossFit',
                'price' => 180.00,
                'sessions' => 12,
                'duration' => 60,
                'type' => 'CrossFit',
                'status' => 'active',
                'description' => 'Λειτουργική προπόνηση υψηλής έντασης για δύναμη και αντοχή.',
            ],
            [
                'name' => 'Πακέτο 6 Boxing',
                'price' => 120.00,
                'sessions' => 6,
                'duration' => 50,
                'type' => 'Boxing',
                'status' => 'active',
                'description' => 'Μαθήματα πυγμαχίας για καρδιοαναπνευστική φυσική κατάσταση και αυτοάμυνα.',
            ],
        ];

        foreach ($packages as $packageData) {
            Package::updateOrCreate(
                ['name' => $packageData['name']],
                $packageData
            );
        }

        $this->command->info('Package data created successfully!');
    }
}