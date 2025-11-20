<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Store;

class StoresSeeder extends Seeder
{
    public function run(): void
    {
        $stores = [
            [
                'name' => 'Βάρη',
                'address' => 'Βάρη Αττικής',
                'phone' => '210 1234567',
                'email' => 'vari@sweat24.gr',
                'color' => '#10B981',
                'description' => 'Κύριο κατάστημα Βάρη - Προσφέρουμε πλήρη γκάμα προπονήσεων και υπηρεσιών.',
                'is_active' => true,
            ],
            [
                'name' => 'Λαγονήσι',
                'address' => 'Λαγονήσι Αττικής',
                'phone' => '210 7654321',
                'email' => 'lagonisi@sweat24.gr',
                'color' => '#F59E0B',
                'description' => 'Κατάστημα Λαγονήσι - Σύγχρονες εγκαταστάσεις και έμπειρο προσωπικό.',
                'is_active' => true,
            ],
        ];

        foreach ($stores as $storeData) {
            Store::updateOrCreate(
                ['name' => $storeData['name']],
                $storeData
            );
        }
    }
}