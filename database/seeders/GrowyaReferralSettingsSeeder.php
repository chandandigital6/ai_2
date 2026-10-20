<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class GrowyaReferralSettingsSeeder extends Seeder {public function run():void {
 DB::table('growya_referral_settings')->insertOrIgnore(['id'=>1,'customer_points'=>20,'business_points'=>100,'enabled'=>true,'created_at'=>now(),'updated_at'=>now()]);
}}
