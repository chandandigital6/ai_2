<?php

use App\Models\Attribute;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Category;
use App\Models\GrowyaSupportTicket;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\BusinessAccessService;
use Database\Seeders\BusinessPermissionSeeder;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(BusinessPermissionSeeder::class);
    Role::findOrCreate('super_admin', 'web');
    $this->owner = User::factory()->create(['status' => 'active']);
    $this->business = Business::withoutEvents(fn () => Business::create(['owner_id' => $this->owner->id, 'name' => 'Owner Store', 'slug' => 'owner-store', 'status' => 'active', 'verification_status' => 'approved']));
    $this->staff = User::factory()->create(['status' => 'active']);
    $this->membership = BusinessUser::create(['business_id' => $this->business->id, 'user_id' => $this->staff->id, 'role' => 'manager', 'is_active' => true]);
    $this->access = app(BusinessAccessService::class);
});
function grantBusinessPermissions(BusinessUser $member, array $names): void
{
    $member->permissions()->sync(Permission::where('guard_name', 'web')->whereIn('name', $names)->pluck('id'));
}
it('gives the recorded owner every business permission without an owner pivot', function () {
    foreach (config('business_permissions.permissions') as $permission) {
        expect($this->access->can($this->owner, $this->business, $permission))->toBeTrue();
    }
});
it('does not authorize a manager from its label alone', function () {
    expect($this->access->can($this->staff, $this->business, 'products.create'))->toBeFalse();
});
it('never uses a global product permission as tenant membership', function () {
    $outsider = User::factory()->create(['status' => 'active']);
    $outsider->givePermissionTo('products.view');
    expect($this->access->can($outsider, $this->business, 'products.view'))->toBeFalse();
});
it('isolates a membership permission to its business', function () {
    grantBusinessPermissions($this->membership, ['products.view']);
    $other = Business::withoutEvents(fn () => Business::create(['owner_id' => $this->owner->id, 'name' => 'Other', 'slug' => 'other', 'status' => 'active', 'verification_status' => 'approved']));
    expect($this->access->can($this->staff, $this->business, 'products.view'))->toBeTrue()->and($this->access->can($this->staff, $other, 'products.view'))->toBeFalse();
});
it('revokes inactive staff immediately', function () {
    grantBusinessPermissions($this->membership, ['products.view']);
    $this->membership->update(['is_active' => false]);
    expect($this->access->can($this->staff, $this->business, 'products.view'))->toBeFalse();
});
it('allows active super admin and blocks suspended super admin', function () {
    $this->staff->assignRole('super_admin');
    expect($this->access->can($this->staff, $this->business, 'products.view'))->toBeTrue();
    $this->staff->update(['status' => 'inactive']);
    expect($this->access->can($this->staff, $this->business, 'products.view'))->toBeFalse();
});
it('allows onboarding catalogs and staff but blocks public operations before approval', function () {
    $this->business->updateQuietly(['verification_status' => 'pending']);
    expect($this->access->can($this->owner, $this->business, 'staff.manage'))->toBeTrue()->and($this->access->can($this->owner, $this->business, 'categories.manage'))->toBeTrue()->and($this->access->can($this->owner, $this->business, 'products.create'))->toBeFalse();
});
it('does not expose orders or payments through product permission', function () {
    grantBusinessPermissions($this->membership, ['products.view']);
    Sanctum::actingAs($this->staff, ['growya']);
    $this->getJson('/api/v1/businesses/'.$this->business->id.'/orders')->assertForbidden();
    $this->getJson('/api/v1/businesses/'.$this->business->id.'/payments')->assertForbidden();
});
it('allows order read with orders view and no products permission', function () {
    grantBusinessPermissions($this->membership, ['orders.view']);
    Sanctum::actingAs($this->staff, ['growya']);
    $this->getJson('/api/v1/businesses/'.$this->business->id.'/orders')->assertOk()->assertJsonPath('data.orders.total', 0);
});
it('denies writes to view-only staff before validation', function () {
    grantBusinessPermissions($this->membership, ['orders.view']);
    Sanctum::actingAs($this->staff, ['growya']);
    $order = Order::withoutEvents(fn () => Order::create(['business_id' => $this->business->id, 'customer_name' => 'Buyer', 'order_number' => 'TEST-001', 'subtotal' => 100, 'discount_amount' => 0, 'tax_amount' => 0, 'total_amount' => 100, 'payment_status' => 'pending', 'status' => 'pending']));
    $this->patchJson('/api/v1/businesses/'.$this->business->id.'/orders/'.$order->id.'/status', ['status' => 'confirmed'])->assertForbidden();
});
it('protects owner memberships from removal', function () {
    $member = BusinessUser::create(['business_id' => $this->business->id, 'user_id' => $this->owner->id, 'role' => 'owner', 'is_active' => true]);
    Sanctum::actingAs($this->owner, ['growya']);
    $this->deleteJson('/api/v1/businesses/'.$this->business->id.'/staff/'.$member->id)->assertForbidden();
});
it('creates a new staff account and records its access history', function () {
    $this->membership->delete();
    Sanctum::actingAs($this->owner, ['growya']);
    $this->postJson('/api/v1/businesses/'.$this->business->id.'/staff', ['name' => 'New Staff', 'email' => 'staff@example.test', 'phone' => '9876543210', 'password' => 'SecurePass123!', 'password_confirmation' => 'SecurePass123!', 'role' => 'staff', 'permissions' => [Permission::findByName('products.view', 'web')->id]])->assertCreated();
    $user = User::where('email', 'staff@example.test')->firstOrFail();
    $this->assertDatabaseHas('business_users', ['user_id' => $user->id, 'business_id' => $this->business->id]);
    $this->assertDatabaseHas('business_staff_audits', ['subject_user_id' => $user->id, 'action' => 'created']);
});
it('prevents a delegated manager from granting permissions they do not hold', function () {
    grantBusinessPermissions($this->membership, ['staff.manage', 'staff.view']);
    Sanctum::actingAs($this->staff, ['growya']);
    $this->postJson('/api/v1/businesses/'.$this->business->id.'/staff', ['email' => 'x@example.test', 'role' => 'manager', 'permissions' => [Permission::findByName('finance.manage', 'web')->id]])->assertUnprocessable();
});
it('creates a custom category in the selected business only', function () {
    Sanctum::actingAs($this->owner, ['growya']);
    $this->postJson('/api/v1/businesses/'.$this->business->id.'/catalog/categories', ['name' => 'Gold Rings', 'category_type' => 'product', 'is_active' => true])->assertCreated();
    $this->assertDatabaseHas('categories', ['business_id' => $this->business->id, 'name' => 'Gold Rings']);
});
it('rejects another business category while validating product input', function () {
    $other = Business::withoutEvents(fn () => Business::create(['owner_id' => $this->owner->id, 'name' => 'Other', 'slug' => 'different', 'status' => 'active', 'verification_status' => 'approved']));
    $category = Category::create(['business_id' => $other->id, 'name' => 'Private', 'slug' => 'private', 'category_type' => 'product']);
    Sanctum::actingAs($this->owner, ['growya']);
    $this->postJson('/api/v1/businesses/'.$this->business->id.'/products', ['category_id' => $category->id])->assertUnprocessable()->assertJsonValidationErrors('category_id');
});
it('rejects another business attribute value assignment', function () {
    $other = Business::withoutEvents(fn () => Business::create(['owner_id' => $this->owner->id, 'name' => 'Other', 'slug' => 'different', 'status' => 'active', 'verification_status' => 'approved']));
    $attribute = Attribute::create(['business_id' => $other->id, 'name' => 'Purity', 'slug' => 'private-purity']);
    $value = $attribute->values()->create(['value' => '22KT']);
    $product = Product::create(['business_id' => $this->business->id, 'name' => 'Ring', 'slug' => 'ring', 'price' => 100, 'stock_status' => 'in_stock', 'status' => 'active']);
    Sanctum::actingAs($this->owner, ['growya']);
    $this->putJson('/api/v1/businesses/'.$this->business->id.'/products/'.$product->id.'/attributes', ['values' => [$value->id]])->assertUnprocessable();
});
it('denies global permission administration to an owner', function () {
    Sanctum::actingAs($this->owner, ['growya']);
    $this->getJson('/api/v1/permissions')->assertForbidden();
});
it('returns JSON for anonymous API authentication failures', function () {
    $this->get('/api/v1/businesses')->assertUnauthorized()->assertHeader('Content-Type', 'application/json');
});
it('rejects a token without Growya ability', function () {
    Sanctum::actingAs($this->owner, ['profile']);
    $this->getJson('/api/v1/businesses')->assertForbidden();
});
it('offers business registration without assigning global privilege', function () {
    $response = $this->postJson('/api/v1/auth/register', ['name' => 'Applicant', 'email' => 'applicant@example.test', 'phone' => '9876543210', 'password' => 'SecurePass123!', 'password_confirmation' => 'SecurePass123!', 'account_type' => 'business']);
    $response->assertCreated()->assertJsonPath('data.user.user_type', 'owner');
    $user = User::where('email', 'applicant@example.test')->firstOrFail();
    expect($user->hasRole('super_admin', 'web'))->toBeFalse();
});
it('does not return credential material in login or me', function () {
    $this->owner->update(['password' => 'SecurePass123!']);
    $response = $this->postJson('/api/v1/auth/login', ['email' => $this->owner->email, 'password' => 'SecurePass123!', 'device_name' => 'Test']);
    $response->assertOk()->assertJsonMissingPath('data.user.password');
    $this->withToken($response->json('data.token'))->getJson('/api/v1/auth/me')->assertOk()->assertJsonMissingPath('data.password');
});
it('blocks a suspended account using an already issued token', function () {
    $token = $this->owner->createToken('Test', ['growya'])->plainTextToken;
    $this->owner->update(['status' => 'inactive']);
    $this->withToken($token)->getJson('/api/v1/businesses')->assertForbidden();
});

