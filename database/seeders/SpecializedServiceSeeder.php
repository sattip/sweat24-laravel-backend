<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SpecializedServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = [
            [
                'name' => 'Προσωπική Εκγύμναση',
                'slug' => 'personal-training',
                'description' => 'Ατομικές συνεδρίες εκγύμνασης με πιστοποιημένο προσωπικό προπονητή προσαρμοσμένες στους συγκεκριμένους στόχους σας.',
                'icon' => '💪',
                'display_order' => 1,
            ],
            [
                'name' => 'Εκγύμναση EMS',
                'slug' => 'ems-training',
                'description' => 'Εκγύμναση με Ηλεκτρική Διέγερση Μυών που ενεργοποιεί περισσότερες μυϊκές ίνες σε λιγότερο χρόνο.',
                'icon' => '⚡',
                'display_order' => 2,
            ],
            [
                'name' => 'Pilates Reformer',
                'slug' => 'pilates-reformer',
                'description' => 'Εξειδικευμένες συνεδρίες Pilates χρησιμοποιώντας τη μηχανή reformer για βελτιωμένη δύναμη του κορμού και ευελιξία.',
                'icon' => '🧘‍♀️',
                'display_order' => 3,
            ],
            [
                'name' => 'Προσωπικό Καρδιαγγειακό',
                'slug' => 'cardio-personal',
                'description' => 'Εστιασμένες συνεδρίες καρδιαγγειακής εκγύμνασης σχεδιασμένες για να βελτιώσουν την καρδιαγγειακή σας υγεία και αντοχή.',
                'icon' => '❤️',
                'display_order' => 4,
            ],
        ];

        foreach ($services as $service) {
            \App\Models\SpecializedService::create($service);
        }
    }
}
