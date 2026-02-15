<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ClassType;

class ClassTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $classTypes = [
            [
                'name' => 'Ομαδικό Μάθημα',
                'value' => 'group',
                'description' => 'Ομαδικά μαθήματα για πολλούς συμμετέχοντες',
                'color' => '#3b82f6',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Personal Training',
                'value' => 'personal',
                'description' => 'Προσωπική προπόνηση one-on-one',
                'color' => '#10b981',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'EMS Training',
                'value' => 'ems',
                'description' => 'Προπόνηση με ηλεκτρομυϊκή διέγερση',
                'color' => '#f59e0b',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Pilates',
                'value' => 'pilates',
                'description' => 'Μαθήματα Pilates',
                'color' => '#ec4899',
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($classTypes as $classType) {
            ClassType::updateOrCreate(
                ['value' => $classType['value']],
                $classType
            );
        }
    }
}
