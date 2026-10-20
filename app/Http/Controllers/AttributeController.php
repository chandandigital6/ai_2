<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AttributeController extends Controller
{
    private function authorizeAdmin(): void
    {
        abort_unless(
            auth()->user()?->hasRole('super_admin', 'web'),
            403
        );
    }

    public function index(Request $request)
    {
        $this->authorizeAdmin();
        if (isset($attribute)) {
            abort_unless($attribute->business_id === null, 404);
        }

        $query = Attribute::query()->whereNull('business_id')
            ->with([
                'values' => fn ($q) => $q
                    ->orderBy('sort_order')
                    ->orderBy('id'),
            ])
            ->withCount('values');

        if ($request->filled('search')) {
            $search = trim((string) $request->search);

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $attributes = $query
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view(
            'attributes.index',
            compact('attributes')
        );
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();
        if (isset($attribute)) {
            abort_unless($attribute->business_id === null, 404);
        }

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('attributes', 'name'),
            ],
        ]);

        $name = trim($data['name']);

        Attribute::create([
            'name' => $name,
            'slug' => $this->uniqueSlug($name),
        ]);

        return redirect()
            ->route('attributes.index')
            ->with(
                'success',
                'Attribute created successfully.'
            );
    }

    public function update(
        Request $request,
        Attribute $attribute
    ) {
        $this->authorizeAdmin();
        if (isset($attribute)) {
            abort_unless($attribute->business_id === null, 404);
        }

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('attributes', 'name')
                    ->ignore($attribute->id),
            ],
        ]);

        $name = trim($data['name']);

        $attribute->update([
            'name' => $name,
            'slug' => $attribute->name !== $name
                ? $this->uniqueSlug(
                    $name,
                    $attribute->id
                )
                : $attribute->slug,
        ]);

        return back()->with(
            'success',
            'Attribute updated successfully.'
        );
    }

    public function destroy(Attribute $attribute)
    {
        $this->authorizeAdmin();
        if (isset($attribute)) {
            abort_unless($attribute->business_id === null, 404);
        }

        if ($attribute->values()->exists()) {
            return back()->withErrors([
                'attribute' => 'Delete all attribute values first.',
            ]);
        }

        $attribute->delete();

        return back()->with(
            'success',
            'Attribute deleted successfully.'
        );
    }

    public function storeValue(
        Request $request,
        Attribute $attribute
    ) {
        $this->authorizeAdmin();
        if (isset($attribute)) {
            abort_unless($attribute->business_id === null, 404);
        }

        $data = $request->validate([
            'value' => [
                'required',
                'string',
                'max:255',
                Rule::unique('attribute_values', 'value')
                    ->where(
                        'attribute_id',
                        $attribute->id
                    ),
            ],
            'sort_order' => [
                'required',
                'integer',
                'min:0',
                'max:999999',
            ],
        ]);

        $attribute->values()->create($data);

        return back()->with(
            'success',
            'Attribute value added successfully.'
        );
    }

    public function updateValue(
        Request $request,
        Attribute $attribute,
        AttributeValue $value
    ) {
        $this->authorizeAdmin();
        if (isset($attribute)) {
            abort_unless($attribute->business_id === null, 404);
        }
        $this->checkValue($attribute, $value);

        $data = $request->validate([
            'value' => [
                'required',
                'string',
                'max:255',
                Rule::unique('attribute_values', 'value')
                    ->where(
                        'attribute_id',
                        $attribute->id
                    )
                    ->ignore($value->id),
            ],
            'sort_order' => [
                'required',
                'integer',
                'min:0',
                'max:999999',
            ],
        ]);

        $value->update($data);

        return back()->with(
            'success',
            'Attribute value updated successfully.'
        );
    }

    public function destroyValue(
        Attribute $attribute,
        AttributeValue $value
    ) {
        $this->authorizeAdmin();
        if (isset($attribute)) {
            abort_unless($attribute->business_id === null, 404);
        }
        $this->checkValue($attribute, $value);

        if ($value->products()->exists() || DB::table('product_variant_attribute_values')->where('attribute_value_id', $value->id)->exists()) {
            return back()->withErrors([
                'value' => 'This value is assigned to products. Remove it from those products first.',
            ]);
        }

        $value->delete();

        return back()->with(
            'success',
            'Attribute value deleted successfully.'
        );
    }

    private function checkValue(
        Attribute $attribute,
        AttributeValue $value
    ): void {
        abort_unless(
            (int) $value->attribute_id
                === (int) $attribute->id,
            404
        );
    }

    private function uniqueSlug(
        string $name,
        ?int $ignoreId = null
    ): string {
        $base = Str::slug($name) ?: 'attribute';
        $slug = $base;
        $number = 2;

        while (
            Attribute::query()->whereNull('business_id')
                ->where('slug', $slug)
                ->when(
                    $ignoreId,
                    fn ($q) => $q->where(
                        'id',
                        '!=',
                        $ignoreId
                    )
                )
                ->exists()
        ) {
            $slug = $base.'-'.$number++;
        }

        return $slug;
    }
}
