<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Service;
use App\Models\ServiceImage;
use App\Services\BusinessAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ServiceImageController extends Controller
{
    public function __construct(
        private BusinessAccessService $access
    ) {}

    private function authorizeService(
        Business $business,
        Service $service
    ): void {
        abort_unless(
            (int) $service->business_id === (int) $business->id,
            404
        );

        abort_unless(
            auth()->check()
            && $this->access->can(
                auth()->user(),
                $business,
                'services.update'
            ),
            403
        );
    }

    public function store(
        Request $request,
        Business $business,
        Service $service
    ) {
        $this->authorizeService($business, $service);

        $data = $request->validate([
            'images' => [
                'required',
                'array',
                'min:1',
                'max:10',
            ],

            'images.*' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:3072',
            ],
        ]);

        $paths = [];

        try {
            foreach ($request->file('images', []) as $file) {
                $paths[] = $file->store(
                    "businesses/{$business->id}/services/{$service->id}/gallery",
                    'public'
                );
            }

            DB::transaction(function () use ($service, $paths) {
                $nextSort = (int) $service->images()
                    ->max('sort_order') + 1;

                foreach ($paths as $path) {
                    $service->images()->create([
                        'image' => $path,
                        'sort_order' => $nextSort++,
                    ]);
                }

                if (!$service->featured_image && count($paths) > 0) {
                    $service->update([
                        'featured_image' => $paths[0],
                    ]);
                }
            });
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($paths);
            throw $e;
        }

        return back()->with(
            'success',
            'Service images uploaded successfully.'
        );
    }

    public function featured(
        Business $business,
        Service $service,
        ServiceImage $image
    ) {
        $this->authorizeService($business, $service);

        abort_unless(
            (int) $image->service_id === (int) $service->id,
            404
        );

        $service->update([
            'featured_image' => $image->image,
        ]);

        return back()->with(
            'success',
            'Featured image updated.'
        );
    }

    public function destroy(
        Business $business,
        Service $service,
        ServiceImage $image
    ) {
        $this->authorizeService($business, $service);

        abort_unless(
            (int) $image->service_id === (int) $service->id,
            404
        );

        $path = $image->image;

        DB::transaction(function () use ($service, $image, $path) {
            $image->delete();

            if ($service->featured_image === $path) {
                $replacement = $service->images()
                    ->orderBy('sort_order')
                    ->first();

                $service->update([
                    'featured_image' => $replacement?->image,
                ]);
            }
        });

        // Delete only if no longer referenced.
        $stillUsed = $service->featured_image === $path
            || $service->images()
                ->where('image', $path)
                ->exists();

        if (!$stillUsed) {
            Storage::disk('public')->delete($path);
        }

        return back()->with(
            'success',
            'Service image deleted.'
        );
    }
}
