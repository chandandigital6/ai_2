<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\GrowyaPaidPromotion;
use App\Models\GrowyaPromotionPackage;
use App\Services\BusinessAccessService;
use App\Services\GrowyaNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GrowyaPaidPromotionController extends Controller
{
    private function admin(): void
    {
        abort_unless(auth()->check() && auth()->user()->hasRole('super_admin', 'web'), 403);
    }

    private function owner(Business $business): void
    {
        abort_unless(auth()->user() && app(BusinessAccessService::class)->can(auth()->user(), $business, 'promotions.view'), 403);
    }

    public function packages()
    {
        $this->admin();

        return view('growya-promotions.packages', ['packages' => GrowyaPromotionPackage::latest()->get()]);
    }

    public function storePackage(Request $r)
    {
        $this->admin();
        $d = $r->validate(['name' => 'required|string|max:120', 'target_type' => ['required', Rule::in(['business', 'product'])], 'duration_days' => 'required|integer|min:1|max:365', 'price' => 'required|numeric|min:0|max:99999999', 'is_active' => 'sometimes|boolean']);
        $d['is_active'] = $r->boolean('is_active');
        GrowyaPromotionPackage::create($d);

        return back()->with('success', 'Package created.');
    }

    public function updatePackage(Request $r, GrowyaPromotionPackage $package)
    {
        $this->admin();
        $d = $r->validate(['name' => 'required|string|max:120', 'duration_days' => 'required|integer|min:1|max:365', 'price' => 'required|numeric|min:0|max:99999999', 'is_active' => 'sometimes|boolean']);
        $d['is_active'] = $r->boolean('is_active');
        $package->update($d);

        return back()->with('success', 'Package updated.');
    }

    public function ownerIndex(Business $business)
    {
        $this->owner($business);

        return view('growya-promotions.owner', ['business' => $business, 'packages' => GrowyaPromotionPackage::where('is_active', true)->get(), 'products' => $business->products()->where('status', 'active')->orderBy('name')->get(['id', 'name']), 'promotions' => GrowyaPaidPromotion::where('business_id', $business->id)->with(['package', 'product'])->latest()->paginate(20)]);
    }

    public function request(Request $r, Business $business)
    {
        $this->owner($business);
        $d = $r->validate(['package_id' => 'required|exists:growya_promotion_packages,id', 'product_id' => 'nullable|integer', 'payment_reference' => 'nullable|string|max:150']);
        $package = GrowyaPromotionPackage::findOrFail($d['package_id']);
        abort_unless($package->is_active, 422);
        $productId = null;
        if ($package->target_type === 'product') {
            $productId = $d['product_id'] ?? null;
            abort_unless($productId && $business->products()->whereKey($productId)->where('status', 'active')->exists(), 422, 'Choose an active product belonging to this business.');
        } elseif (! empty($d['product_id'])) {
            throw ValidationException::withMessages(['product_id' => 'Business package cannot target a product.']);
        }
        $promotion = DB::transaction(function () use ($business, $package, $productId, $d) {
            $p = GrowyaPaidPromotion::create(['business_id' => $business->id, 'package_id' => $package->id, 'product_id' => $productId, 'requested_by' => auth()->id(), 'status' => 'pending', 'payment_reference' => $d['payment_reference'] ?? null, 'payment_status' => 'unverified']);
            DB::table('growya_promotion_events')->insert(['promotion_id' => $p->id, 'event' => 'requested', 'actor_id' => auth()->id(), 'notes' => null, 'created_at' => now(), 'updated_at' => now()]);

            return $p;
        });
        app(GrowyaNotificationService::class)->send(app(GrowyaNotificationService::class)->superAdmins(), 'promotion_request', 'New promotion request', $business->name.' requested a promotion.', '/admin/paid-promotions', $business->id);

        return back()->with('success', 'Request submitted. Await admin review and manual payment verification.');
    }

    public function adminIndex(Request $r)
    {
        $this->admin();
        $q = GrowyaPaidPromotion::with(['package', 'business', 'product', 'requester'])->latest();
        if (in_array($r->query('status'), ['pending', 'active', 'rejected', 'expired'], true)) {
            $q->where('status', $r->query('status'));
        }

        return view('growya-promotions.admin', ['promotions' => $q->paginate(30)->withQueryString()]);
    }

    public function review(Request $r, GrowyaPaidPromotion $promotion)
    {
        $this->admin();
        $d = $r->validate(['decision' => ['required', Rule::in(['approve', 'reject'])], 'payment_verified' => 'sometimes|boolean', 'review_note' => 'nullable|string|max:2000']);
        $approved = $d['decision'] === 'approve';
        if ($approved && ! $r->boolean('payment_verified')) {
            throw ValidationException::withMessages(['payment_verified' => 'Confirm receipt in the actual bank/UPI records before approval.']);
        }
        $result = DB::transaction(function () use ($promotion, $approved, $d) {
            $p = GrowyaPaidPromotion::query()->lockForUpdate()->findOrFail($promotion->id);
            if ($p->status !== 'pending') {
                throw ValidationException::withMessages(['decision' => 'This request has already been reviewed.']);
            }
            if ($approved && ($p->business->status !== 'active' || $p->business->verification_status !== 'approved' || ($p->product_id && (! $p->product || $p->product->status !== 'active')))) {
                throw ValidationException::withMessages(['decision' => 'Business or product is not currently eligible.']);
            }
            $p->status = $approved ? 'active' : 'rejected';
            $p->payment_status = $approved ? 'verified' : 'unverified';
            $p->reviewed_by = auth()->id();
            $p->reviewed_at = now();
            $p->review_note = $d['review_note'] ?? null;
            if ($approved) {
                $p->starts_at = now();
                $p->ends_at = now()->addDays($p->package->duration_days);
            }
            $p->save();
            DB::table('growya_promotion_events')->insert(['promotion_id' => $p->id, 'event' => $approved ? 'approved' : 'rejected', 'actor_id' => auth()->id(), 'notes' => $p->review_note, 'created_at' => now(), 'updated_at' => now()]);

            return $p;
        });
        app(GrowyaNotificationService::class)->send([(int) $result->business->owner_id], $approved ? 'promotion_approved' : 'promotion_rejected', $approved ? 'Promotion approved' : 'Promotion rejected', $result->business->name.' promotion '.$result->status.'.', '/businesses/'.$result->business_id.'/paid-promotions', $result->business_id);

        return back()->with('success', 'Promotion reviewed.');
    }

    public function click(GrowyaPaidPromotion $promotion)
    {
        abort_unless($promotion->status === 'active' && $promotion->starts_at?->lte(now()) && $promotion->ends_at?->gt(now()) && $promotion->business?->status === 'active' && $promotion->business?->verification_status === 'approved', 404);
        $p = $promotion->product;
        if ($promotion->product_id && (! $p || $p->status !== 'active')) {
            abort(404);
        }
        $promotion->increment('clicks');

        return redirect()->route($p ? 'public.business.product' : 'public.business.home', $p ? [$promotion->business->slug, $p->slug] : [$promotion->business->slug]);
    }

    public function expire()
    {
        $this->admin();

        return abort(405);
    }
}
