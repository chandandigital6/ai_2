<?php
namespace App\Observers;
use App\Models\Business;
use App\Services\GrowyaReferralService;
class GrowyaReferralBusinessObserver {
 public function updated(Business $business):void {
  if($business->wasChanged('verification_status') && $business->verification_status==='approved'){
   app(GrowyaReferralService::class)->businessApproved($business);
  }
 }
}
