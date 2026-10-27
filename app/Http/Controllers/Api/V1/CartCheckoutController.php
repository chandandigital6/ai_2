<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GrowyaCartCheckoutController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class CartCheckoutController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\GrowyaCartCheckoutController@myOrders. */
    public function myOrders(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'myOrders');
    }

    /** Shared operation: App\Http\Controllers\GrowyaCartCheckoutController@order. */
    public function order(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'order');
    }

    /** Shared operation: App\Http\Controllers\GrowyaCartCheckoutController@index. */
    public function index(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'index');
    }

    /** Shared operation: App\Http\Controllers\GrowyaCartCheckoutController@add. */
    public function add(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'add');
    }

    /** Shared operation: App\Http\Controllers\GrowyaCartCheckoutController@checkoutForm. */
    public function checkoutForm(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'checkoutForm');
    }

    /** Shared operation: App\Http\Controllers\GrowyaCartCheckoutController@place. */
    public function place(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'place');
    }

    /** Shared operation: App\Http\Controllers\GrowyaCartCheckoutController@update. */
    public function update(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'update');
    }

    /** Shared operation: App\Http\Controllers\GrowyaCartCheckoutController@remove. */
    public function remove(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'remove');
    }
}
