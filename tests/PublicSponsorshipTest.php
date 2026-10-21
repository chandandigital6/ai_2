<?php

use App\Models\Business;
use App\Models\GrowyaPaidPromotion;
use App\Models\GrowyaPromotionPackage;
use App\Models\Product;
use App\Models\User;
use App\Services\GrowyaPaidPromotionService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
    Http::preventStrayRequests();
    $this->owner = User::factory()->create(['status' => 'active']);
});
function publicShop($test, string $name, array $data = []): Business
{
    return Business::withoutEvents(fn () => Business::create(array_merge(['owner_id' => $test->owner->id, 'name' => $name, 'slug' => Str::slug($name).'-'.Str::random(6), 'status' => 'active', 'verification_status' => 'approved'], $data)));
}
function publicProduct(Business $business, string $name, array $data = []): Product
{
    return Product::withoutEvents(fn () => Product::create(array_merge(['business_id' => $business->id, 'name' => $name, 'slug' => Str::slug($name).'-'.Str::random(6), 'price' => 100, 'stock_status' => 'in_stock', 'stock_quantity' => 10, 'status' => 'active', 'is_featured' => false], $data)));
}
function publicSponsor($test, Business $business, ?Product $product = null, array $changes = []): GrowyaPaidPromotion
{
    $package = GrowyaPromotionPackage::create(['name' => 'Sponsored test', 'target_type' => $product ? 'product' : 'business', 'duration_days' => 30, 'price' => 100, 'is_active' => true]);

    return GrowyaPaidPromotion::create(array_merge(['package_id' => $package->id, 'business_id' => $business->id, 'product_id' => $product?->id, 'requested_by' => $test->owner->id, 'status' => 'active', 'payment_status' => 'verified', 'starts_at' => now()->subDay(), 'ends_at' => now()->addDays(29)], $changes));
}

it('prioritizes sponsored businesses before pagination under every marketplace sort', function (string $sort) {
    $sponsored = publicShop($this, 'ZZ Sponsored Old Store', ['views_count' => 0]);
    publicSponsor($this, $sponsored);
    for ($i = 0; $i < 14; $i++) {
        publicShop($this, 'AA Ordinary '.$i, ['views_count' => 1000]);
    }
    $response = $this->get('/marketplace?sort='.$sort)->assertOk()->assertViewHas('paidPromotions');
    expect($response->viewData('businesses')->first()->id)->toBe($sponsored->id);
    expect($response->viewData('businesses')->total())->toBe(15);
    $response->assertSee('Sponsored')->assertSee('data-sponsored="true"', false);
    $page2 = $this->get('/marketplace?sort='.$sort.'&page=2')->assertOk();
    expect($page2->viewData('businesses')->pluck('id')->contains($sponsored->id))->toBeFalse();
})->with(['newest', 'popular', 'name']);

it('boosts only active paid verified in-date campaigns', function (array $change) {
    $shop = publicShop($this, 'Old promotion');
    publicSponsor($this, $shop, null, $change);
    $ordinary = publicShop($this, 'New ordinary');
    $rows = Business::sponsoredFirst()->latest('id')->get();
    expect($rows->first()->id)->toBe($ordinary->id)->and($rows->firstWhere('id', $shop->id)->is_sponsored)->toBeFalse();
})->with([
    [['status' => 'pending']], [['status' => 'rejected']], [['payment_status' => 'unverified']],
]);

it('ignores future expired and hidden businesses', function () {
    $future = publicShop($this, 'Future');
    publicSponsor($this, $future, null, ['starts_at' => now()->addDay(), 'ends_at' => now()->addDays(2)]);
    $expired = publicShop($this, 'Expired');
    publicSponsor($this, $expired, null, ['starts_at' => now()->subDays(2), 'ends_at' => now()->subSecond()]);
    $hidden = publicShop($this, 'Hidden', ['verification_status' => 'pending']);
    publicSponsor($this, $hidden);
    expect(app(GrowyaPaidPromotionService::class)->businessIds())->toBe([]);
    $this->get('/marketplace')->assertOk()->assertDontSee('>Hidden<', false);
});

it('puts a sponsored product ahead of newer products on showroom home and product pages', function () {
    $business = publicShop($this, 'Product showroom');
    $sponsored = publicProduct($business, 'Old sponsored ring');
    publicSponsor($this, $business, $sponsored);
    for ($i = 0; $i < 14; $i++) {
        publicProduct($business, 'New regular '.$i);
    }
    $home = $this->get('/b/'.$business->slug)->assertOk();
    expect($home->viewData('products')->first()->id)->toBe($sponsored->id)->and($home->viewData('products')->count())->toBe(8);
    $list = $this->get('/b/'.$business->slug.'/products')->assertOk();
    expect($list->viewData('products')->first()->id)->toBe($sponsored->id)->and($list->viewData('products')->total())->toBe(15);
    $list->assertSee('Sponsored');
});

