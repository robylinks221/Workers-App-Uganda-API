<?php

namespace Database\Seeders;

use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

class ServiceCategorySeeder extends Seeder
{
    /**
     * Seed the application's service categories.
     */
    public function run(): void
    {
        $services = [
            [
                'name' => 'House Cleaning',
                'slug' => 'house-cleaning',
                'icon' => 'cleaning_services',
                'description' => 'General home cleaning, mopping, dusting, bathroom cleaning and room organisation.',
                'active' => true,
            ],
            [
                'name' => 'Laundry',
                'slug' => 'laundry',
                'icon' => 'local_laundry_service',
                'description' => 'Washing, drying, ironing, folding and organising clothes.',
                'active' => true,
            ],
            [
                'name' => 'Babysitting',
                'slug' => 'babysitting',
                'icon' => 'child_care',
                'description' => 'Supervision and care of babies and young children.',
                'active' => true,
            ],
            [
                'name' => 'Cooking',
                'slug' => 'cooking',
                'icon' => 'restaurant',
                'description' => 'Preparing household meals and assisting with kitchen duties.',
                'active' => true,
            ],
            [
                'name' => 'Housekeeping',
                'slug' => 'housekeeping',
                'icon' => 'home',
                'description' => 'Daily household management, cleaning, organisation and general home support.',
                'active' => true,
            ],
            [
                'name' => 'Elderly Care',
                'slug' => 'elderly-care',
                'icon' => 'elderly',
                'description' => 'Non-medical assistance and companionship for elderly people.',
                'active' => true,
            ],
            [
                'name' => 'Gardening',
                'slug' => 'gardening',
                'icon' => 'yard',
                'description' => 'Garden maintenance, compound cleaning, planting and basic outdoor work.',
                'active' => true,
            ],
            [
                'name' => 'Security Guard',
                'slug' => 'security-guard',
                'icon' => 'security',
                'description' => 'Residential or business premises security and access monitoring.',
                'active' => true,
            ],
            [
                'name' => 'Driver',
                'slug' => 'driver',
                'icon' => 'directions_car',
                'description' => 'Personal, family or business driving services.',
                'active' => true,
            ],
            [
                'name' => 'Office Cleaning',
                'slug' => 'office-cleaning',
                'icon' => 'business',
                'description' => 'Cleaning and maintaining offices and commercial workplaces.',
                'active' => true,
            ],
            [
                'name' => 'Hotel Housekeeping',
                'slug' => 'hotel-housekeeping',
                'icon' => 'hotel',
                'description' => 'Guest-room cleaning, linen changing and hotel housekeeping support.',
                'active' => true,
            ],
            [
                'name' => 'Caregiver',
                'slug' => 'caregiver',
                'icon' => 'volunteer_activism',
                'description' => 'Daily support and companionship for people who need personal assistance.',
                'active' => true,
            ],
            [
                'name' => 'Nanny',
                'slug' => 'nanny',
                'icon' => 'family_restroom',
                'description' => 'Regular childcare, school support and household assistance for families.',
                'active' => true,
            ],
            [
                'name' => 'Farm Work',
                'slug' => 'farm-work',
                'icon' => 'agriculture',
                'description' => 'General farm labour, crop maintenance and animal-care support.',
                'active' => true,
            ],
            [
                'name' => 'Pet Care',
                'slug' => 'pet-care',
                'icon' => 'pets',
                'description' => 'Feeding, cleaning, walking and general care of household pets.',
                'active' => true,
            ],
        ];

        foreach ($services as $service) {
            ServiceCategory::query()->updateOrCreate(
                [
                    'slug' => $service['slug'],
                ],
                $service
            );
        }
    }
}
