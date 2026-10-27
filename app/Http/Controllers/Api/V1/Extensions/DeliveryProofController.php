<?php

namespace App\Http\Controllers\Api\V1\Extensions;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Extensions\DeliveryProofController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class DeliveryProofController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\Extensions\DeliveryProofController@send. */
    public function send(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'send', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\DeliveryProofController@verify. */
    public function verify(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'verify', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\DeliveryProofController@upload. */
    public function upload(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'upload', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\DeliveryProofController@complete. */
    public function complete(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'complete', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\DeliveryProofController@file. */
    public function file(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'file', adaptResponse: false);
    }
}
