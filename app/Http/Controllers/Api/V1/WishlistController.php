<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GrowyaWishlistController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\GrowyaWishlistController@mine. */
    public function mine(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'mine');
    }

    /** Shared operation: App\Http\Controllers\GrowyaWishlistController@admin. */
    public function admin(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'admin');
    }

    /** Shared operation: App\Http\Controllers\GrowyaWishlistController@seller. */
    public function seller(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'seller');
    }

    /** Shared operation: App\Http\Controllers\GrowyaWishlistController@adminUpdate. */
    public function adminUpdate(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'adminUpdate');
    }

    /** Shared operation: App\Http\Controllers\GrowyaWishlistController@add. */
    public function add(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'add');
    }

    /** Shared operation: App\Http\Controllers\GrowyaWishlistController@remove. */
    public function remove(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'remove');
    }

    /** Shared operation: App\Http\Controllers\GrowyaWishlistController@update. */
    public function update(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'update');
    }
}
