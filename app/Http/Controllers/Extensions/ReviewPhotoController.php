<?php

namespace App\Http\Controllers\Extensions;

use App\Models\Review;
use App\Services\BusinessAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ReviewPhotoController extends ExtensionController
{
    public function store(Request $r, Review $review)
    {
        abort_unless((int) $review->user_id === (int) $r->user()->id, 403);
        $r->validate(['images' => ['required', 'array', 'min:1', 'max:5'], 'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072']]);
        $paths = [];
        try {
            DB::transaction(function () use ($r, $review, &$paths) {
                $review = Review::whereKey($review->id)->lockForUpdate()->firstOrFail();
                if ($review->review_images()->count() + count($r->file('images')) > 5) {
                    throw ValidationException::withMessages(['images' => 'Maximum five photos per review.']);
                }
                foreach ($r->file('images') as $file) {
                    $path = $file->store('review-photos', 'local');
                    $paths[] = $path;
                    $review->review_images()->create(['image' => $path]);
                }
                $review->update(['status' => 'pending']);
            }, 3);
        } catch (\Throwable $e) {
            foreach ($paths as $path) {
                Storage::disk('local')->delete($path);
            }throw $e;
        }

        return $this->reply($r, ['images' => $review->fresh()->review_images->map(fn ($i) => ['id' => $i->id, 'url' => url('/review-photos/'.$i->id)])], 201);
    }

    public function file(Request $r, int $photo)
    {
        $row = DB::table('review_images')->find($photo);
        abort_unless($row, 404);
        $review = Review::with('business')->findOrFail($row->review_id);
        $public = $review->status === 'approved' && $review->business?->status === 'active' && $review->business?->verification_status === 'approved';
        $own = $r->user() && (int) $r->user()->id === (int) $review->user_id;
        $manage = $r->user() && app(BusinessAccessService::class)->can($r->user(), $review->business, 'reviews.view');
        abort_unless($public || $own || $manage || $r->user()?->hasRole('super_admin', 'web'), 404);
        abort_unless(Storage::disk('local')->exists($row->image), 404);

        return Storage::disk('local')->response($row->image, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function destroy(Request $r, Review $review, int $photo)
    {
        abort_unless((int) $review->user_id === (int) $r->user()->id, 403);
        $row = $review->review_images()->findOrFail($photo);
        $path = $row->image;
        $row->delete();
        Storage::disk('local')->delete($path);

        return $this->reply($r, ['removed' => true]);
    }
}
