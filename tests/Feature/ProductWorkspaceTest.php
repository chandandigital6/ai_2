<?php

use App\Models\Attribute;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Cart;
use App\Models\GrowyaCoupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\BusinessPermissionSeeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->seed(BusinessPermissionSeeder::class);
    $this->owner = User::factory()->create(['status' => 'active']);
    $this->shop = Business::withoutEvents(fn () => Business::create(['owner_id' => $this->owner->id, 'name' => 'Product Demo Store', 'slug' => 'product-demo', 'status' => 'active', 'verification_status' => 'approved']));
    $this->product = Product::create(['business_id' => $this->shop->id, 'name' => 'Cotton T-shirt', 'slug' => 'cotton-shirt', 'price' => 1000, 'offer_price' => 800, 'stock_quantity' => 20, 'stock_status' => 'in_stock', 'status' => 'active']);
    $this->size = Attribute::create(['business_id' => $this->shop->id, 'name' => 'Size', 'slug' => 'demo-size']);
    $this->medium = $this->size->values()->create(['value' => 'M', 'sort_order' => 1]);
    $this->large = $this->size->values()->create(['value' => 'L', 'sort_order' => 2]);
    $this->product->attributeValues()->sync([$this->medium->id, $this->large->id]);
    $this->path = '/businesses/'.$this->shop->id.'/products/'.$this->product->id;
    $this->actingAs($this->owner);
});
function variantPayload(array $overrides = []): array
{
    return array_merge(['name' => 'Medium', 'price' => 850, 'offer_price' => null, 'stock_quantity' => 7, 'status' => 'active'], $overrides);
}
function productPayload(array $overrides = []): array
{
    return array_merge(['name' => 'Updated shirt', 'price' => 1000, 'offer_price' => 800, 'status' => 'active', 'is_featured' => 0, 'is_trending' => 0], $overrides);
}

it('renders all product workspace sections', function (string $suffix) {
    $response = $this->get($this->path.$suffix)->assertOk();
    $response->assertSee('Attributes &amp; options', false);
    if (getenv('PRODUCT_QA_DIR')) {
        file_put_contents(getenv('PRODUCT_QA_DIR').'/'.(['' => 'overview', '/edit' => 'form', '/attributes' => 'attributes', '/variants' => 'variants', '/gallery' => 'gallery', '/tags' => 'tags', '/inventory' => 'inventory'][$suffix]).'.html', $response->getContent());
    }
})->with(['', '/edit', '/attributes', '/variants', '/gallery', '/tags', '/inventory']);

