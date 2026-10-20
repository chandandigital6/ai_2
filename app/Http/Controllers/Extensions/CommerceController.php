<?php

namespace App\Http\Controllers\Extensions;

use App\Models\Business;
use App\Services\Extensions\CommerceService;
use App\Services\Extensions\RazorpayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommerceController extends ExtensionController
{
    public function show(Request $r, Business $business, CommerceService $service)
    {
        $this->business($r, $business, 'commerce.view');

        return response()->json(['data' => $service->policy($business->id)]);
    }

    public function save(Request $r, Business $business, CommerceService $service)
    {
        $this->business($r, $business, 'commerce.manage');
        if (! $r->is('api/*')) {
            $r->validate(['shipping_rupees' => ['required', 'decimal:0,2', 'min:0', 'max:100000'], 'free_above_rupees' => ['nullable', 'decimal:0,2', 'min:0', 'max:10000000'], 'tax_percent' => ['required', 'decimal:0,2', 'min:0', 'max:40']]);
            $r->merge(['shipping_paise' => RazorpayService::paise((string) $r->input('shipping_rupees')), 'free_above_paise' => $r->filled('free_above_rupees') ? RazorpayService::paise((string) $r->input('free_above_rupees')) : null, 'tax_basis_points' => RazorpayService::paise((string) $r->input('tax_percent'))]);
        }
        $d = $r->validate(['shipping_enabled' => ['required', 'boolean'], 'shipping_paise' => ['required', 'integer', 'min:0', 'max:10000000'], 'free_above_paise' => ['nullable', 'integer', 'min:0', 'max:1000000000'], 'tax_enabled' => ['required', 'boolean'], 'tax_basis_points' => ['required', 'integer', 'min:0', 'max:4000'], 'seller_state' => ['required_if:tax_enabled,1', 'nullable', 'string', 'exists:states,name'], 'delivery_otp_required' => ['required', 'boolean'], 'delivery_proof_required' => ['required', 'boolean']]);
        abort_if($d['tax_enabled'] && ! preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][A-Z0-9]Z[A-Z0-9]$/', (string) $business->gst_number), 422, 'Set a valid GSTIN on the business profile before enabling GST.');
        DB::table('growya_commerce_policies')->updateOrInsert(['business_id' => $business->id], $d + ['updated_at' => now(), 'created_at' => now()]);

        return $this->reply($r, (array) $service->policy($business->id));
    }
}
