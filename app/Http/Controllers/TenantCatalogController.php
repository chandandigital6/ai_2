<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\Business;
use App\Models\Category;
use App\Services\BusinessAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TenantCatalogController extends Controller
{
    private function scope(Request $request, Business $business, string $kind, bool $write = false): void
    {
        abort_unless(in_array($kind, ['categories', 'attributes'], true), 404);
        abort_unless(app(BusinessAccessService::class)->can($request->user(), $business, $kind.'.'.($write ? 'manage' : 'view')), 403);
    }

    private function model(string $kind): string
    {
        return $kind === 'categories' ? Category::class : Attribute::class;
    }

    public function index(Request $request, Business $business, string $kind)
    {
        $this->scope($request, $business, $kind);
        $query = $this->model($kind)::where('business_id', $business->id);
        if ($kind === 'attributes') {
            $query->with('values');
        }
        $items = $query->orderBy('name')->paginate(20)->withQueryString();
        $parents = Category::where('business_id', $business->id)->whereNull('parent_id')->orderBy('name')->get();

        return view('businesses.catalog.index', compact('business', 'kind', 'items', 'parents'));
    }

    private function data(Request $request, Business $business, string $kind, $item = null): array
    {
        $rules = ['name' => ['required', 'string', 'max:255', Rule::unique($kind, 'name')->where('business_id', $business->id)->ignore($item?->id)]];
        if ($kind === 'categories') {
            $rules += [
                'category_type' => ['required', Rule::in(['product', 'service'])],
                'parent_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('business_id', $business->id)->where('category_type', $request->input('category_type'))->whereNull('parent_id')],
                'is_active' => ['required', 'boolean'], 'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
            ];
        }
        $data = $request->validate($rules);
        if ($kind === 'categories' && $item) {
            if ((int) ($data['parent_id'] ?? 0) === (int) $item->id || ($item->children()->exists() && ! empty($data['parent_id']))) {
                throw ValidationException::withMessages(['parent_id' => 'A category with children cannot become a child.']);
            }
            if ($item->category_type !== $data['category_type'] && ($item->products()->exists() || $item->services()->exists() || $item->children()->exists())) {
                throw ValidationException::withMessages(['category_type' => 'An in-use category cannot change type.']);
            }
        }

        return $data;
    }

    public function store(Request $request, Business $business, string $kind)
    {
        $this->scope($request, $business, $kind, true);
        $data = $this->data($request, $business, $kind);
        $this->model($kind)::create($data + ['business_id' => $business->id, 'slug' => (Str::slug($data['name']) ?: 'item').'-'.strtolower((string) Str::ulid())]);

        return back()->with('success', 'Created successfully.');
    }

    public function update(Request $request, Business $business, string $kind, int $item)
    {
        $this->scope($request, $business, $kind, true);
        $record = $this->model($kind)::where('business_id', $business->id)->findOrFail($item);
        $record->update($this->data($request, $business, $kind, $record));

        return back()->with('success', 'Updated successfully.');
    }

    public function destroy(Request $request, Business $business, string $kind, int $item)
    {
        $this->scope($request, $business, $kind, true);
        $record = $this->model($kind)::where('business_id', $business->id)->findOrFail($item);
        if ($kind === 'categories' ? ($record->children()->exists() || $record->products()->exists() || $record->services()->exists()) : $record->values()->exists()) {
            throw ValidationException::withMessages(['item' => 'This item is in use. Remove associations first.']);
        }
        $record->delete();

        return back()->with('success', 'Deleted successfully.');
    }

    public function storeValue(Request $request, Business $business, int $attribute)
    {
        abort_unless(app(BusinessAccessService::class)->can($request->user(), $business, 'attributes.manage'), 403);
        $record = Attribute::where('business_id', $business->id)->findOrFail($attribute);
        $data = $request->validate(['value' => ['required', 'string', 'max:255', Rule::unique('attribute_values', 'value')->where('attribute_id', $attribute)], 'sort_order' => ['nullable', 'integer', 'min:0']]);
        $record->values()->create($data);

        return back()->with('success', 'Attribute value added.');
    }

    public function destroyValue(Request $request, Business $business, int $attribute, int $value)
    {
        abort_unless(app(BusinessAccessService::class)->can($request->user(), $business, 'attributes.manage'), 403);
        $attribute = Attribute::where('business_id', $business->id)->findOrFail($attribute);
        $value = $attribute->values()->findOrFail($value);
        if ($value->products()->exists() || DB::table('product_variant_attribute_values')->where('attribute_value_id', $value->id)->exists()) {
            throw ValidationException::withMessages(['value' => 'This value is assigned to a product or variant.']);
        }
        $value->delete();

        return back()->with('success','Value deleted.');
    }
}