it('creates the product and options together with automatic stock availability', function () {
    $this->post('/businesses/'.$this->shop->id.'/products', productPayload(['name' => 'New shirt', 'stock_quantity' => 0, 'attribute_values_present' => 1, 'attribute_values' => [$this->medium->id], 'after_save' => 'options']))->assertRedirect();
    $p = Product::where('name', 'New shirt')->firstOrFail();
    expect($p->stock_status)->toBe('out_of_stock')->and($p->attributeValues()->count())->toBe(1);
});
it('saves variant details and its chosen values in one submission', function () {
    $this->post($this->path.'/variants', variantPayload(['attributes' => [$this->size->id => $this->medium->id]]))->assertSessionHasNoErrors()->assertRedirect();
    $v = $this->product->variants()->firstOrFail();
    expect($v->attributeValues()->pluck('attribute_values.id')->all())->toBe([$this->medium->id]);
    $this->put($this->path.'/variants/'.$v->id, variantPayload(['name' => 'Large', 'attributes' => [$this->size->id => $this->large->id]]))->assertSessionHasNoErrors();
    expect($v->fresh()->attributeValues()->pluck('attribute_values.id')->all())->toBe([$this->large->id]);
});
it('rejects duplicate combinations and values from another tenant', function () {
    $this->post($this->path.'/variants', variantPayload(['attributes' => [$this->size->id => $this->medium->id]]))->assertSessionHasNoErrors();
    $this->post($this->path.'/variants', variantPayload(['name' => 'Duplicate', 'attributes' => [$this->size->id => $this->medium->id]]))->assertSessionHasErrors('attributes');
    expect($this->product->variants()->count())->toBe(1);
    $foreign = Attribute::create(['business_id' => Business::withoutEvents(fn () => Business::create(['owner_id' => $this->owner->id, 'name' => 'Other', 'slug' => 'other', 'status' => 'active', 'verification_status' => 'approved']))->id, 'name' => 'Foreign', 'slug' => 'foreign']);
    $value = $foreign->values()->create(['value' => 'Other']);
    $this->post($this->path.'/variants', variantPayload(['attributes' => [$foreign->id => $value->id]]))->assertSessionHasErrors('attributes');
    $this->put($this->path, productPayload(['attribute_values' => [$value->id]]))->assertSessionHasErrors('attribute_values.0');
});
it('prevents removal of product values used by variants and rolls back the edit', function () {
    $v = $this->product->variants()->create(variantPayload());
    $v->attributeValues()->sync([$this->medium->id]);
    $this->put($this->path, productPayload(['attribute_values_present' => 1, 'attribute_values' => [$this->large->id]]))->assertSessionHasErrors('attribute_values');
    expect($this->product->fresh()->name)->toBe('Cotton T-shirt')->and($this->product->attributeValues()->count())->toBe(2);
    $this->put($this->path.'/attributes', ['values' => [$this->large->id]])->assertSessionHasErrors('attribute_values');
});
it('preserves legacy mappings when omitted and edits details without replacing stock', function () {
    $v = $this->product->variants()->create(variantPayload());
    $v->attributeValues()->sync([$this->medium->id]);
    $data = variantPayload(['name' => 'Renamed']);
    unset($data['stock_quantity']);
    $this->put($this->path.'/variants/'.$v->id, $data)->assertSessionHasNoErrors();
    expect($v->fresh()->stock_quantity)->toBe(7)->and($v->attributeValues()->count())->toBe(1);
    $this->put($this->path, productPayload())->assertSessionHasNoErrors();
    expect($this->product->attributeValues()->count())->toBe(2);
});
it('requires inventory permission for a direct variant stock change and records allowed adjustments', function () {
    $v = $this->product->variants()->create(variantPayload());
    $staff = User::factory()->create(['status' => 'active']);
    $member = BusinessUser::create(['business_id' => $this->shop->id, 'user_id' => $staff->id, 'role' => 'staff', 'is_active' => true]);
    $member->permissions()->sync(Permission::whereIn('name', ['products.view', 'products.update'])->pluck('id'));
    $this->actingAs($staff)->put($this->path.'/variants/'.$v->id, variantPayload(['stock_quantity' => 12]))->assertForbidden();
    expect($v->fresh()->stock_quantity)->toBe(7);
    $this->actingAs($this->owner)->put($this->path.'/variants/'.$v->id, variantPayload(['stock_quantity' => 12]))->assertSessionHasNoErrors();
    $this->assertDatabaseHas('product_stock_movements', ['product_variant_id' => $v->id, 'quantity_before' => 7, 'quantity_after' => 12]);
});
it('renders a public checkout and previews savings without consuming the coupon', function () {
    $cart = Cart::create(['business_id' => $this->shop->id, 'user_id' => $this->owner->id, 'status' => 'active']);
    $cart->cart_items()->create(['product_id' => $this->product->id, 'quantity' => 2, 'price' => 800]);
    GrowyaCoupon::create(['business_id' => $this->shop->id, 'code' => 'SAVE10', 'discount_type' => 'percent', 'discount_value' => 10, 'minimum_subtotal' => 0, 'per_user_limit' => 2, 'used_count' => 0, 'is_active' => true]);
    DB::table('growya_commerce_policies')->insert(['business_id' => $this->shop->id, 'shipping_enabled' => true, 'shipping_paise' => 5000, 'tax_enabled' => true, 'tax_basis_points' => 300, 'seller_state' => 'Uttar Pradesh']);
    $response = $this->get('/shops/'.$this->shop->id.'/checkout')->assertOk()->assertSee('Coupon code')->assertSee('Total payable')->assertSee('g-public', false)->assertDontSee('g-sidebar', false);
    if (getenv('PRODUCT_QA_DIR')) {
        file_put_contents(getenv('PRODUCT_QA_DIR').'/checkout.html', $response->getContent());
    }
    $q = $this->postJson('/shops/'.$this->shop->id.'/quote', ['state' => 'Uttar Pradesh', 'coupon_code' => 'SAVE10'])->assertOk()->assertJsonPath('data.product_savings_paise', 40000)->assertJsonPath('data.discount_paise', 16000)->assertJsonPath('data.total_paise', 153470);
    expect(GrowyaCoupon::value('used_count'))->toBe(0);
    $data = ['customer_name' => 'Buyer', 'customer_phone' => '9876543210', 'address' => '10 Main Road', 'city' => 'Kanpur', 'state' => 'Uttar Pradesh', 'pincode' => '208001', 'payment_method' => 'cod', 'coupon_code' => 'SAVE10', 'quoted_total_paise' => 153470];
    $this->post('/shops/'.$this->shop->id.'/checkout', $data)->assertSessionHasNoErrors()->assertRedirect();
    $order = Order::latest('id')->firstOrFail();
    expect($order->total_amount)->toBe('1534.70');
    $this->get('/my-orders')->assertOk()->assertSee('g-public', false)->assertDontSee('g-sidebar', false);
    $this->get('/shops/'.$this->shop->id.'/cart')->assertOk()->assertSee('g-public', false)->assertDontSee('g-sidebar', false);
    $this->get('/my-orders/'.$order->id)->assertOk()->assertSee('g-public', false)->assertDontSee('g-sidebar', false);
    expect(GrowyaCoupon::value('used_count'))->toBe(1);
});
it('rejects a tampered checkout total and invalid coupon without creating an order', function () {
    $cart = Cart::create(['business_id' => $this->shop->id, 'user_id' => $this->owner->id, 'status' => 'active']);
    $cart->cart_items()->create(['product_id' => $this->product->id, 'quantity' => 1, 'price' => 800]);
    $this->postJson('/shops/'.$this->shop->id.'/quote', ['state' => 'Uttar Pradesh', 'coupon_code' => 'NOPE'])->assertUnprocessable();
    $this->post('/shops/'.$this->shop->id.'/checkout', ['customer_name' => 'Buyer', 'customer_phone' => '9876543210', 'address' => 'Main Road', 'city' => 'Kanpur', 'state' => 'Uttar Pradesh', 'pincode' => '208001', 'payment_method' => 'cod', 'quoted_total_paise' => 1])->assertSessionHasErrors('cart');
    expect(Order::count())->toBe(0)->and($this->product->fresh()->stock_quantity)->toBe(20);
});
