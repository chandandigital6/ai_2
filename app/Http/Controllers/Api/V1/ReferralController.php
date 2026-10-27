<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GrowyaReferralController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\GrowyaReferralController@mine. */
    public function mine(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'mine');
    }

    /** Shared operation: App\Http\Controllers\GrowyaReferralController@admin. */
    public function admin(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'admin');
    }

    /** Shared operation: App\Http\Controllers\GrowyaReferralController@settings. */
    public function settings(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'settings');
    }

    /** Shared operation: App\Http\Controllers\GrowyaReferralController@approveBusinessReward. */
    public function approveBusinessReward(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'approveBusinessReward');
    }
}
