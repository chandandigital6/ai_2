<?php
namespace App\Services;
use App\Models\GrowyaPaidPromotion;
use Illuminate\Support\Facades\DB;
class GrowyaPaidPromotionService {
 public function businessIds():array {return GrowyaPaidPromotion::query()->live()->whereHas('business',fn($q)=>$q->where('status','active')->where('verification_status','approved'))->whereHas('package',fn($q)=>$q->where('target_type','business'))->distinct()->pluck('business_id')->map(fn($i)=>(int)$i)->all();}
 public function productIds():array {return GrowyaPaidPromotion::query()->live()->whereNotNull('product_id')->whereHas('business',fn($q)=>$q->where('status','active')->where('verification_status','approved'))->whereHas('product',fn($q)=>$q->where('status','active'))->whereHas('package',fn($q)=>$q->where('target_type','product'))->distinct()->pluck('product_id')->map(fn($i)=>(int)$i)->all();}
 public function countImpressions(array $businessIds=[],array $productIds=[]):void {
   if($businessIds){GrowyaPaidPromotion::query()->live()->whereIn('business_id',$businessIds)->whereHas('package',fn($q)=>$q->where('target_type','business'))->update(['impressions'=>DB::raw('impressions + 1')]);}
   if($productIds){GrowyaPaidPromotion::query()->live()->whereIn('product_id',$productIds)->whereHas('package',fn($q)=>$q->where('target_type','product'))->update(['impressions'=>DB::raw('impressions + 1')]);}
 }
}