it('serves an owner workspace API without rendering Blade', function (string $path) {
    Sanctum::actingAs($this->owner, ['growya']);
    $response = $this->getJson('/api/v1/'.str_replace('{business}', (string) $this->business->id, $path));
    $response->assertOk()->assertHeader('Content-Type', 'application/json');
})->with('owner workspace paths');
dataset('owner workspace paths', ['businesses/{business}/finance', 'businesses/{business}/crm-incentives', 'businesses/{business}/crm-staff', 'businesses/{business}/crm-kanban', 'businesses/{business}/lead-insights', 'businesses/{business}/crm', 'businesses/{business}/reviews', 'businesses/{business}/map-location', 'businesses/{business}/wishlist-interests', 'businesses/{business}/coupons', 'businesses/{business}/subscriptions', 'businesses/{business}/plan-usage', 'businesses/{business}/paid-promotions', 'businesses/{business}/analytics', 'businesses/{business}/kyc', 'businesses/{business}/messages', 'businesses/{business}/billing', 'businesses/{business}/payments', 'businesses/{business}/returns', 'businesses/{business}/delivery', 'businesses/{business}/orders', 'businesses/{business}/product-enquiries', 'businesses/{business}/edit', 'businesses/{business}/hours', 'businesses/{business}/social-links', 'businesses/{business}/staff', 'businesses/{business}/products', 'businesses/{business}/services', 'businesses/{business}/service-enquiries', 'businesses/{business}/service-follow-ups', 'businesses/{business}/appointments', 'businesses/{business}/gallery', 'businesses/{business}/offers', 'businesses/{business}/crm-incentives/report', 'businesses/{business}/crm-staff/report', 'businesses/{business}/crm/create', 'businesses/{business}/coupons/create', 'businesses/{business}/staff/history', 'businesses/{business}/products/bulk', 'businesses/{business}/products/create', 'businesses/{business}/services/create', 'businesses/{business}/appointments/create', 'businesses/{business}/gallery/create', 'businesses/{business}/offers/create']);

