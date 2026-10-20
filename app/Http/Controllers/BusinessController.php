<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;

class BusinessController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Business::query()->with([
            'owner:id,name',
            'category:id,name',
            'city:id,name',
        ]);

        // Global business.view must NOT expose other businesses to owners.
        if (! $user->hasRole('super_admin', 'web')) {
            $query->where(function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                    ->orWhereHas('businessUsers', function ($members) use ($user) {
                        $members->where('user_id', $user->id)
                            ->where('is_active', true);
                    });
            });
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $businesses = $query->latest('id')->paginate(15)->withQueryString();

        return view('businesses.index', compact('businesses'));
    }

    public function create(Request $request)
    {
        Gate::authorize('create', Business::class);

        return view('businesses.create', $this->formOptions());
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Business::class);

        $data = $this->validated($request);
        $user = $request->user();

        $ownerId = $user->id;

        if (
            $user->hasRole('super_admin', 'web')
            && $request->filled('owner_id')
        ) {
            $ownerId = $request->validate([
                'owner_id' => [
                    'required',
                    Rule::exists('users', 'id')
                        ->where('status', 'active'),
                ],
            ])['owner_id'];
        }

        unset($data['logo'], $data['cover']);

        $uploadedPaths = [];

        try {
            $business = DB::transaction(function () use (
                $data,
                $ownerId,
                $request,
                &$uploadedPaths
            ) {
                $slug = (Str::slug($data['name']) ?: 'business')
                    .'-'.strtolower((string) Str::ulid());

                $business = Business::create([
                    ...$data,
                    'slug' => $slug,
                    'owner_id' => $ownerId,
                    'status' => 'active',
                    'verification_status' => 'pending',
                ]);

                foreach (['logo', 'cover'] as $field) {
                    if ($request->hasFile($field)) {
                        $path = $request->file($field)->store(
                            "businesses/{$business->id}/{$field}",
                            'public'
                        );

                        if (! $path) {
                            throw new \RuntimeException(
                                "Failed to upload {$field}."
                            );
                        }

                        $uploadedPaths[] = $path;
                        $business->{$field} = $path;
                    }
                }

                $business->save();

                $business->businessUsers()->create([
                    'user_id' => $ownerId,
                    'role' => 'owner',
                    'is_active' => true,
                    'joined_at' => now(),
                ]);

                return $business;
            });
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($uploadedPaths);
            throw $e;
        }

        return redirect()
            ->route('growya.business-request.status')
            ->with('success', 'Your business application was submitted for Super Admin approval.');
    }

    public function show(Business $business)
    {
        Gate::authorize('view', $business);
        $business->load([
            'owner:id,name,email',
            'category:id,name',
            'country:id,name',
            'state:id,name',
            'city:id,name',
            'hours',
            'socialLinks',
        ]);

        return view('businesses.show', compact('business'));
    }

    public function edit(Business $business)
    {
        Gate::authorize('update', $business);

        return view('businesses.edit', ['business' => $business, ...$this->formOptions()]);
    }

    public function update(Request $request, Business $business)
    {
        Gate::authorize('update', $business);

        $data = $this->validated($request);

        unset($data['logo'], $data['cover']);

        $oldPaths = [];
        $newPaths = [];

        try {
            foreach (['logo', 'cover'] as $field) {
                if ($request->hasFile($field)) {
                    $path = $request->file($field)->store(
                        "businesses/{$business->id}/{$field}",
                        'public'
                    );

                    if (! $path) {
                        throw new \RuntimeException(
                            "Failed to upload {$field}."
                        );
                    }

                    $oldPaths[] = $business->{$field};
                    $newPaths[] = $path;

                    $data[$field] = $path;
                }
            }

            if ($business->verification_status === 'rejected' && (int) $business->owner_id === (int) $request->user()->id) {
                $data['verification_status'] = 'pending';
            }
            $business->update($data);
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($newPaths);
            throw $e;
        }

        // Database update succeeded; remove replaced files.
        foreach ($oldPaths as $oldPath) {
            if ($oldPath) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        return redirect()
            ->route('businesses.show', $business)
            ->with('success', 'Business updated successfully.');
    }

    public function destroy(Business $business)
    {
        Gate::authorize('delete', $business);
        $business->delete(); // Soft delete; member records remain for recovery

        return redirect()->route('businesses.index')->with('success', 'Business moved to trash.');
    }

    private function validated(Request $request): array
    {
        $india = Country::query()
            ->where('iso_code', 'IND')
            ->where('is_active', true)
            ->firstOrFail();

        $request->merge([
            'country_id' => $india->id,
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],

            'description' => [
                'nullable',
                'string',
                'max:10000',
            ],

            'category_id' => [
                'nullable',
                Rule::exists('categories', 'id')
                    ->whereNull('business_id')->where('category_type', 'business')
                    ->where('is_active', true),
            ],

            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:2000'],
            'pincode' => ['nullable', 'string', 'max:12'],

            'country_id' => [
                'required',
                Rule::exists('countries', 'id')
                    ->where('is_active', true),
            ],

            'state_id' => [
                'required',
                Rule::exists('states', 'id')
                    ->where('country_id', $india->id)
                    ->where('is_active', true),
            ],

            'city_id' => [
                'required',
                Rule::exists('cities', 'id')
                    ->where('is_active', true),
            ],

            'gst_number' => [
                'nullable',
                'string',
                'max:30',
            ],

            'upi_id' => [
                'nullable',
                'string',
                'max:255',
            ],

            'logo' => [
                'nullable',
                File::image()
                    ->types(['jpg', 'jpeg', 'png', 'webp'])
                    ->max('2mb'),
            ],

            'cover' => [
                'nullable',
                File::image()
                    ->types(['jpg', 'jpeg', 'png', 'webp'])
                    ->max('5mb'),
            ],

            'cod_enabled' => ['sometimes', 'boolean'],
            'upi_enabled' => ['sometimes', 'boolean'],
        ]);

        $validCity = City::query()
            ->whereKey($data['city_id'])
            ->where('state_id', $data['state_id'])
            ->where('is_active', true)
            ->exists();

        if (! $validCity) {
            throw ValidationException::withMessages([
                    'city_id' => 'Please select a city from the selected state.',
                ]);
        }

        $data['cod_enabled'] = $request->boolean('cod_enabled');
        $data['upi_enabled'] = $request->boolean('upi_enabled');

        return $data;
    }

    private function formOptions(): array
    {
        return [
            'categories' => Category::whereNull('business_id')->whereNull('business_id')->where('category_type', 'business')->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'countries' => Country::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'states' => State::where('is_active', true)->orderBy('name')->get(['id', 'name', 'country_id']),
            'cities' => City::where('is_active', true)->orderBy('name')->get(['id', 'name', 'state_id']),
            'owners' => auth()->user()->hasRole('super_admin', 'web') ? User::where('status', 'active')->orderBy('name')->get(['id', 'name', 'email']) : collect(),
        ];
    }
}
