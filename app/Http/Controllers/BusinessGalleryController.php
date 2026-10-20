<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\BusinessGallery;
use App\Services\BusinessAccessService;
use App\Services\GrowyaPlanLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class BusinessGalleryController extends Controller
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

    private function checkGallery(
        Business $business,
        BusinessGallery $gallery
    ): void {
        abort_unless(
            (int) $gallery->business_id === (int) $business->id,
            404
        );
    }

    private function types(): array
    {
        return [
            'image' => 'General Photos',
            'video' => 'Videos',
            'store' => 'Store / Office',
            'team' => 'Team Photos',
            'certificate' => 'Certificates',
        ];
    }

    public function index(Request $request, Business $business)
    {
        $this->authorizeBusiness(
            $business,
            'gallery.view'
        );

        $request->validate([
            'media_type' => [
                'nullable',
                Rule::in(array_keys($this->types())),
            ],
            'status' => [
                'nullable',
                Rule::in(['active', 'inactive']),
            ],
        ]);

        $query = BusinessGallery::query()
            ->where('business_id', $business->id);

        if ($request->filled('media_type')) {
            $query->where(
                'media_type',
                $request->input('media_type')
            );
        }

        if ($request->filled('status')) {
            $query->where(
                'is_active',
                $request->input('status') === 'active'
            );
        }

        $galleries = $query
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate(18)
            ->withQueryString();

        $counts = BusinessGallery::query()
            ->where('business_id', $business->id)
            ->selectRaw('media_type, COUNT(*) as total')
            ->groupBy('media_type')
            ->pluck('total', 'media_type');

        $total = BusinessGallery::query()
            ->where('business_id', $business->id)
            ->count();

        return view('businesses.gallery.index', [
            'business' => $business,
            'galleries' => $galleries,
            'counts' => $counts,
            'total' => $total,
            'types' => $this->types(),
        ]);
    }

    public function create(Business $business)
    {
        $this->authorizeBusiness(
            $business,
            'gallery.manage'
        );

        return view('businesses.gallery.form', [
            'business' => $business,
            'gallery' => new BusinessGallery([
                'media_type' => 'image',
                'is_active' => true,
                'sort_order' => 0,
            ]),
            'types' => $this->types(),
        ]);
    }

    public function store(Request $request, Business $business)
    {
        $this->authorizeBusiness(
            $business,
            'gallery.manage'
        );

        $data = $request->validate([
            'media_type' => [
                'required',
                Rule::in(array_keys($this->types())),
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
            'file' => [
                'required',
                'file',
                'max:102400',
            ],
        ]);

        // Image categories accept images only;
        // video category accepts supported videos only.
        $isVideo = $data['media_type'] === 'video';

        $request->validate([
            'file' => $isVideo
                ? [
                    'required',
                    File::types(['mp4', 'webm', 'mov'])
                        ->max('100mb'),
                ]
                : [
                    'required',
                    File::image()
                        ->max('10mb'),
                ],
        ]);

        $path = $request->file('file')->store(
            'business-gallery/'.$business->id,
            'public'
        );

        try {
            DB::transaction(function () use ($business, $data, $path) {
                Business::whereKey($business->id)->lockForUpdate()->firstOrFail();
                app(GrowyaPlanLimitService::class)->assertCanAdd($business, 'max_gallery');
                BusinessGallery::create([
                    'business_id' => $business->id,
                    'media_type' => $data['media_type'],
                    'file_url' => $path,
                    'thumbnail' => null,
                    'title' => $data['title'] ?? null,
                    'description' => $data['description'] ?? null,
                    'sort_order' => $data['sort_order'],
                    'is_active' => $data['is_active'],
                ]);
            });
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($path);
            throw $e;
        }

        return redirect()
            ->route('businesses.gallery.index', $business)
            ->with('success', 'Gallery media uploaded successfully.');
    }

    public function edit(
        Business $business,
        BusinessGallery $gallery
    ) {
        $this->checkGallery($business, $gallery);
        $this->authorizeBusiness(
            $business,
            'gallery.manage'
        );

        return view('businesses.gallery.form', [
            'business' => $business,
            'gallery' => $gallery,
            'types' => $this->types(),
        ]);
    }

    public function update(
        Request $request,
        Business $business,
        BusinessGallery $gallery
    ) {
        $this->checkGallery($business, $gallery);
        $this->authorizeBusiness(
            $business,
            'gallery.manage'
        );

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ]);

        // Media type and actual file remain unchanged on edit.
        $gallery->update([
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sort_order'],
            'is_active' => $data['is_active'],
        ]);

        return redirect()
            ->route('businesses.gallery.index', $business)
            ->with('success', 'Gallery details updated successfully.');
    }

    public function destroy(
        Business $business,
        BusinessGallery $gallery
    ) {
        $this->checkGallery($business, $gallery);
        $this->authorizeBusiness(
            $business,
            'gallery.manage'
        );

        $filePath = $gallery->file_url;
        $thumbPath = $gallery->thumbnail;

        $gallery->delete();

        // Only delete files created by this gallery module.
        $prefix = 'business-gallery/'.$business->id.'/';

        foreach (array_filter([$filePath, $thumbPath]) as $path) {
            if (
                str_starts_with($path, $prefix)
                && ! BusinessGallery::query()
                    ->where('file_url', $path)
                    ->orWhere('thumbnail', $path)
                    ->exists()
            ) {
                Storage::disk('public')->delete($path);
            }
        }

        return redirect()
            ->route('businesses.gallery.index', $business)
            ->with('success', 'Gallery media deleted successfully.');
    }
}
