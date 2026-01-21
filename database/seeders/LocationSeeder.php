<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = [
            [
                'name' => 'Studio 1',
                'code' => 'studio-1',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Studio 2',
                'code' => 'studio-2',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Outdoor',
                'code' => 'outdoor',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Κεντρικό Κατάστημα',
                'code' => 'main-store',
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'Υποκατάστημα Β',
                'code' => 'branch-b',
                'is_active' => true,
                'sort_order' => 5,
            ],
        ];

        foreach ($locations as $location) {
            Location::updateOrCreate(
                ['code' => $location['code']],
                $location
            );
        }
    }
}
