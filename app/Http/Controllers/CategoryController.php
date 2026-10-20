<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Category;
use App\Models\Product;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    private function authorizeAdmin(): void
    {
        abort_unless(
            auth()->user()?->hasRole('super_admin', 'web'),
            403
        );
    }

    private function types(): array
    {
        return [
            'business' => 'Business',
            'product' => 'Product',
            'service' => 'Service',
        ];
    }

    public function index(Request $request)
    {
        $this->authorizeAdmin();
        if (isset($category)) {
            abort_unless($category->business_id === null, 404);
        }

        $query = Category::query()->whereNull('business_id')->with('parent');

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if (in_array($request->type, array_keys($this->types()))) {
            $query->where('category_type', $request->type);
        }

        $categories = $query
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('categories.index', [
            'categories' => $categories,
            'types' => $this->types(),
        ]);
    }

    public function create()
    {
        $this->authorizeAdmin();
        if (isset($category)) {
            abort_unless($category->business_id === null, 404);
        }

        return view('categories.form', [
            'category' => new Category,
            'parents' => Category::query()->whereNull('business_id')
                ->whereNull('parent_id')
                ->orderBy('name')
                ->get(),
            'types' => $this->types(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();
        if (isset($category)) {
            abort_unless($category->business_id === null, 404);
        }

        $data = $this->validateCategory($request);

        $image = $request->file('image');
        unset($data['image']);

        $data['slug'] = $this->uniqueSlug(
            $data['name'],
            $data['category_type']
        );

        $path = null;

        try {
            if ($image) {
                $path = $image->store('categories', 'public');

                if (! $path) {
                    throw new \RuntimeException('Image upload failed.');
                }

                $data['image'] = $path;
            }

            Category::create($data);
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }
            throw $e;
        }

        return redirect()
            ->route('categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function edit(Category $category)
    {
        $this->authorizeAdmin();
        if (isset($category)) {
            abort_unless($category->business_id === null, 404);
        }

        return view('categories.form', [
            'category' => $category,
            'parents' => Category::query()->whereNull('business_id')
                ->whereNull('parent_id')
                ->where('id', '!=', $category->id)
                ->orderBy('name')
                ->get(),
            'types' => $this->types(),
        ]);
    }

    public function update(Request $request, Category $category)
    {
        $this->authorizeAdmin();
        if (isset($category)) {
            abort_unless($category->business_id === null, 404);
        }

        $data = $this->validateCategory($request, $category);

        // Avoid changing category hierarchy/type when in use.
        if ($category->category_type !== $data['category_type']
            && $this->isUsed($category)) {
            throw ValidationException::withMessages([
                'category_type' => 'Category is in use and its type cannot be changed.',
            ]);
        }

        if ($category->children()->exists()
            && ! empty($data['parent_id'])) {
            throw ValidationException::withMessages([
                'parent_id' => 'A parent category cannot become a subcategory while it has children.',
            ]);
        }

        $image = $request->file('image');
        unset($data['image']);

        if ($category->name !== $data['name']
            || $category->category_type !== $data['category_type']) {
            $data['slug'] = $this->uniqueSlug(
                $data['name'],
                $data['category_type'],
                $category->id
            );
        }

        $newPath = null;
        $oldPath = $category->image;

        try {
            if ($image) {
                $newPath = $image->store('categories', 'public');

                if (! $newPath) {
                    throw new \RuntimeException('Image upload failed.');
                }

                $data['image'] = $newPath;
            }

            $category->update($data);
        } catch (\Throwable $e) {
            if ($newPath) {
                Storage::disk('public')->delete($newPath);
            }
            throw $e;
        }

        if ($newPath && $oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return redirect()
            ->route('categories.index')
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category)
    {
        $this->authorizeAdmin();
        if (isset($category)) {
            abort_unless($category->business_id === null, 404);
        }

        if ($this->isUsed($category)) {
            return back()->withErrors([
                'delete' => 'Category is in use or has subcategories. Deactivate it instead.',
            ]);
        }

        $image = $category->image;
        $category->delete();

        if ($image) {
            Storage::disk('public')->delete($image);
        }

        return back()->with('success', 'Category deleted.');
    }

    private function isUsed(Category $category): bool
    {
        return $category->children()->exists()
            || Business::where('category_id', $category->id)->exists()
            || Product::where('category_id', $category->id)->exists()
            || Service::where('category_id', $category->id)->exists();
    }

    private function validateCategory(
        Request $request,
        ?Category $category = null
    ): array {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category_type' => [
                'required',
                Rule::in(array_keys($this->types())),
            ],
            'parent_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->whereNull('business_id')],
            'icon' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'is_active' => ['required', 'boolean'],
            'is_featured' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999999'],
        ]);

        if (! empty($data['parent_id'])) {
            $parent = Category::findOrFail($data['parent_id']);

            if (
                $parent->category_type !== $data['category_type']
                || $parent->parent_id !== null
                || ($category && $parent->id === $category->id)
            ) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Select a top-level parent from the same category type.',
                ]);
            }
        }

        return $data;
    }

    private function uniqueSlug(
        string $name,
        string $type,
        ?int $exceptId = null
    ): string {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        $count = 2;

        while (
            Category::query()->whereNull('business_id')
                ->where('category_type', $type)
                ->where('slug', $slug)
                ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
                ->exists()
        ) {
            $slug = $base.'-'.$count++;
        }

        return $slug;
    }
}
