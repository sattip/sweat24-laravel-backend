<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Service;

class ServicesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = [
            [
                'name' => 'SEMI PERSONAL',
                'slug' => 'semi-personal',
                'description' => 'Προσωπική προπόνηση σε μικρότερες ομάδες με εξατομικευμένο πρόγραμμα. Επιτρέπεται 1 δοκιμαστικό ανά χρήστη που δεν έχει ενεργή συνδρομή.',
                'icon' => 'group',
                'trial_price' => 1500, // 15 ευρώ
                'is_active' => true,
                'display_order' => 1,
                'allows_trial' => true,
                'max_trial_per_user' => 1,
            ],
            [
                'name' => 'PERSONAL TRAINING',
                'slug' => 'personal-training',
                'description' => 'Πλήρως εξατομικευμένη προπόνηση με προσωπικό γυμναστή. Επιτρέπεται 1 δοκιμαστικό ανά χρήστη που δεν έχει ενεργή συνδρομή.',
                'icon' => 'person',
                'trial_price' => 2500, // 25 ευρώ
                'is_active' => true,
                'display_order' => 2,
                'allows_trial' => true,
                'max_trial_per_user' => 1,
            ],
            [
                'name' => 'PILATES PERSONAL',
                'slug' => 'pilates-personal',
                'description' => 'Προσωπικά μαθήματα Pilates με εξειδικευμένο εκπαιδευτή. Επιτρέπεται 1 δοκιμαστικό ανά χρήστη που δεν έχει ενεργή συνδρομή.',
                'icon' => 'pilates',
                'trial_price' => 2000, // 20 ευρώ
                'is_active' => true,
                'display_order' => 3,
                'allows_trial' => true,
                'max_trial_per_user' => 1,
            ],
            [
                'name' => 'PILATES GROUP',
                'slug' => 'pilates-group',
                'description' => 'Ομαδικά μαθήματα Pilates σε μικρές ομάδες. Επιτρέπεται 1 δοκιμαστικό ανά χρήστη που δεν έχει ενεργή συνδρομή.',
                'icon' => 'group-pilates',
                'trial_price' => 1200, // 12 ευρώ
                'is_active' => true,
                'display_order' => 4,
                'allows_trial' => true,
                'max_trial_per_user' => 1,
            ],
            [
                'name' => 'EMS TRAINING',
                'slug' => 'ems-training',
                'description' => 'Ηλεκτρομυοδιέγερση με εξατομικευμένα πρωτόκολλα. Επιτρέπεται 1 δοκιμαστικό ανά χρήστη που δεν έχει ενεργή συνδρομή.',
                'icon' => 'ems',
                'trial_price' => 3000, // 30 ευρώ
                'is_active' => true,
                'display_order' => 5,
                'allows_trial' => true,
                'max_trial_per_user' => 1,
            ],
            [
                'name' => 'CARDIO PERSONAL',
                'slug' => 'cardio-personal',
                'description' => 'Προσωπική προπόνηση καρδιοαναπνευστικής αντοχής. Επιτρέπεται 1 δοκιμαστικό ανά χρήστη που δεν έχει ενεργή συνδρομή.',
                'icon' => 'cardio',
                'trial_price' => 1800, // 18 ευρώ
                'is_active' => true,
                'display_order' => 6,
                'allows_trial' => true,
                'max_trial_per_user' => 1,
            ],
        ];

        foreach ($services as $serviceData) {
            Service::create($serviceData);
        }
    }
}
