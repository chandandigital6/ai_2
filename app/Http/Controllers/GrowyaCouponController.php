<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\GrowyaCoupon;
use App\Services\BusinessAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GrowyaCouponController extends Controller
{
    private function authorizeBusiness(Business $business): void
    {
        abort_unless(auth()->user() && app(BusinessAccessService::class)->can(auth()->user(), $business, 'coupons.view'), 403);
    }

    private function rules(Request $request, Business $business, ?GrowyaCoupon $coupon = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9_-]+$/', Rule::unique('growya_coupons', 'code')->where('business_id', $business->id)->ignore($coupon?->id)],
            'discount_type' => ['required', Rule::in(['fixed', 'percent'])],
            'discount_value' => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'minimum_subtotal' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'maximum_discount' => ['nullable', 'numeric', 'gt:0', 'max:99999999'],
            'usage_limit' => ['nullable', 'integer', 'min:1', 'max:10000000'],
            'per_user_limit' => ['required', 'integer', 'min:1', 'max:10000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'is_active' => ['required', 'boolean'],
        ]);
    }

    public function index(Business $business)
    {
        $this->authorizeBusiness($business);
        $coupons = GrowyaCoupon::where('business_id', $business->id)->latest('id')->paginate(20);

        return view('businesses.coupons.index', compact('business', 'coupons'));
    }

    public function create(Business $business)
    {
        $this->authorizeBusiness($business);
        $coupon = new GrowyaCoupon(['is_active' => true, 'per_user_limit' => 1, 'minimum_subtotal' => 0]);

        return view('businesses.coupons.form', compact('business', 'coupon'));
    }

    public function store(Request $request, Business $business)
    {
        $this->authorizeBusiness($business);
        $data = $this->rules($request, $business);
        $data['code'] = strtoupper($data['code']);
        if ($data['discount_type'] === 'percent' && $data['discount_value'] > 100) {
            return back()->withErrors(['discount_value' => 'Percentage cannot exceed 100.'])->withInput();
        }
        $data['business_id'] = $business->id;
        GrowyaCoupon::create($data);

        return redirect()->route('businesses.coupons.index', $business)->with('success', 'Coupon created.');
    }

    public function edit(Business $business, GrowyaCoupon $coupon)
    {
        $this->authorizeBusiness($business);
        abort_unless($coupon->business_id === $business->id, 404);

        return view('businesses.coupons.form', compact('business', 'coupon'));
    }

    public function update(Request $request, Business $business, GrowyaCoupon $coupon)
    {
        $this->authorizeBusiness($business);
        abort_unless($coupon->business_id === $business->id, 404);
        $data = $this->rules($request, $business, $coupon);
        $data['code'] = strtoupper($data['code']);
        if ($data['discount_type'] === 'percent' && $data['discount_value'] > 100) {
            return back()->withErrors(['discount_value' => 'Percentage cannot exceed 100.'])->withInput();
        }
        DB::transaction(function () use ($coupon, $data) {
            $locked = GrowyaCoupon::whereKey($coupon->id)->lockForUpdate()->firstOrFail();
            if ($locked->used_count > 0 && ($data['code'] !== $locked->code || $data['discount_type'] !== $locked->discount_type || (float) $data['discount_value'] !== (float) $locked->discount_value)) {
                throw ValidationException::withMessages(['code' => 'Used coupon code/type/value cannot be changed. Deactivate it and create a new coupon.']);
            }
            if ($data['usage_limit'] !== null && $data['usage_limit'] < $locked->used_count) {
                throw ValidationException::withMessages(['usage_limit' => 'Limit cannot be smaller than existing usage.']);
            }
            $locked->update($data);
        }, 3);

        return redirect()->route('businesses.coupons.index', $business)->with('success', 'Coupon updated.');
    }
}
