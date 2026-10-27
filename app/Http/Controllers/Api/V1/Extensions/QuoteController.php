<?php

namespace App\Http\Controllers\Api\V1\Extensions;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Extensions\QuoteController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class QuoteController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\Extensions\QuoteController@quote. */
    public function quote(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'quote', adaptResponse: false);
    }
}
