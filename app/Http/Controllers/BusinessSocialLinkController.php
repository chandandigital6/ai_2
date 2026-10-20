<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Services\BusinessAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BusinessSocialLinkController extends Controller
{
    private function platforms(): array
    {
        return [
            'facebook' => 'Facebook',
            'instagram' => 'Instagram',
            'youtube' => 'YouTube',
            'whatsapp' => 'WhatsApp',
            'linkedin' => 'LinkedIn',
            'twitter' => 'X (Twitter)',
            'website' => 'Website',
            'google_maps' => 'Google Maps',
        ];
    }

    public function index(Business $business)
    {
        abort_unless(app(BusinessAccessService::class)->can(auth()->user(), $business, 'business.social.manage'), 403);

        $platforms = $this->platforms();

        $links = $business->socialLinks()
            ->get()
            ->keyBy('platform');

        return view(
            'businesses.social_links.index',
            compact('business', 'platforms', 'links')
        );
    }

    public function store(Request $request, Business $business)
    {
        abort_unless(app(BusinessAccessService::class)->can(auth()->user(), $business, 'business.social.manage'), 403);

        $platforms = $this->platforms();

        $data = $request->validate([
            'links' => ['required', 'array'],
            'links.*.url' => [
                'nullable',
                'string',
                'url:http,https',
                'max:2000',
            ],
            'links.*.username' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        $submitted = $data['links'];

        foreach (array_keys($submitted) as $platform) {
            if (! array_key_exists($platform, $platforms)) {
                throw ValidationException::withMessages([
                    'links' => 'Invalid social platform.',
                ]);
            }
        }

        DB::transaction(function () use (
            $business,
            $platforms,
            $submitted
        ) {
            foreach ($platforms as $platform => $label) {

                $link = $submitted[$platform] ?? [];

                $url = trim((string) ($link['url'] ?? ''));
                $username = trim(
                    (string) ($link['username'] ?? '')
                );

                if ($url === '') {
                    // Clearing a URL removes the saved platform link.
                    $business->socialLinks()
                        ->where('platform', $platform)
                        ->delete();

                    continue;
                }

                $business->socialLinks()->updateOrCreate(
                    ['platform' => $platform],
                    [
                        'url' => $url,
                        'username' => $username ?: null,
                    ]
                );
            }
        });

        return redirect()
            ->route('businesses.social-links.index', $business)
            ->with(
                'success',
                'Business social links saved successfully.'
            );
    }
}
