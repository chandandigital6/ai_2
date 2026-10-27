<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GrowyaOrderReturnsController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class OrderReturnsController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\GrowyaOrderReturnsController@customerIndex. */
    public function customerIndex(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'customerIndex');
    }

    /** Shared operation: App\Http\Controllers\GrowyaOrderReturnsController@submit. */
    public function submit(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'submit');
    }

    /** Shared operation: App\Http\Controllers\GrowyaOrderReturnsController@sellerIndex. */
    public function sellerIndex(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'sellerIndex');
    }

    /** Shared operation: App\Http\Controllers\GrowyaOrderReturnsController@review. */
    public function review(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'review');
    }

    /** Shared operation: App\Http\Controllers\GrowyaOrderReturnsController@markRefunded. */
    public function markRefunded(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'markRefunded');
    }
}
