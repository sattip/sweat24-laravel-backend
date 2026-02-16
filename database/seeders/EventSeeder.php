<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Event;

class EventSeeder extends Seeder
{
    public function run()
    {
        Event::create([
            'name' => 'Καλοκαιρινό Πάρτι στην παραλία',
            'description' => 'Ελάτε να γιορτάσουμε το καλοκαίρι με μουσική, χορό και κοκτέιλ διπλά στη θάλασσα. Μια βραδιά για να γνωριστούμε καλύτερα εκτός γυμναστηρίου!',
            'date' => '2024-08-02',
            'time' => '18:00',
            'location' => 'Beach Bar \'Ammos\'',
            'image_url' => '/placeholder.svg',
            'type' => 'social',
            'details' => [
                'Δωρεάν είσοδος για μέλη του γυμναστηρίου',
                'Έκπτωση 20% σε όλα τα ποτά',
                'Live DJ από τις 20:00',
                'Δώρα και διαγωνισμοί όλο το βράδυ',
                'Ειδικό μενού με υγιεινά snacks'
            ],
            'current_attendees' => 87,
            'is_active' => true,
        ]);

        Event::create([
            'name' => 'Σεμινάριο Διατροφής & Performance',
            'description' => 'Ο διατροφολόγος μας, κ. Νίκος Γεωργίου, θα μας μιλήσει για το πώς η σωστή διατροφή μπορεί να εκτοξεύσει την απόδοσή μας. Θα ακολουθήσει Q&A.',
            'date' => '2024-09-15',
            'time' => '11:00',
            'location' => 'Sweat24 - Αίθουσα Yoga',
            'image_url' => '/placeholder.svg',
            'type' => 'educational',
            'details' => [
                'Διάρκεια: 2 ώρες',
                'Περιλαμβάνεται δωρεάν υλικό',
                'Δωρεάν δείγματα συμπληρωμάτων',
                'Εξατομικευμένες συμβουλές διατροφής',
                'Πιστοποιητικό συμμετοχής'
            ],
            'current_attendees' => 45,
            'is_active' => true,
        ]);
    }
}