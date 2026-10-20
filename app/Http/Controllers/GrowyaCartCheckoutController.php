<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\City;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\State;
use App\Models\User;
use App\Models\UserAddress;
use App\Services\GrowyaOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GrowyaCartCheckoutController extends Controller
{
    private function business(int $id): Business
    {
        return Business::query()->whereKey($id)->where('status', 'active')->where('verification_status', 'approved')->firstOrFail();
    }

    private function cart(Business $business): Cart
    {
        return DB::transaction(function () use ($business) {
            Business::query()->whereKey($business->id)->lockForUpdate()->firstOrFail();

            return Cart::firstOrCreate(['business_id' => $business->id, 'user_id' => auth()->id(), 'status' => 'active'], ['session_id' => null]);
        }, 3);
    }

    public function index(Business $business)
    {
        $business = $this->business($business->id);
        $cart = $this->cart($business)->load('cart_items.product', 'cart_items.variant');

        return view('checkout.cart', compact('business', 'cart'));
    }

    public function add(Request $request, Business $business)
    {
        $business = $this->business($business->id);
        $data = $request->validate([
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('business_id', $business->id)->where('status', 'active')->whereNull('deleted_at')],
            'variant_id' => ['nullable', 'integer', Rule::exists('product_variants', 'id')->where('status', 'active')],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);
        $product = Product::where('business_id', $business->id)->findOrFail($data['product_id']);
        $variant = null;
        if (! empty($data['variant_id'])) {
            $variant = ProductVariant::where('product_id', $product->id)->findOrFail($data['variant_id']);
        } elseif ($product->variants()->exists()) {
            throw ValidationException::withMessages(['variant_id' => 'Select a product variant.']);
        }
        $price = $variant ? ($variant->offer_price ?? $variant->price) : ($product->offer_price ?? $product->price);
        $cart = $this->cart($business);
        // MySQL NULL in a composite UNIQUE can repeat: locate by null variant explicitly.
        $item = CartItem::where('cart_id', $cart->id)->where('product_id', $product->id)->where('variant_id', $variant?->id)->first();
        $newQty = ($item?->quantity ?? 0) + (int) $data['quantity'];
        if ($newQty > 100 || $newQty > (int) ($variant?->stock_quantity ?? $product->stock_quantity)) {
            throw ValidationException::withMessages(['quantity' => 'Requested quantity exceeds available stock or cart limit.']);
        }
        if ($item) {
            $item->update(['quantity' => $newQty, 'price' => $price]);
        } else {
            CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'variant_id' => $variant?->id, 'quantity' => $newQty, 'price' => $price]);
        }

        return redirect()->route('growya.cart.index', $business)->with('success', 'Product added to cart.');
    }

    public function update(Request $request, Business $business, CartItem $item)
    {
        $business = $this->business($business->id);
        $cart = $this->cart($business);
        abort_unless((int) $item->cart_id === (int) $cart->id, 404);
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:100']]);
        $stock = $item->variant ?: $item->product;
        if (! $stock || $stock->stock_quantity < $data['quantity']) {
            throw ValidationException::withMessages(['quantity' => 'Insufficient stock.']);
        }
        $item->update(['quantity' => $data['quantity']]);

        return back()->with('success', 'Cart updated.');
    }

    public function remove(Business $business, CartItem $item)
    {
        $business = $this->business($business->id);
        abort_unless((int) $item->cart_id === (int) $this->cart($business)->id, 404);
        $item->delete();

        return back()->with('success', 'Item removed.');
    }

    public function checkoutForm(Business $business)
    {
        $business = $this->business($business->id);
        $cart = $this->cart($business)->load('cart_items.product', 'cart_items.variant');
        if ($cart->cart_items->isEmpty()) {
            return redirect()->route('growya.cart.index', $business);
        }
        $addresses = UserAddress::where('user_id', auth()->id())->with('state:id,name', 'city:id,name')->orderByDesc('is_default')->latest('id')->get();

        return view('checkout.checkout', compact('business', 'cart', 'addresses'));
    }

    public function place(Request $request, Business $business, GrowyaOrderService $service)
    {
        $business = $this->business($business->id);
        $data = $request->validate([
            'quoted_total_paise' => ['nullable', 'integer', 'min:0'],
            'address_id' => ['nullable', 'integer', Rule::exists('user_addresses', 'id')->where('user_id', auth()->id())],
            'customer_name' => ['required_without:address_id', 'nullable', 'string', 'max:255'],
            'customer_phone' => ['required_without:address_id', 'nullable', 'regex:/^[6-9][0-9]{9}$/'],
            'address' => ['required_without:address_id', 'nullable', 'string', 'max:500'],
            'city' => ['required_without:address_id', 'nullable', 'string', 'max:100'],
            'state' => ['required_without:address_id', 'nullable', 'string', 'max:100'],
            'pincode' => ['required_without:address_id', 'nullable', 'regex:/^[1-9][0-9]{5}$/'],
            'payment_method' => ['required', Rule::in(config('growya_extensions.razorpay.enabled') ? ['cod', 'upi_manual', 'razorpay'] : ['cod', 'upi_manual'])],
            'notes' => ['nullable', 'string', 'max:2000'],
            'coupon_code' => ['nullable', 'string', 'max:40'],
            'save_address' => ['nullable', 'boolean'],
            'address_label' => ['nullable', Rule::in(['Home', 'Office', 'Other'])],
        ]);
        if (! empty($data['address_id'])) {
            $saved = UserAddress::where('user_id', auth()->id())->with('city:id,name', 'state:id,name')->findOrFail($data['address_id']);
            if (! $saved->city || ! $saved->state) {
                throw ValidationException::withMessages(['address_id' => 'Saved address is incomplete. Please edit it first.']);
            }
            $data['customer_name'] = $saved->recipient_name;
            $data['customer_phone'] = $saved->phone;
            $data['address'] = $saved->address;
            $data['city'] = $saved->city->name;
            $data['state'] = $saved->state->name;
            $data['pincode'] = $saved->pincode;
        }
        // Order service writes an immutable-by-design address snapshot using the
        // resolved values. Client-supplied manual fields cannot override saved IDs.
        $order = $service->checkout($business, $this->cart($business), $data);
        if (empty($data['address_id']) && ! empty($data['save_address'])) {
            // Saving a new address is optional; an order must not be rolled back
            // because the city name cannot be matched to a master record.
            $state = State::where('name', $data['state'])->where('is_active', true)->first();
            $city = $state ? City::where('state_id', $state->id)->where('name', $data['city'])->where('is_active', true)->first() : null;
            if ($state && $city) {
                DB::transaction(function () use ($data, $state, $city) {
                    User::whereKey(auth()->id())->lockForUpdate()->firstOrFail();
                    $has = UserAddress::where('user_id', auth()->id())->exists();
                    UserAddress::create([
                        'user_id' => auth()->id(),
                        'label' => $data['address_label'] ?? 'Home',
                        'recipient_name' => $data['customer_name'],
                        'phone' => $data['customer_phone'],
                        'address' => $data['address'],
                        'state_id' => $state->id,
                        'city_id' => $city->id,
                        'pincode' => $data['pincode'],
                        'is_default' => ! $has,
                    ]);
                }, 3);
            }
        }

        return redirect()->route('growya.orders.show', $order)->with('success', 'Order placed. Payment is pending.');
    }

    public function myOrders()
    {
        $orders = Order::query()->where('customer_id', auth()->id())->latest('id')->paginate(15);

        return view('checkout.my-orders', compact('orders'));
    }

    public function order(Order $order)
    {
        abort_unless((int) $order->customer_id === (int) auth()->id(), 403);
        $order->load('items', 'business');

        return view('checkout.order', compact('order'));
    }
}
