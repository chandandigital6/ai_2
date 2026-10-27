<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BusinessReviewController as DomainController;
use App\Http\Controllers\Controller;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class BusinessReviewController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\BusinessReviewController@publicIndex. */
    public function publicIndex(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'publicIndex');
    }

    /** Shared operation: App\Http\Controllers\BusinessReviewController@store. */
    public function store(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'store');
    }

    /** Shared operation: App\Http\Controllers\BusinessReviewController@ownerIndex. */
    public function ownerIndex(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'ownerIndex');
    }

    /** Shared operation: App\Http\Controllers\BusinessReviewController@update. */
    public function update(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'update');
    }

    /** Shared operation: App\Http\Controllers\BusinessReviewController@reply. */
    public function reply(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'reply');
    }
}