it('renders the owner staff and catalog screens with the existing application layout', function () {
    $this->actingAs($this->owner);
    $this->get('/businesses/'.$this->business->id.'/staff')->assertOk()->assertSee('Team & permissions', false);
    $this->get('/businesses/'.$this->business->id.'/catalog/categories')->assertOk()->assertSee('Catalog settings');
    $this->get('/businesses/'.$this->business->id.'/catalog/attributes')->assertOk()->assertSee('Catalog settings');
});
it('renders a staff dashboard with only the granted module', function () {
    grantBusinessPermissions($this->membership, ['orders.view']);
    $this->actingAs($this->staff);
    $this->get('/dashboard')->assertOk()->assertSee('Total orders')->assertDontSee('Paid order value')->assertDontSee('Add product');
});
it('takes a business applicant to the business application form', function () {
    $this->post('/register', ['name' => 'New Applicant', 'email' => 'new@example.test', 'phone' => '9876543210', 'password' => 'SecurePass123!', 'password_confirmation' => 'SecurePass123!', 'account_type' => 'business'])->assertRedirect(route('businesses.create'));
});
it('blocks cross-business staff deletion before touching membership', function () {
    $other = Business::withoutEvents(fn () => Business::create(['owner_id' => $this->owner->id, 'name' => 'Other', 'slug' => 'different', 'status' => 'active', 'verification_status' => 'approved']));
    Sanctum::actingAs($this->owner, ['growya']);
    $this->deleteJson('/api/v1/businesses/'.$other->id.'/staff/'.$this->membership->id)->assertNotFound();
    $this->assertDatabaseHas('business_users', ['id' => $this->membership->id]);
});
it('prevents delegated staff from changing their own permissions', function () {
    grantBusinessPermissions($this->membership, ['staff.view', 'staff.manage']);
    Sanctum::actingAs($this->staff, ['growya']);
    $this->putJson('/api/v1/businesses/'.$this->business->id.'/staff/'.$this->membership->id, ['role' => 'manager', 'is_active' => true])->assertForbidden();
});
it('allows staff catalog access only with the scoped permission', function () {
    grantBusinessPermissions($this->membership, ['categories.view']);
    Sanctum::actingAs($this->staff, ['growya']);
    $this->getJson('/api/v1/businesses/'.$this->business->id.'/catalog/categories')->assertOk();
    $this->postJson('/api/v1/businesses/'.$this->business->id.'/catalog/categories', ['name' => 'Not allowed', 'category_type' => 'product', 'is_active' => true])->assertForbidden();
    $this->getJson('/api/v1/businesses/'.$this->business->id.'/catalog/attributes')->assertForbidden();
});
it('requires a second factor before issuing an API token for a protected account', function () {
    $this->owner->forceFill(['password' => 'SecurePass123!', 'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'), 'two_factor_confirmed_at' => now()])->save();
    $this->postJson('/api/v1/auth/login', ['email' => $this->owner->email, 'password' => 'SecurePass123!', 'device_name' => 'Test'])->assertUnprocessable()->assertJsonValidationErrors('two_factor_code');
    $this->assertDatabaseCount('personal_access_tokens', 0);
});
it('revokes just the current token on API logout', function () {
    $token = $this->owner->createToken('A', ['growya'])->plainTextToken;
    $this->owner->createToken('B', ['growya']);
    $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();
    $this->assertDatabaseCount('personal_access_tokens', 1);
});

it('renders the admin support API with the complete platform permission catalog', function () {
    $this->owner->assignRole('super_admin');
    $ticket = GrowyaSupportTicket::create(['ticket_number' => 'GYS-TEST', 'user_id' => $this->staff->id, 'subject' => 'Test issue', 'category' => 'technical', 'priority' => 'medium', 'status' => 'open']);
    Sanctum::actingAs($this->owner, ['growya']);
    $this->getJson('/api/v1/support/tickets/'.$ticket->id)->assertOk();
});
