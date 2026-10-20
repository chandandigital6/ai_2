<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IndiaLocationSeeder extends Seeder
{
    public function run(): void
    {
        $countryId = DB::table('countries')->updateOrInsert(
            ['iso_code' => 'IND'],
            [
                'name' => 'India',
                'phone_code' => '+91',
                'is_active' => true,
                'updated_at' => now(),
            ]
        );

        $india = DB::table('countries')
            ->where('iso_code', 'IND')
            ->first();

        $states = [
            'Andhra Pradesh',
            'Arunachal Pradesh',
            'Assam',
            'Bihar',
            'Chhattisgarh',
            'Goa',
            'Gujarat',
            'Haryana',
            'Himachal Pradesh',
            'Jharkhand',
            'Karnataka',
            'Kerala',
            'Madhya Pradesh',
            'Maharashtra',
            'Manipur',
            'Meghalaya',
            'Mizoram',
            'Nagaland',
            'Odisha',
            'Punjab',
            'Rajasthan',
            'Sikkim',
            'Tamil Nadu',
            'Telangana',
            'Tripura',
            'Uttar Pradesh',
            'Uttarakhand',
            'West Bengal',
            'Andaman and Nicobar Islands',
            'Chandigarh',
            'Dadra and Nagar Haveli and Daman and Diu',
            'Delhi',
            'Jammu and Kashmir',
            'Ladakh',
            'Lakshadweep',
            'Puducherry',
        ];

        foreach ($states as $state) {
            DB::table('states')->updateOrInsert(
                [
                    'country_id' => $india->id,
                    'name' => $state,
                ],
                [
                    'is_active' => true,
                    'updated_at' => now(),
                ]
            );
        }
    }
}
