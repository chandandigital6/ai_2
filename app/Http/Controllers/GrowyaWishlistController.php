<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Product;
use App\Models\ProductFavourite;
use App\Models\Service;
use App\Models\User;
use App\Models\WishlistInterest;
use App\Services\BusinessAccessService;
use App\Services\GrowyaNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class GrowyaWishlistController extends Controller
{
    private function resolveItem(string $type, int $id): Product|Service
    {
        abort_unless(in_array($type, ['product', 'service'], true), 404);
        $item = $type === 'product' ? Product::with('business')->findOrFail($id) : Service::with('business')->findOrFail($id);
        abort_unless($item->status === 'active' && $item->business && $item->business->status === 'active'
            && $item->business->verification_status === 'approved', 404);

        return $item;
    }

    public function mine(Request $request)
    {
        $uid = $request->user()->id;
        $products = ProductFavourite::with('product.business')->where('user_id', $uid)->latest()->get();
        $services = DB::table('service_favourites')->where('user_id', $uid)->orderByDesc('id')->get();
        $serviceModels = Service::with('business')->whereIn('id', $services->pluck('service_id'))->get()->keyBy('id');
        $interests = WishlistInterest::where('user_id', $uid)->get()->keyBy(fn ($i) => $i->item_type.':'.$i->item_id);

        return view('wishlist.mine', compact('products', 'services', 'serviceModels', 'interests'));
    }

    public function add(Request $request, string $type, int $id, GrowyaNotificationService $notifications)
    {
        $item = $this->resolveItem($type, $id);
        $user = $request->user();
        $data = $request->validate(['contact_consent' => ['nullable', 'boolean']]);
        $consent = (bool) ($data['contact_consent'] ?? false);
        $wasCreated = false;
        DB::transaction(function () use ($type, $id, $user, $item, $consent, &$wasCreated) {
            if ($type === 'product') {
                ProductFavourite::firstOrCreate(['product_id' => $id, 'user_id' => $user->id]);
            } else {
                DB::table('service_favourites')->insertOrIgnore([
                    'service_id' => $id, 'user_id' => $user->id, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            $interest = WishlistInterest::firstOrCreate(
                ['user_id' => $user->id, 'item_type' => $type, 'item_id' => $id],
                ['business_id' => $item->business_id, 'contact_consent' => $consent, 'status' => 'new']
            );
            $wasCreated = $interest->wasRecentlyCreated;
            if (! $wasCreated && $consent && ! $interest->contact_consent) {
                $interest->update(['contact_consent' => true]);
            }
        });
        if ($wasCreated) {
            // Notifications are best-effort: never undo customer's saved item because delivery failed.
            try {
                $recipientIds = array_unique(array_merge($notifications->ownerOf((int) $item->business_id), $notifications->superAdmins()));
                $notifications->send($recipientIds, 'wishlist_interest', 'New wishlist interest',
                    'A customer saved '.$type.': '.$item->name,
                    '/businesses/'.$item->business_id.'/wishlist-interests', (int) $item->business_id);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return back()->with('success', 'Added to wishlist.');
    }

    public function remove(Request $request, string $type, int $id)
    {
        abort_unless(in_array($type, ['product', 'service'], true), 404);
        $uid = $request->user()->id;
        DB::transaction(function () use ($type, $id, $uid) {
            if ($type === 'product') {
                ProductFavourite::where('user_id', $uid)->where('product_id', $id)->delete();
            } else {
                DB::table('service_favourites')->where('user_id', $uid)->where('service_id', $id)->delete();
            }
            WishlistInterest::where('user_id', $uid)->where('item_type', $type)->where('item_id', $id)->delete();
        });

        return back()->with('success', 'Removed from wishlist.');
    }

    private function authorizeBusiness(User $user, Business $business, BusinessAccessService $access): void
    {
        abort_unless($access->can($user, $business, 'wishlist.view'), 403);
        // Restrict to owner and super admin until business-wise advanced permissions are implemented.

    }

    public function seller(Request $request, Business $business, BusinessAccessService $access)
    {
        $this->authorizeBusiness($request->user(), $business, $access);
        $status = $request->query('status');
        $rows = WishlistInterest::with(['user', 'updater'])
            ->where('business_id', $business->id)
            ->when(in_array($status, ['new', 'contacted', 'follow_up', 'converted', 'closed'], true), fn ($q) => $q->where('status', $status))
            ->latest('id')->paginate(25)->withQueryString();
        $products = Product::whereIn('id', $rows->getCollection()->where('item_type', 'product')->pluck('item_id'))->pluck('name', 'id');
        $services = Service::whereIn('id', $rows->getCollection()->where('item_type', 'service')->pluck('item_id'))->pluck('name', 'id');

        return view('wishlist.seller', compact('business', 'rows', 'products', 'services'));
    }

    public function admin(Request $request)
    {
        abort_unless($request->user()->hasRole('super_admin', 'web'), 403);
        $rows = WishlistInterest::with(['user', 'business', 'updater'])->latest('id')->paginate(30);
        $products = Product::whereIn('id', $rows->getCollection()->where('item_type', 'product')->pluck('item_id'))->pluck('name', 'id');
        $services = Service::whereIn('id', $rows->getCollection()->where('item_type', 'service')->pluck('item_id'))->pluck('name', 'id');

        return view('wishlist.seller', ['business' => null, 'rows' => $rows, 'products' => $products, 'services' => $services]);
    }

    public function update(Request $request, Business $business, WishlistInterest $interest, BusinessAccessService $access)
    {
        $this->authorizeBusiness($request->user(), $business, $access);
        abort_unless((int) $interest->business_id === (int) $business->id, 404);

        return $this->saveUpdate($request, $interest);
    }

    public function adminUpdate(Request $request, WishlistInterest $interest)
    {
        abort_unless($request->user()->hasRole('super_admin', 'web'), 403);

        return $this->saveUpdate($request, $interest);
    }

    private function saveUpdate(Request $request, WishlistInterest $interest)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['new', 'contacted', 'follow_up', 'converted', 'closed'])],
            'seller_note' => ['nullable', 'string', 'max:3000'],
            'follow_up_at' => ['nullable', 'date'],
        ]);
        $interest->update($data + ['updated_by' => $request->user()->id]);

        return back()->with('success','Follow-up updated.');
    }
}
