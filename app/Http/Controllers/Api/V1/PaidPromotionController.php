<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GrowyaPaidPromotionController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class PaidPromotionController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\GrowyaPaidPromotionController@packages. */
    public function packages(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'packages');
    }

    /** Shared operation: App\Http\Controllers\GrowyaPaidPromotionController@storePackage. */
    public function storePackage(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'storePackage');
    }

    /** Shared operation: App\Http\Controllers\GrowyaPaidPromotionController@adminIndex. */
    public function adminIndex(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'adminIndex');
    }

    /** Shared operation: App\Http\Controllers\GrowyaPaidPromotionController@click. */
    public function click(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'click');
    }

    /** Shared operation: App\Http\Controllers\GrowyaPaidPromotionController@updatePackage. */
    public function updatePackage(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'updatePackage');
    }

    /** Shared operation: App\Http\Controllers\GrowyaPaidPromotionController@review. */
    public function review(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'review');
    }

    /** Shared operation: App\Http\Controllers\GrowyaPaidPromotionController@ownerIndex. */
    public function ownerIndex(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'ownerIndex');
    }

    /** Shared operation: App\Http\Controllers\GrowyaPaidPromotionController@request. */
    public function request(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'request');
    }
}
