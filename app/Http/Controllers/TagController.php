<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TagController extends Controller
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

        $tags = Tag::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->search);

                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('tags.index', compact('tags'));
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tags', 'name'),
            ],
        ]);

        Tag::create([
            'name' => trim($data['name']),
            'slug' => $this->uniqueSlug($data['name']),
        ]);

        return back()->with(
            'success',
            'Tag created successfully.'
        );
    }

    public function update(Request $request, Tag $tag)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tags', 'name')->ignore($tag->id),
            ],
        ]);

        $name = trim($data['name']);

        $tag->update([
            'name' => $name,
            'slug' => $tag->name !== $name
                ? $this->uniqueSlug($name, $tag->id)
                : $tag->slug,
        ]);

        return back()->with(
            'success',
            'Tag updated successfully.'
        );
    }

    public function destroy(Tag $tag)
    {
        $this->authorizeAdmin();

        // Product में इस्तेमाल हो रहा Tag Delete नहीं होगा.
        if ($tag->products()->exists()) {
            return back()->withErrors([
                'tag' => 'This tag is used by products. Remove it from those products first.',
            ]);
        }

        $tag->delete();

        return back()->with(
            'success',
            'Tag deleted successfully.'
        );
    }

    private function uniqueSlug(
        string $name,
        ?int $ignoreId = null
    ): string {
        $base = Str::slug(trim($name)) ?: 'tag';
        $slug = $base;
        $number = 2;

        while (
            Tag::query()
                ->where('slug', $slug)
                ->when(
                    $ignoreId,
                    fn ($q) => $q->where('id', '!=', $ignoreId)
                )
                ->exists()
        ) {
            $slug = $base . '-' . $number++;
        }

        return $slug;
    }
}
