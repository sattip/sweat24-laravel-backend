<?php

use App\Models\Instructor;

// Delete duplicate trainers
$instructors = Instructor::all();
$seen = [];

foreach ($instructors as $instructor) {
    $key = $instructor->email;
    if (in_array($key, $seen)) {
        echo "Deleting duplicate: {$instructor->name} (ID: {$instructor->id})\n";
        $instructor->delete();
    } else {
        $seen[] = $key;
        
        // Fix specialties if it's a JSON string
        if (is_string($instructor->specialties) && substr($instructor->specialties, 0, 1) === '[') {
            $specialties = json_decode($instructor->specialties, true);
            $instructor->specialties = $specialties;
            $instructor->save();
            echo "Fixed specialties for: {$instructor->name}\n";
        }
    }
}

echo "\nRemaining trainers:\n";
foreach (Instructor::all() as $instructor) {
    echo "- {$instructor->name} (ID: {$instructor->id})\n";
}