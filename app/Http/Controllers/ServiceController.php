<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Category;
use App\Models\Service;
use App\Services\BusinessAccessService;
use App\Services\GrowyaPlanLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ServiceController extends Controller
{
    public function __construct(
        private BusinessAccessService $access
    ) {}

    private function authorizeBusiness(
        Business $business,
        string $permission
    ): void {
        abort_unless(
            auth()->check()
            && $this->access->can(
                auth()->user(),
                $business,
                $permission
            ),
            403
        );
    }

    private function authorizeService(
        Business $business,
        Service $service,
        string $permission
    ): void {
        abort_unless(
            (int) $service->business_id === (int) $business->id,
            404
        );

        $this->authorizeBusiness($business, $permission);
    }

    private function categories(Business $business)
    {
        return Category::query()->where(fn ($q) => $q->whereNull('business_id')->orWhere('business_id', $business->id))
            ->where('category_type', 'service')
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'parent_id',
            ]);
    }

    public function index(Request $request, Business $business)
    {
        $this->authorizeBusiness($business, 'services.view');

        $query = $business->services()
            ->with('category:id,name')
            ->withCount('images')
            ->latest('id');

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            });
        }

        if (in_array(
            $request->input('status'),
            ['active', 'inactive'],
            true
        )) {
            $query->where('status', $request->input('status'));
        }

        $services = $query
            ->paginate(15)
            ->withQueryString();

        return view('services.index', compact(
            'business',
            'services'
        ));
    }

    public function create(Business $business)
    {
        $this->authorizeBusiness($business, 'services.create');

        return view('services.form', [
            'business' => $business,
            'service' => new Service,
            'categories' => $this->categories($business),
        ]);
    }

    private function validated(
        Request $request,
        ?Service $service = null
    ): array {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'category_id' => [
                'nullable',
                Rule::exists('categories', 'id')->where(fn ($q) => $q->whereNull('business_id')->orWhere('business_id', $business->id))
                    ->where('category_type', 'service')
                    ->where('is_active', true),
            ],

            'description' => [
                'nullable',
                'string',
                'max:20000',
            ],

            'price_type' => [
                'required',
                Rule::in([
                    'fixed',
                    'starting_from',
                    'on_request',
                    'free',
                ]),
            ],

            'price' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999999.99',
            ],

            'offer_price' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999999.99',
            ],

            'duration_minutes' => [
                'nullable',
                'integer',
                'min:1',
                'max:525600',
            ],

            'status' => [
                'required',
                Rule::in(['active', 'inactive']),
            ],

            'featured_image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:3072',
            ],
        ]);

        if (in_array(
            $data['price_type'],
            ['fixed', 'starting_from'],
            true
        )) {
            if (! isset($data['price'])) {
                throw ValidationException::withMessages([
                    'price' => 'Price is required for this price type.',
                ]);
            }

            if (
                isset($data['offer_price'])
                && (float) $data['offer_price'] > (float) $data['price']
            ) {
                throw ValidationException::withMessages([
                    'offer_price' => 'Offer price cannot exceed regular price.',
                ]);
            }
        } else {
            $data['price'] = null;
            $data['offer_price'] = null;
        }

        return $data;
    }

    private function uniqueSlug(
        Business $business,
        string $name,
        ?Service $service = null
    ): string {
        $base = Str::slug($name) ?: 'service';
        $slug = $base;
        $counter = 2;

        while (
            Service::withTrashed()
                ->where('business_id', $business->id)
                ->where('slug', $slug)
                ->when(
                    $service,
                    fn ($q) => $q->where('id', '!=', $service->id)
                )
                ->exists()
        ) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    public function store(Request $request, Business $business)
    {
        $this->authorizeBusiness($business, 'services.create');

        $data = $this->validated($request);

        unset($data['featured_image']);

        $data['slug'] = $this->uniqueSlug(
            $business,
            $data['name']
        );

        $imagePath = null;

        try {
            if ($request->hasFile('featured_image')) {
                $imagePath = $request
                    ->file('featured_image')
                    ->store(
                        "businesses/{$business->id}/services",
                        'public'
                    );

                $data['featured_image'] = $imagePath;
            }

            $service = DB::transaction(function () use (
                $business,
                $data
            ) {
                Business::whereKey($business->id)->lockForUpdate()->firstOrFail();
                app(GrowyaPlanLimitService::class)->assertCanAdd($business, 'max_services');

                return $business->services()->create($data);
            });
        } catch (\Throwable $e) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }

            throw $e;
        }

        return redirect()
            ->route('businesses.services.show', [
                $business,
                $service,
            ])
            ->with('success', 'Service created successfully.');
    }

    public function show(Business $business, Service $service)
    {
        $this->authorizeService(
            $business,
            $service,
            'services.view'
        );

        $service->load([
            'category:id,name',
            'images',
        ]);

        return view('services.show', compact(
            'business',
            'service'
        ));
    }

    public function edit(Business $business, Service $service)
    {
        $this->authorizeService(
            $business,
            $service,
            'services.update'
        );

        return view('services.form', [
            'business' => $business,
            'service' => $service,
            'categories' => $this->categories($business),
        ]);
    }

    public function update(
        Request $request,
        Business $business,
        Service $service
    ) {
        $this->authorizeService(
            $business,
            $service,
            'services.update'
        );

        $data = $this->validated($request, $service);

        unset($data['featured_image']);

        // Keep the slug stable when editing a service.
        $oldImage = $service->featured_image;
        $newImage = null;

        try {
            if ($request->hasFile('featured_image')) {
                $newImage = $request
                    ->file('featured_image')
                    ->store(
                        "businesses/{$business->id}/services",
                        'public'
                    );

                $data['featured_image'] = $newImage;
            }

            DB::transaction(function () use ($service, $data) {
                $service->update($data);
            });
        } catch (\Throwable $e) {
            if ($newImage) {
                Storage::disk('public')->delete($newImage);
            }

            throw $e;
        }

        if (
            $newImage
            && $oldImage
            && $oldImage !== $newImage
            && ! $service->images()->where('image', $oldImage)->exists()
        ) {
            Storage::disk('public')->delete($oldImage);
        }

        return redirect()
            ->route('businesses.services.show', [
                $business,
                $service,
            ])
            ->with('success', 'Service updated successfully.');
    }

    public function destroy(Business $business, Service $service)
    {
        $this->authorizeService(
            $business,
            $service,
            'services.delete'
        );

        $service->delete();

        return redirect()
            ->route('businesses.services.index', $business)
            ->with('success', 'Service deleted successfully.');
    }
}
