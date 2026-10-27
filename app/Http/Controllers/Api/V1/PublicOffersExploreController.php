<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicOffersExploreController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class PublicOffersExploreController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\PublicOffersExploreController@index. */
    public function index(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'index');
    }
}
