<?php

use App\Models\GymClass;
use App\Models\Instructor;

// Get instructors
$instructors = Instructor::all();

if ($instructors->isEmpty()) {
    echo "No instructors found!\n";
    exit;
}

// Create classes for the next 7 days
$classTemplates = [
    ['name' => 'HIIT Blast', 'type' => 'group', 'time' => '09:00:00', 'duration' => 45, 'max' => 12],
    ['name' => 'Yoga Flow', 'type' => 'group', 'time' => '07:30:00', 'duration' => 60, 'max' => 15],
    ['name' => 'Power Training', 'type' => 'group', 'time' => '18:00:00', 'duration' => 60, 'max' => 10],
    ['name' => 'Morning Yoga', 'type' => 'group', 'time' => '08:00:00', 'duration' => 45, 'max' => 20],
    ['name' => 'Strength Circuit', 'type' => 'group', 'time' => '17:30:00', 'duration' => 50, 'max' => 12],
    ['name' => 'Personal Training', 'type' => 'personal', 'time' => '10:00:00', 'duration' => 60, 'max' => 1],
    ['name' => 'Evening HIIT', 'type' => 'group', 'time' => '19:00:00', 'duration' => 45, 'max' => 15],
    ['name' => 'Core & Abs', 'type' => 'group', 'time' => '12:00:00', 'duration' => 30, 'max' => 20],
];

$createdCount = 0;

for ($day = 0; $day < 7; $day++) {
    $numClasses = rand(2, 4);
    $usedTemplates = [];
    
    for ($i = 0; $i < $numClasses; $i++) {
        do {
            $templateIndex = rand(0, count($classTemplates) - 1);
        } while (in_array($templateIndex, $usedTemplates));
        
        $usedTemplates[] = $templateIndex;
        $template = $classTemplates[$templateIndex];
        
        // Pick a random instructor
        $instructor = $instructors->random();
        
        // Make some classes full for testing waitlist
        $participants = rand(0, $template['max']);
        if (rand(1, 100) <= 30) {
            $participants = $template['max'];
        }
        
        GymClass::create([
            'name' => $template['name'],
            'type' => $template['type'],
            'instructor' => $instructor->name, // This should be instructor_id based on schema
            'instructor_id' => $instructor->id, // Add this if the column exists
            'date' => today()->addDays($day),
            'time' => $template['time'],
            'duration' => $template['duration'],
            'max_participants' => $template['max'],
            'current_participants' => $participants,
            'location' => $template['type'] === 'personal' ? 'Personal Training Area' : 'Main Floor',
            'description' => 'Προπόνηση ' . $template['name'],
            'status' => 'active'
        ]);
        
        $createdCount++;
    }
}

echo "Created {$createdCount} gym classes\n";