<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\GrowyaCrmContact;
use App\Services\BusinessAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GrowyaCrmAdvancedController extends Controller
{
    private function authorizeView(Request $request, Business $business, GrowyaCrmContact $contact): void
    {
        abort_unless((int) $contact->business_id === (int) $business->id, 404);
        $user = $request->user();
        abort_unless($user && app(BusinessAccessService::class)->can($user, $business, 'customers.view'), 403);
        $admin = $user->hasRole('super_admin', 'web');
        $owner = (int) $business->owner_id === (int) $user->id;
        $assigned = (int) $contact->assigned_to === (int) $user->id && DB::table('business_users')->where('business_id', $business->id)->where('user_id', $user->id)->where('is_active', true)->exists();
        abort_unless($admin || $owner || $assigned || app(BusinessAccessService::class)->can($user, $business, 'customers.assign'), 403);
    }

    public function show(Request $request, Business $business, GrowyaCrmContact $contact)
    {
        $this->authorizeView($request, $business, $contact);
        $linked = (int) $contact->user_id;
        $orders = $linked ? DB::table('orders')->where('business_id', $business->id)->where('customer_id', $linked)->orderByDesc('id')->limit(20)->get(['id', 'order_number', 'total_amount', 'status', 'created_at']) : collect();
        $wish = $linked ? DB::table('wishlist_interests')->where('business_id', $business->id)->where('user_id', $linked)->orderByDesc('id')->limit(20)->get(['id', 'item_type', 'item_id', 'status', 'created_at']) : collect();
        $chat = $linked ? DB::table('growya_chat_threads')->where('business_id', $business->id)->where('customer_id', $linked)->orderByDesc('id')->limit(20)->get(['id', 'context_type', 'last_message_at', 'created_at']) : collect();
        $productEnquiries = $linked ? DB::table('business_enquiries')->where('business_id', $business->id)->where('user_id', $linked)->whereNotNull('product_id')->orderByDesc('id')->limit(20)->get(['id', 'product_id', 'status', 'created_at']) : collect();
        $serviceEnquiries = $linked ? DB::table('service_enquiries')->where('business_id', $business->id)->where('user_id', $linked)->orderByDesc('id')->limit(20)->get(['id', 'service_id', 'status', 'created_at']) : collect();
        $links = DB::table('growya_crm_source_links')->where('business_id', $business->id)->where('contact_id', $contact->id)->orderByDesc('id')->limit(40)->get();
        $activities = DB::table('growya_crm_activities')->where('business_id', $business->id)->where('contact_id', $contact->id)->orderByDesc('id')->limit(40)->get();

        return view('growya-crm-advanced.show', compact('business', 'contact', 'orders', 'wish', 'chat', 'productEnquiries', 'serviceEnquiries', 'links', 'activities'));
    }
}
