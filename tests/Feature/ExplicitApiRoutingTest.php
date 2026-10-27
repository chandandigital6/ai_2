<?php

use App\Models\Business;
use App\Models\User;
use Database\Seeders\BusinessPermissionSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

it('exposes real API controller actions for route discovery and keeps static route precedence', function () {
    $routes = Route::getRoutes();
    foreach (['api.v1.businesses.products.store' => 'App\\Http\\Controllers\\Api\\V1\\ProductController@store', 'api.v1.growya.chat.send' => 'App\\Http\\Controllers\\Api\\V1\\ChatController@send', 'api.v1.extensions.ai.store' => 'App\\Http\\Controllers\\Api\\V1\\Extensions\\AiController@store'] as $name => $action) {
        expect($routes->getByName($name)->getActionName())->toBe($action);
    }
    $route = $routes->match(Request::create('/api/v1/businesses/create', 'GET'));
    expect($route->getName())->toBe('api.v1.businesses.create');
    $route = $routes->match(Request::create('/api/v1/products/1/2/enquiry', 'GET'));
    expect($route->gatherMiddleware())->toContain('auth:sanctum');
});

it('keeps the explicit account permissions API tenant scoped and response compatible', function () {
    $this->seed(BusinessPermissionSeeder::class);
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $shop = Business::withoutEvents(fn () => Business::create(['owner_id' => $owner->id, 'name' => 'Permissions Shop', 'slug' => 'permissions-shop', 'status' => 'active', 'verification_status' => 'approved']));
    $url = '/api/v1/businesses/'.$shop->id.'/my-permissions';
    $this->getJson($url)->assertUnauthorized();
    $this->withToken($owner->createToken('test', ['growya'])->plainTextToken)->getJson($url)->assertOk()->assertJsonStructure(['data']);
    expect($this->getJson($url)->json('data'))->toContain('products.create', 'staff.manage');
    app('auth')->forgetGuards();
    $this->withToken($other->createToken('test', ['growya'])->plainTextToken)->getJson($url)->assertForbidden();
});
