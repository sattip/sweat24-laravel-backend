<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LinkEntitiesToServicesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Link existing packages to services based on their type and name
        $packages = \App\Models\Package::all();
        $services = \App\Models\Service::all();

        foreach ($packages as $package) {
            // Skip if package already has a service_id
            if ($package->service_id) {
                continue;
            }

            $serviceId = null;

            // Match packages to services based on name/type
            if (str_contains(strtolower($package->name), 'personal') && str_contains(strtolower($package->name), 'semi')) {
                $serviceId = $services->where('slug', 'semi-personal')->first()?->id;
            } elseif (str_contains(strtolower($package->name), 'personal training') || $package->type === 'Personal') {
                $serviceId = $services->where('slug', 'personal-training')->first()?->id;
            } elseif (str_contains(strtolower($package->name), 'pilates') && str_contains(strtolower($package->name), 'personal')) {
                $serviceId = $services->where('slug', 'pilates-personal')->first()?->id;
            } elseif (str_contains(strtolower($package->name), 'pilates') && str_contains(strtolower($package->name), 'group')) {
                $serviceId = $services->where('slug', 'pilates-group')->first()?->id;
            } elseif (str_contains(strtolower($package->name), 'ems')) {
                $serviceId = $services->where('slug', 'ems-training')->first()?->id;
            } elseif (str_contains(strtolower($package->name), 'cardio')) {
                $serviceId = $services->where('slug', 'cardio-personal')->first()?->id;
            }

            if ($serviceId) {
                $package->update(['service_id' => $serviceId]);
                echo "Linked package '{$package->name}' to service ID {$serviceId}\n";
            } else {
                // Default to PERSONAL TRAINING for unmatched packages
                $defaultServiceId = $services->where('slug', 'personal-training')->first()?->id;
                if ($defaultServiceId) {
                    $package->update(['service_id' => $defaultServiceId]);
                    echo "Linked package '{$package->name}' to default service (PERSONAL TRAINING)\n";
                }
            }
        }

        // Link existing gym classes to services based on their type
        $classes = \App\Models\GymClass::all();

        foreach ($classes as $class) {
            // Skip if class already has a service_id
            if ($class->service_id) {
                continue;
            }

            $serviceId = null;

            // Match classes to services based on type/name
            if (str_contains(strtolower($class->name), 'personal') || str_contains(strtolower($class->name), 'individual')) {
                $serviceId = $services->where('slug', 'personal-training')->first()?->id;
            } elseif (str_contains(strtolower($class->name), 'pilates')) {
                // Default to group pilates for classes
                $serviceId = $services->where('slug', 'pilates-group')->first()?->id;
            } elseif (str_contains(strtolower($class->name), 'ems') || str_contains(strtolower($class->name), 'electro')) {
                $serviceId = $services->where('slug', 'ems-training')->first()?->id;
            } elseif (str_contains(strtolower($class->name), 'cardio')) {
                $serviceId = $services->where('slug', 'cardio-personal')->first()?->id;
            } elseif (str_contains(strtolower($class->name), 'hiit') || str_contains(strtolower($class->name), 'bootcamp')) {
                $serviceId = $services->where('slug', 'semi-personal')->first()?->id;
            } elseif ($class->type === 'group' || str_contains(strtolower($class->name), 'group')) {
                // Default to SEMI PERSONAL for group classes
                $serviceId = $services->where('slug', 'semi-personal')->first()?->id;
            }

            if ($serviceId) {
                $class->update(['service_id' => $serviceId]);
                echo "Linked class '{$class->name}' to service ID {$serviceId}\n";
            } else {
                // Default to PERSONAL TRAINING for unmatched classes
                $defaultServiceId = $services->where('slug', 'personal-training')->first()?->id;
                if ($defaultServiceId) {
                    $class->update(['service_id' => $defaultServiceId]);
                    echo "Linked class '{$class->name}' to default service (PERSONAL TRAINING)\n";
                }
            }
        }

        // Link existing appointment requests to services based on their specialized service
        $appointmentRequests = \App\Models\AppointmentRequest::with('specializedService')->get();

        foreach ($appointmentRequests as $request) {
            // Skip if request already has a service_id
            if ($request->service_id) {
                continue;
            }

            $serviceId = null;

            // Try to match based on specialized service name if available
            if ($request->specializedService) {
                $specializedName = strtolower($request->specializedService->name);

                if (str_contains($specializedName, 'personal') || str_contains($specializedName, 'individual')) {
                    $serviceId = $services->where('slug', 'personal-training')->first()?->id;
                } elseif (str_contains($specializedName, 'pilates') && str_contains($specializedName, 'personal')) {
                    $serviceId = $services->where('slug', 'pilates-personal')->first()?->id;
                } elseif (str_contains($specializedName, 'pilates') && str_contains($specializedName, 'group')) {
                    $serviceId = $services->where('slug', 'pilates-group')->first()?->id;
                } elseif (str_contains($specializedName, 'ems') || str_contains($specializedName, 'electro')) {
                    $serviceId = $services->where('slug', 'ems-training')->first()?->id;
                } elseif (str_contains($specializedName, 'cardio')) {
                    $serviceId = $services->where('slug', 'cardio-personal')->first()?->id;
                } elseif (str_contains($specializedName, 'semi') || str_contains($specializedName, 'group')) {
                    $serviceId = $services->where('slug', 'semi-personal')->first()?->id;
                }
            }

            if ($serviceId) {
                $request->update(['service_id' => $serviceId]);
                echo "Linked appointment request '{$request->client_name}' to service ID {$serviceId}\n";
            } else {
                // Default to PERSONAL TRAINING for unmatched requests
                $defaultServiceId = $services->where('slug', 'personal-training')->first()?->id;
                if ($defaultServiceId) {
                    $request->update(['service_id' => $defaultServiceId]);
                    echo "Linked appointment request '{$request->client_name}' to default service (PERSONAL TRAINING)\n";
                }
            }
        }

        echo "\nEntity linking to services completed!\n";
    }
}
