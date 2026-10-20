@php
    $lines = $cart->cart_items->map(function ($item) {
        $unit = (float) ($item->variant?->offer_price
            ?? $item->variant?->price
            ?? $item->product?->offer_price
            ?? $item->product?->price
            ?? 0);

        return (object) [
            'item' => $item,
            'unit' => $unit,
            'total' => $unit * (int) $item->quantity,
        ];
    });

    $subtotal = $lines->sum('total');

    // Default saved address (old input ko priority)
    $selectedAddressId = old('address_id', $addresses->firstWhere('is_default', true)?->id);
    $useSaved = $addresses->isNotEmpty() && filled($selectedAddressId);

    $fields = [
        'customer_name' => ['Full Name', 'text', 'name'],
        'customer_phone' => ['Mobile Number', 'tel', 'tel'],
        'address' => ['Street / House Address', 'text', 'street-address'],
        'city' => ['City', 'text', 'address-level2'],
        'state' => ['State', 'text', 'address-level1'],
        'pincode' => ['PIN Code', 'text', 'postal-code'],
    ];

    $inputClass = 'w-full rounded-lg border p-3 text-zinc-900 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-100 dark:border-zinc-700';
@endphp

<x-layouts::app title="Checkout">
    <div id="main-content" class="mx-auto max-w-6xl space-y-6 p-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-3xl font-bold">Checkout</h1>
                <p class="text-sm text-zinc-500">{{ $business->name }}</p>
            </div>
            <a class="text-sm font-semibold text-emerald-700 underline dark:text-emerald-400"
                href="{{ route('growya.cart.index', $business) }}">← Back to Cart</a>
        </div>

        @if ($errors->any())
            <div class="rounded-xl bg-red-50 p-4 text-sm text-red-800 dark:bg-red-950 dark:text-red-200" role="alert">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form id="checkout-order" method="POST" action="{{ route('growya.checkout.place', $business) }}" class="grid gap-6 lg:grid-cols-3">
            @csrf

            <div class="space-y-6 lg:col-span-2">

                {{-- Delivery address --}}
                <section class="space-y-4 rounded-2xl border bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 class="text-lg font-bold">1. Delivery Address</h2>
                        <a class="text-sm text-emerald-700 underline dark:text-emerald-400"
                            href="{{ route('growya.addresses.index') }}">Manage Addresses</a>
                    </div>

                    @if ($addresses->isNotEmpty())
                        <div>
                            <label class="mb-1 block text-sm font-medium" for="address_id">Saved address</label>
                            <select id="address_id" name="address_id" class="{{ $inputClass }}"
                                onchange="document.getElementById('manual-address').hidden = (this.value !== '')">
                                <option value="">Enter a new address</option>
                                @foreach ($addresses as $saved)
                                    <option value="{{ $saved->id }}" @selected((string) $selectedAddressId === (string) $saved->id)>
                                        {{ $saved->label }} — {{ $saved->recipient_name }} —
                                        {{ \Illuminate\Support\Str::limit($saved->address, 55) }} ({{ $saved->pincode }})
                                    </option>
                                @endforeach
                            </select>
                            @error('address_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    <div id="manual-address" class="grid gap-4 sm:grid-cols-2" @if ($useSaved) hidden @endif>
                        @foreach ($fields as $key => [$label, $type, $autocomplete])
                            <div class="{{ in_array($key, ['address']) ? 'sm:col-span-2' : '' }}">
                                <label for="{{ $key }}" class="mb-1 block text-sm font-medium">{{ $label }}</label>
                                <input id="{{ $key }}" name="{{ $key }}" type="{{ $type }}"
                                    autocomplete="{{ $autocomplete }}"
                                    @if ($key === 'customer_phone') inputmode="numeric" maxlength="10" @endif
                                    @if ($key === 'pincode') inputmode="numeric" maxlength="6" @endif
                                    value="{{ old($key, $key === 'customer_name' ? auth()->user()->name : '') }}"
                                    class="{{ $inputClass }}">
                                @error($key) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        @endforeach

                        <label class="flex items-start gap-2 text-sm sm:col-span-2">
                            <input type="checkbox" name="save_address" value="1" @checked(old('save_address'))
                                class="mt-0.5 h-4 w-4 rounded border-zinc-300 text-emerald-700">
                            <span>Is address ko address book mein save karo
                                <span class="block text-xs text-zinc-500">City aur state master list se match hone par hi save hoga.</span>
                            </span>
                        </label>

                        <div>
                            <label for="address_label" class="mb-1 block text-sm font-medium">Address Label</label>
                            <select id="address_label" name="address_label" class="{{ $inputClass }}">
                                @foreach (['Home', 'Office', 'Other'] as $label)
                                    <option value="{{ $label }}" @selected(old('address_label', 'Home') === $label)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </section>

                {{-- Payment --}}
                <section class="space-y-4 rounded-2xl border bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <h2 class="text-lg font-bold">2. Payment Method</h2>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border p-4 has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50 dark:border-zinc-700 dark:has-[:checked]:bg-emerald-950">
                            <input type="radio" name="payment_method" value="cod" class="mt-1 text-emerald-700"
                                @checked(old('payment_method', 'cod') === 'cod')>
                            <span><strong class="block">Cash on Delivery</strong>
                                <span class="text-xs text-zinc-500">Delivery par payment karo.</span></span>
                        </label>

                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border p-4 has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50 dark:border-zinc-700 dark:has-[:checked]:bg-emerald-950">
                            <input type="radio" name="payment_method" value="upi_manual" class="mt-1 text-emerald-700"
                                @checked(old('payment_method') === 'upi_manual')>
                            <span><strong class="block">Manual UPI</strong>
                                <span class="text-xs text-zinc-500">Seller payment verify karega.</span></span>
                        </label>
                    </div>

                    <p class="text-xs text-amber-700 dark:text-amber-400">Manual UPI payments automatically verify nahi hote.</p>
                    @if(config('growya_extensions.razorpay.enabled'))<label class="flex gap-3 p-4 border rounded-xl"><input type="radio" name="payment_method" value="razorpay" @checked(old('payment_method')==='razorpay')>Pay online via Razorpay</label>@endif
                    @error('payment_method') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </section>

                {{-- Notes --}}
                <section class="space-y-2 rounded-2xl border bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <label for="notes" class="text-lg font-bold">3. Notes <span class="text-sm font-normal text-zinc-500">(optional)</span></label>
                    <textarea id="notes" name="notes" rows="3" maxlength="2000" class="{{ $inputClass }}"
                        placeholder="Delivery ke liye koi instruction...">{{ old('notes') }}</textarea>
                    @error('notes') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </section>
            </div>

            {{-- Summary --}}
            <aside class="h-fit space-y-4 rounded-2xl border bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 lg:sticky lg:top-6">
                <h2 class="text-lg font-bold">Order Summary</h2>

                <ul class="divide-y text-sm dark:divide-zinc-700">
                    @foreach ($lines as $line)
                        <li class="flex justify-between gap-3 py-2">
                            <span class="min-w-0">
                                {{ $line->item->product?->name }}
                                @if ($line->item->variant)
                                    <span class="text-zinc-500">· {{ $line->item->variant->name }}</span>
                                @endif
                                <span class="text-zinc-500">× {{ $line->item->quantity }}</span>
                            </span>
                            <span class="shrink-0">₹{{ number_format($line->total, 2) }}</span>
                        </li>
                    @endforeach
                </ul>

                {{-- Coupon --}}
                <div class="border-t pt-4 dark:border-zinc-700">
                    <label for="coupon_code" class="mb-1 block text-sm font-semibold">Coupon code</label>
                    <input id="coupon_code" name="coupon_code" type="text" maxlength="40" autocomplete="off"
                        value="{{ old('coupon_code') }}" placeholder="e.g. WELCOME10"
                        oninput="this.value = this.value.toUpperCase().replace(/[^A-Z0-9_-]/g, '')"
                        class="{{ $inputClass }} uppercase tracking-wider">
                    @error('coupon_code') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    <p class="mt-1 text-xs text-zinc-500">Order place karte waqt coupon validate hoga aur discount order mein lagega.</p>
                </div>

                <dl class="space-y-2 border-t pt-4 text-sm dark:border-zinc-700">
                    <div class="flex justify-between">
                        <dt class="text-zinc-500">Subtotal</dt>
                        <dd>₹{{ number_format($subtotal, 2) }}</dd>
                    </div>
                    <div class="flex justify-between text-lg font-bold">
                        <dt>Merchandise estimate</dt>
                        <dd>₹{{ number_format($subtotal, 2) }}</dd>
                    </div>
                </dl>

                @php $commercePolicy=app(\App\Services\Extensions\CommerceService::class)->policy($business->id); @endphp
                @if($commercePolicy?->shipping_enabled || $commercePolicy?->tax_enabled)<div class="text-sm p-3 border rounded-xl">@if($commercePolicy?->shipping_enabled)<p>Shipping: ₹{{ number_format($commercePolicy->shipping_paise/100,2) }}@if($commercePolicy->free_above_paise!==null) · Free above ₹{{ number_format($commercePolicy->free_above_paise/100,2) }}@endif</p>@endif @if($commercePolicy?->tax_enabled)<p>GST: {{ number_format($commercePolicy->tax_basis_points/100,2) }}% added to merchandise after discount and shipping.</p>@endif<p>Final charges are shown on the order before payment.</p></div>@endif
                <button type="submit"
                    class="w-full rounded-xl bg-emerald-700 p-3 font-bold text-white transition hover:bg-emerald-800">
                    Place Order
                </button>
            </aside>
        </form>
    </div>
<script>document.getElementById('checkout-order').addEventListener('submit',async function(e){if(this.dataset.confirmed==='yes')return;e.preventDefault();const form=this,btn=this.querySelector('button[type=submit]');btn.disabled=true;try{const response=await fetch(@json(route('extensions.checkout.quote',$business)),{method:'POST',headers:{'Accept':'application/json','X-CSRF-TOKEN':@json(csrf_token())},body:new FormData(form)});const result=await response.json();if(!response.ok)throw Error(Object.values(result.errors||{}).flat().join(' ')||result.message||'Unable to calculate total');const q=result.data,m=v=>'₹'+(v/100).toFixed(2);if(confirm('Review order: Merchandise '+m(q.subtotal_paise)+' − Discount '+m(q.discount_paise)+' + Shipping '+m(q.shipping_paise)+' + GST '+m(q.tax_paise)+' = Total '+m(q.total_paise)+'. Place this order?')){const amount=document.createElement('input');amount.type='hidden';amount.name='quoted_total_paise';amount.value=q.total_paise;form.append(amount);form.dataset.confirmed='yes';form.requestSubmit();}}catch(err){alert(err.message);}finally{btn.disabled=false;}});</script>
</x-layouts::app>