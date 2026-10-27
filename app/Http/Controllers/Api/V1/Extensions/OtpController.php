<?php

namespace App\Http\Controllers\Api\V1\Extensions;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Extensions\OtpController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class OtpController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\Extensions\OtpController@send. */
    public function send(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'send', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\OtpController@verify. */
    public function verify(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'verify', adaptResponse: false);
    }
}
