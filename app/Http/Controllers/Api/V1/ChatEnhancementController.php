<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GrowyaChatEnhancementController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class ChatEnhancementController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\GrowyaChatEnhancementController@reports. */
    public function reports(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'reports');
    }

    /** Shared operation: App\Http\Controllers\GrowyaChatEnhancementController@unread. */
    public function unread(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'unread');
    }

    /** Shared operation: App\Http\Controllers\GrowyaChatEnhancementController@assign. */
    public function assign(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'assign');
    }

    /** Shared operation: App\Http\Controllers\GrowyaChatEnhancementController@block. */
    public function block(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'block');
    }

    /** Shared operation: App\Http\Controllers\GrowyaChatEnhancementController@report. */
    public function report(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'report');
    }

    /** Shared operation: App\Http\Controllers\GrowyaChatEnhancementController@followup. */
    public function followup(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'followup');
    }

    /** Shared operation: App\Http\Controllers\GrowyaChatEnhancementController@activity. */
    public function activity(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'activity');
    }

    /** Shared operation: App\Http\Controllers\GrowyaChatEnhancementController@review. */
    public function review(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'review');
    }
}
