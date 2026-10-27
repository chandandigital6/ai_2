<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GrowyaPaymentController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\GrowyaPaymentController@customerIndex. */
    public function customerIndex(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'customerIndex');
    }

    /** Shared operation: App\Http\Controllers\GrowyaPaymentController@submit. */
    public function submit(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'submit');
    }

    /** Shared operation: App\Http\Controllers\GrowyaPaymentController@sellerIndex. */
    public function sellerIndex(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'sellerIndex');
    }

    /** Shared operation: App\Http\Controllers\GrowyaPaymentController@customerProof. */
    public function customerProof(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'customerProof');
    }

    /** Shared operation: App\Http\Controllers\GrowyaPaymentController@review. */
    public function review(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'review');
    }

    /** Shared operation: App\Http\Controllers\GrowyaPaymentController@proof. */
    public function proof(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'proof');
    }
}
