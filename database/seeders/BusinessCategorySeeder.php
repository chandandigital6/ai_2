<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BusinessCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Jewellery & Gems',
            'Clothing & Fashion',
            'Electronics & Mobiles',
            'Grocery & Supermarket',
            'Restaurants & Food',
            'Hotels & Accommodation',
            'Beauty & Salon',
            'Health & Medical',
            'Education & Coaching',
            'Automobile & Accessories',
            'Real Estate',
            'Home & Furniture',
            'Hardware & Building Materials',
            'Agriculture & Farming',
            'Solar & Renewable Energy',
            'Computers & IT Services',
            'Digital Marketing & Advertising',
            'Printing & Packaging',
            'Travel & Tourism',
            'Fitness & Gym',
            'Sports & Recreation',
            'Books & Stationery',
            'Gifts & Toys',
            'Florist & Decoration',
            'Pet Shops & Services',
            'Financial & Insurance Services',
            'Professional Services',
            'Repair & Maintenance',
            'Manufacturing & Industrial',
            'Wholesale & Distribution',
            'Event Management',
            'Photography & Videography',
            'Logistics & Transportation',
            'Other Businesses',
        ];

        foreach ($categories as $index => $name) {
            DB::table('categories')->updateOrInsert(
                [
                    'category_type' => 'business',
                    'slug' => Str::slug($name),
                ],
                [
                    'name' => $name,
                    'parent_id' => null,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                    'updated_at' => now(),
                ]
            );
        }
    }
}