it('preserves search matches and filters while sorting business and product sponsors first', function () {
    $shop = publicShop($this, 'Search target old');
    publicSponsor($this, $shop);
    publicShop($this, 'Search target new');
    $irrelevant = publicShop($this, 'Unrelated sponsor');
    publicSponsor($this, $irrelevant);
    $r = $this->get('/search?type=businesses&q=Search%20target')->assertOk();
    expect($r->viewData('results')->first()->id)->toBe($shop->id)->and($r->viewData('results')->total())->toBe(2);
    $product = publicProduct($shop, 'Search gold old', ['price' => 200]);
    publicSponsor($this, $shop, $product);
    publicProduct($shop, 'Search gold new', ['price' => 200]);
    publicProduct($shop, 'Search gold expensive', ['price' => 900]);
    $r = $this->get('/search?type=products&q=Search%20gold&min_price=100&max_price=300')->assertOk();
    expect($r->viewData('results')->first()->id)->toBe($product->id)->and($r->viewData('results')->total())->toBe(2);
});

it('does not promote all products because their business is sponsored', function () {
    $shop = publicShop($this, 'Sponsored business');
    publicSponsor($this, $shop);
    $first = publicProduct($shop, 'Older organic');
    $second = publicProduct($shop, 'New organic');
    $rows = Product::where('business_id', $shop->id)->sponsoredFirst()->latest('id')->get();
    expect($rows->first()->id)->toBe($second->id)->and($rows->where('is_sponsored', true)->count())->toBe(0);
});

it('does not duplicate a product with multiple campaigns and rejects foreign or hidden targets', function () {
    $shop = publicShop($this, 'Shop A');
    $other = publicShop($this, 'Shop B');
    $product = publicProduct($shop, 'Advertised');
    publicSponsor($this, $shop, $product);
    publicSponsor($this, $shop, $product);
    expect(Product::sponsoredFirst()->count())->toBe(1);
    $foreign = publicProduct($other, 'Foreign');
    publicSponsor($this, $shop, $foreign);
    $hidden = publicProduct($shop, 'Hidden', ['status' => 'inactive']);
    publicSponsor($this, $shop, $hidden);
    $deleted = publicProduct($shop, 'Deleted');
    publicSponsor($this, $shop, $deleted);
    $deleted->delete();
    expect(app(GrowyaPaidPromotionService::class)->productIds())->toBe([$product->id]);
});

it('shows sponsored homepage products even if not manually featured and preserves click tracking', function () {
    $shop = publicShop($this, 'Homepage store');
    $product = publicProduct($shop, 'Sponsored unfeatured');
    $promotion = publicSponsor($this, $shop, $product);
    $r = $this->get('/')->assertOk();
    expect($r->viewData('featuredProducts')->first()->id)->toBe($product->id);
    $this->get('/promoted/'.$promotion->id)->assertRedirect(route('public.business.product', [$shop->slug, $product->slug]));
    expect($promotion->fresh()->clicks)->toBe(1);
});

it('applies sponsored order and safe additive metadata to existing public JSON APIs', function () {
    $shop = publicShop($this, 'Sponsored API old');
    $promotion = publicSponsor($this, $shop, null, ['payment_reference' => 'PRIVATE-BANK-REF', 'review_note' => 'PRIVATE-REVIEW']);
    publicShop($this, 'Organic API new');
    $r = $this->getJson('/api/v1/marketplace?sort=name')->assertOk()->assertDontSee('PRIVATE-BANK-REF')->assertDontSee('PRIVATE-REVIEW');
    $r->assertJsonPath('data.businesses.data.0.id', $shop->id)->assertJsonPath('data.businesses.data.0.is_sponsored', true);
    $product = publicProduct($shop, 'Sponsored API product');
    publicSponsor($this, $shop, $product);
    publicProduct($shop, 'New API product');
    $this->getJson('/api/v1/b/'.$shop->slug.'/products')->assertOk()->assertJsonPath('data.products.data.0.id', $product->id)->assertJsonPath('data.products.data.0.is_sponsored', true);
});

it('counts only the displayed campaign when two paid campaigns overlap', function () {
    $shop = publicShop($this, 'Overlap');
    $old = publicSponsor($this, $shop);
    $new = publicSponsor($this, $shop);
    app(GrowyaPaidPromotionService::class)->countImpressions([$shop->id]);
    expect($old->fresh()->impressions)->toBe(0)->and($new->fresh()->impressions)->toBe(1);
});
it('keeps sponsors first in nearby business JSON without changing the filters', function () {
    $shop = publicShop($this,'Old nearby sponsored');
    publicSponsor($this,$shop);
    publicShop($this,'New nearby organic');
    $this->getJson('/api/v1/nearby-businesses?sort=newest')->assertOk()->assertJsonPath('data.businesses.data.0.id',$shop->id)->assertJsonPath('data.businesses.data.0.is_sponsored',true);
});
