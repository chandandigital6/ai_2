<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GrowyaLiveNotificationController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class LiveNotificationController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\GrowyaLiveNotificationController@poll. */
    public function poll(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'poll');
    }
}
