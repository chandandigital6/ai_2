<?php
namespace App\Http\Controllers;

use App\Models\{Business, Offer};
use Illuminate\Http\Request;

class PublicOfferController extends Controller
{
    public function index(Business $business)
    {
        abort_unless($business->status==='active' && $business->verification_status==='approved',404);
        $today = now()->toDateString();
        $offers = Offer::query()->where('business_id',$business->id)->where('status','active')
            ->where(fn($q)=>$q->whereNull('starts_at')->orWhereDate('starts_at','<=',$today))
            ->where(fn($q)=>$q->whereNull('ends_at')->orWhereDate('ends_at','>=',$today))
            ->with(['offer_products.product','offer_services.service'])->latest('id')->paginate(12);
        return view('public.businesses.offers',compact('business','offers'));
    }
}
