<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Offer;
use App\Models\Product;
use App\Models\Service;
use App\Services\BusinessAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BusinessOfferController extends Controller
{
    public function __construct(private BusinessAccessService $access) {}

    private function allow(Business $business, string $permission): void
    {
        abort_unless(auth()->check() && $this->access->can(auth()->user(), $business, $permission), 403);
    }

    private function owned(Business $business, Offer $offer): void
    {
        abort_unless((int) $offer->business_id === (int) $business->id, 404);
    }

    private function choices(Business $business): array
    {
        return [
            'products' => Product::where('business_id', $business->id)->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'services' => Service::where('business_id', $business->id)->where('status', 'active')->orderBy('name')->get(['id', 'name']),
        ];
    }

    private function validateOffer(Request $request, Business $business): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'offer_type' => ['required', Rule::in(['percentage', 'fixed', 'announcement'])],
            'discount_value' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99', 'required_unless:offer_type,announcement'],
            'starts_at' => ['nullable', 'date_format:Y-m-d'],
            'ends_at' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:starts_at'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'banner_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'product_ids' => ['sometimes', 'array', 'max:100'],
            'product_ids.*' => ['integer', 'distinct', Rule::exists('products', 'id')->where('business_id', $business->id)->where('status', 'active')->whereNull('deleted_at')],
            'service_ids' => ['sometimes', 'array', 'max:100'],
            'service_ids.*' => ['integer', 'distinct', Rule::exists('services', 'id')->where('business_id', $business->id)->where('status', 'active')->whereNull('deleted_at')],
        ]);
    }

    private function assertDiscount(array $data): void
    {
        if ($data['offer_type'] === 'percentage' && (float) $data['discount_value'] > 100) {
            throw ValidationException::withMessages(['discount_value' => 'Percentage cannot exceed 100.']);
        }
    }

    public function index(Request $request, Business $business)
    {
        $this->allow($business, 'offers.view');
        $offers = Offer::where('business_id', $business->id)->withCount(['offer_products', 'offer_services'])->latest('id')->paginate(15);

        return view('businesses.offers.index', compact('business', 'offers'));
    }

    public function create(Business $business)
    {
        $this->allow($business, 'offers.manage');

        return view('businesses.offers.form', array_merge(['business' => $business, 'offer' => new Offer, 'selectedProducts' => [], 'selectedServices' => []], $this->choices($business)));
    }

    public function store(Request $request, Business $business)
    {
        $this->allow($business, 'offers.manage');
        $data = $this->validateOffer($request, $business);
        $this->assertDiscount($data);
        $path = $request->hasFile('banner_file') ? $request->file('banner_file')->store('business-offers/'.$business->id, 'public') : null;
        try {
            DB::transaction(function () use ($business, $data, $path) {
                $offer = Offer::create([
                    'business_id' => $business->id, 'title' => $data['title'], 'description' => $data['description'] ?? null,
                    'offer_type' => $data['offer_type'],
                    'discount_value' => $data['offer_type'] === 'announcement' ? null : $data['discount_value'],
                    'starts_at' => $data['starts_at'] ?? null, 'ends_at' => $data['ends_at'] ?? null,
                    'status' => $data['status'], 'banner' => $path,
                ]);
                $offer->offer_products()->createMany(array_map(fn ($id) => ['product_id' => $id], $data['product_ids'] ?? []));
                $offer->offer_services()->createMany(array_map(fn ($id) => ['service_id' => $id], $data['service_ids'] ?? []));
            });
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }
            throw $e;
        }

        return redirect()->route('businesses.offers.index', $business)->with('success', 'Offer created.');
    }

    public function edit(Business $business, Offer $offer)
    {
        $this->allow($business, 'offers.manage');
        $this->owned($business, $offer);

        return view('businesses.offers.form', array_merge([
            'business' => $business, 'offer' => $offer,
            'selectedProducts' => $offer->offer_products()->pluck('product_id')->all(),
            'selectedServices' => $offer->offer_services()->pluck('service_id')->all(),
        ], $this->choices($business)));
    }

    public function update(Request $request, Business $business, Offer $offer)
    {
        $this->allow($business, 'offers.manage');
        $this->owned($business, $offer);
        $data = $this->validateOffer($request, $business);
        $this->assertDiscount($data);
        $path = $request->hasFile('banner_file') ? $request->file('banner_file')->store('business-offers/'.$business->id, 'public') : null;
        $old = $offer->banner;
        try {
            DB::transaction(function () use ($offer, $data, $path) {
                $fields = [
                    'title' => $data['title'], 'description' => $data['description'] ?? null, 'offer_type' => $data['offer_type'],
                    'discount_value' => $data['offer_type'] === 'announcement' ? null : $data['discount_value'],
                    'starts_at' => $data['starts_at'] ?? null, 'ends_at' => $data['ends_at'] ?? null, 'status' => $data['status'],
                ];
                if ($path) {
                    $fields['banner'] = $path;
                }
                $offer->update($fields);
                $offer->offer_products()->delete();
                $offer->offer_services()->delete();
                $offer->offer_products()->createMany(array_map(fn ($id) => ['product_id' => $id], $data['product_ids'] ?? []));
                $offer->offer_services()->createMany(array_map(fn ($id) => ['service_id' => $id], $data['service_ids'] ?? []));
            });
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }
            throw $e;
        }
        if ($path && $old && str_starts_with($old, 'business-offers/'.$business->id.'/')) {
            Storage::disk('public')->delete($old);
        }

        return redirect()->route('businesses.offers.index', $business)->with('success', 'Offer updated.');
    }

    public function destroy(Business $business, Offer $offer)
    {
        $this->allow($business, 'offers.manage');
        $this->owned($business, $offer);
        DB::transaction(function () use ($offer) {
            $offer->offer_products()->delete();
            $offer->offer_services()->delete();
            $offer->delete();
        });

        // Soft-deleted offers retain the banner for recovery; public queries exclude them.
        return redirect()->route('businesses.offers.index',$business)->with('success','Offer archived.');
    }
}
