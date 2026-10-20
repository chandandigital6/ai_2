<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class GrowyaDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production') && env('GROWYA_ALLOW_PRODUCTION_DEMO_SEED', 'false') !== 'true') {
            throw new \RuntimeException('Demo seeder is blocked in production.');
        }

        $password = env('GROWYA_DEMO_PASSWORD', 'Demo@2026!');
        $now = now();

        foreach (['super_admin', 'owner', 'user'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $makeUser = function (string $name, string $email, string $role, string $type, int $serial) use ($password, $now) {
            $id = DB::table('users')->updateOrInsert(['email' => $email], [
                'name' => $name, 'password' => Hash::make($password),
                'phone' => '90000' . str_pad((string) $serial, 5, '0', STR_PAD_LEFT),
                'user_type' => $type, 'status' => 'active',
                'email_verified_at' => $now, 'phone_verified_at' => $now,
                'updated_at' => $now,
            ]);
            $user = \App\Models\User::where('email', $email)->firstOrFail();
            $user->syncRoles([$role]);
            return $user;
        };

        $makeUser('Growya Demo Super Admin', 'admin.demo@growya.test', 'super_admin', 'admin', 1);

        $types = [
            ['Royal Demo Jewellers', 'jewellery-gems', 'Jewellery & Gems', 'Lucknow', 'Uttar Pradesh', 26.8467, 80.9462, ['Gold Chain', 'Gold Ring', 'Silver Anklet', 'Gold Earrings', 'Gold Pendant', 'Silver Coin', 'Bridal Necklace', 'Gold Bangle', 'Diamond Ring', 'Silver Bracelet'], ['Jewellery Repair', 'Gold Polishing', 'Custom Jewellery Design', 'Stone Setting', 'Ring Resizing']],
            ['Demo Mobile Hub', 'electronics-mobiles', 'Electronics & Mobiles', 'Kanpur', 'Uttar Pradesh', 26.4499, 80.3319, ['Android Smartphone', 'Bluetooth Speaker', 'Earbuds', 'USB-C Cable', 'Power Bank', 'Smart Watch', 'Phone Cover', 'Fast Charger', 'Tablet', 'Laptop Stand'], ['Mobile Repair', 'Screen Replacement', 'Battery Replacement', 'Software Setup', 'Laptop Service']],
            ['Demo Fashion Studio', 'clothing-fashion', 'Clothing & Fashion', 'Jaipur', 'Rajasthan', 26.9124, 75.7873, ['Cotton Kurti', 'Mens Shirt', 'Formal Trouser', 'Silk Saree', 'Designer Lehenga', 'Kids Dress', 'Denim Jeans', 'Winter Jacket', 'Ethnic Dupatta', 'Cotton T-Shirt'], ['Blouse Stitching', 'Alterations', 'Custom Tailoring', 'Dress Designing', 'Embroidery']],
            ['Demo Oorja Solar Store', 'solar-renewable-energy', 'Solar & Renewable Energy', 'New Delhi', 'Delhi', 28.6139, 77.2090, ['Solar Panel 100W', 'Solar Panel 550W', 'Solar Lantern', 'Solar Home Kit', 'Solar Inverter', 'Lithium Battery', 'Solar Charge Controller', 'Solar Street Light', 'Solar Fan', 'Solar Water Pump'], ['Rooftop Survey', 'Solar Installation', 'System Maintenance', 'Battery Servicing', 'Solar Consultation']],
        ];

        $customers = [];
        for ($i = 1; $i <= 12; $i++) {
            $customers[] = $makeUser('Demo Customer ' . $i, sprintf('customer%02d@growya.test', $i), 'user', 'customer', 100 + $i);
        }

        foreach ($types as $index => $spec) {
            [$title, $slug, $categoryName, $cityName, $stateName, $lat, $lng, $productNames, $serviceNames] = $spec;
            $num = $index + 1;
            $owner = $makeUser('Demo Owner ' . $num, "owner{$num}@growya.test", 'owner', 'owner', 10 + $num);
            $manager = $makeUser('Demo Manager ' . $num, "manager{$num}@growya.test", 'user', 'employee', 20 + $num);
            $staffUsers = [];
            for ($k = 1; $k <= 2; $k++) {
                $staffUsers[] = $makeUser("Demo Staff {$num}-{$k}", "staff{$num}{$k}@growya.test", 'user', 'employee', 30 + $num * 10 + $k);
            }

            $categoryId = DB::table('categories')->where('category_type', 'business')->where('name', $categoryName)->value('id');
            if (!$categoryId) {
                $categoryId = DB::table('categories')->insertGetId(['name'=>$categoryName, 'slug'=>Str::slug($categoryName), 'category_type'=>'business', 'is_active'=>1, 'created_at'=>$now, 'updated_at'=>$now]);
            }
            $stateId = DB::table('states')->where('name', $stateName)->value('id');
            $cityId = $stateId ? DB::table('cities')->where('state_id', $stateId)->where('name', $cityName)->value('id') : null;
            $countryId = DB::table('countries')->where('iso_code', 'IND')->value('id');

            DB::table('businesses')->updateOrInsert(['slug' => 'demo-' . $slug], [
                'owner_id' => $owner->id, 'category_id' => $categoryId,
                'name' => $title, 'description' => 'DEMO DATA — For manager acceptance testing only.',
                'email' => "business{$num}@growya.test", 'phone' => '91111000' . str_pad((string)$num,2,'0',STR_PAD_LEFT),
                'address' => 'Demo Market, ' . $cityName, 'pincode' => '000000',
                'country_id' => $countryId, 'state_id' => $stateId, 'city_id' => $cityId,
                'latitude' => $lat, 'longitude' => $lng,
                'verification_status' => 'approved', 'status' => 'active',
                'cod_enabled' => 1, 'upi_enabled' => 0, 'updated_at' => $now,
            ]);
            $businessId = DB::table('businesses')->where('slug', 'demo-' . $slug)->value('id');
            foreach (array_merge([[$owner,'owner'],[$manager,'manager']], array_map(fn ($u) => [$u,'staff'], $staffUsers)) as [$user,$role]) {
                DB::table('business_users')->updateOrInsert(['business_id'=>$businessId,'user_id'=>$user->id], [
                    'role'=>$role, 'is_active'=>1, 'joined_at'=>$now, 'updated_at'=>$now,
                ]);
            }
            // Grant manager/staff explicit business permissions; global user role alone does not grant access.
            foreach (array_merge([[$manager, ['business.view','products.view','services.view','customers.view','orders.view','analytics.view']]],
                array_map(fn($u) => [$u, ['business.view','products.view','services.view','customers.view']], $staffUsers)) as [$user, $perms]) {
                $memberId = DB::table('business_users')->where('business_id',$businessId)->where('user_id',$user->id)->value('id');
                foreach ($perms as $perm) {
                    $permissionId = DB::table('permissions')->where('name',$perm)->where('guard_name','web')->value('id');
                    if ($permissionId) DB::table('business_user_permissions')->updateOrInsert(['business_user_id'=>$memberId,'permission_id'=>$permissionId],['updated_at'=>$now]);
                }
            }

            $productCategoryId = DB::table('categories')->where('category_type','product')->where('slug','demo-'.$slug.'-products')->value('id');
            if (!$productCategoryId) $productCategoryId = DB::table('categories')->insertGetId([
                'name'=>$categoryName . ' Demo Products', 'slug'=>'demo-'.$slug.'-products', 'category_type'=>'product', 'is_active'=>1, 'created_at'=>$now, 'updated_at'=>$now,
            ]);
            $serviceCategoryId = DB::table('categories')->where('category_type','service')->where('slug','demo-'.$slug.'-services')->value('id');
            if (!$serviceCategoryId) $serviceCategoryId = DB::table('categories')->insertGetId([
                'name'=>$categoryName . ' Demo Services', 'slug'=>'demo-'.$slug.'-services', 'category_type'=>'service', 'is_active'=>1, 'created_at'=>$now, 'updated_at'=>$now,
            ]);
            $ids = [];
            foreach ($productNames as $i => $name) {
                $pSlug = 'demo-' . Str::slug($name);
                DB::table('products')->updateOrInsert(['business_id'=>$businessId,'slug'=>$pSlug], [
                    'category_id'=>$productCategoryId, 'name'=>$name, 'sku'=>sprintf('DEMO-%d-P%02d',$num,$i+1),
                    'description'=>'Demo product for testing. Not for real sale.', 'price'=>round(499 + ($i+1)*725 + $index*250,2),
                    'offer_price'=>null, 'stock_status'=>'in_stock', 'stock_quantity'=>10+$i*3,
                    'status'=>'active', 'updated_at'=>$now,
                ]);
                $ids[] = DB::table('products')->where('business_id',$businessId)->where('slug',$pSlug)->value('id');
            }
            $serviceIds = [];
            foreach ($serviceNames as $i => $name) {
                $sSlug = 'demo-' . Str::slug($name);
                DB::table('services')->updateOrInsert(['business_id'=>$businessId,'slug'=>$sSlug], [
                    'category_id'=>$serviceCategoryId,'name'=>$name, 'description'=>'Demo service for manager testing.',
                    'price'=>499+$i*500, 'price_type'=>'fixed', 'duration_minutes'=>30+$i*15,
                    'status'=>'active', 'updated_at'=>$now,
                ]);
                $serviceIds[] = DB::table('services')->where('business_id',$businessId)->where('slug',$sSlug)->value('id');
            }

            foreach (range(1, 5) as $j) {
                $customer = $customers[($index * 3 + $j - 1) % 12];
                DB::table('growya_crm_contacts')->updateOrInsert(['business_id'=>$businessId,'email'=>$customer->email], [
                    'user_id'=>$customer->id,'name'=>$customer->name,'phone'=>$customer->phone,
                    'status'=>['new','contacted','interested','converted','closed'][$j-1],
                    'source'=>'manual','tags'=>'demo,manager-test',
                    'assigned_to'=>$j % 2 ? $manager->id : $staffUsers[0]->id,
                    'next_follow_up_at'=>now()->addDays($j-3), 'notes'=>'Demo lead for pipeline testing.', 'updated_at'=>$now,
                ]);
            }
            for ($j=1; $j<=2; $j++) {
                $customer = $customers[($index*3+$j-1)%12];
                $product = DB::table('products')->find($ids[$j-1]);
                $orderNumber = sprintf('DEMO-%d-%03d',$num,$j);
                DB::table('orders')->updateOrInsert(['order_number'=>$orderNumber], [
                    'business_id'=>$businessId,'customer_id'=>$customer->id,'customer_name'=>$customer->name,
                    'customer_phone'=>$customer->phone,'subtotal'=>$product->price,'discount_amount'=>0,'tax_amount'=>0,
                    'total_amount'=>$product->price,'payment_method'=>'cod',
                    'payment_status'=>$j===1?'paid':'pending', 'status'=>$j===1?'delivered':'pending',
                    'notes'=>'DEMO ONLY: no real payment was received.', 'updated_at'=>$now,
                ]);
                $orderId = DB::table('orders')->where('order_number',$orderNumber)->value('id');
                DB::table('order_items')->updateOrInsert(['order_id'=>$orderId,'product_id'=>$product->id], [
                    'product_name'=>$product->name,'sku'=>$product->sku,'quantity'=>1,
                    'unit_price'=>$product->price,'total_price'=>$product->price,'updated_at'=>$now,
                ]);
            }
        }

        // Tags, attributes, values, product mappings and demo variants.
        $this->call(GrowyaDemoProductCatalogSeeder::class);
    }
}
