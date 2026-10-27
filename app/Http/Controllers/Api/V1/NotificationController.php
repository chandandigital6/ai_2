<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GrowyaNotificationController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\GrowyaNotificationController@index. */
    public function index(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'index');
    }

    /** Shared operation: App\Http\Controllers\GrowyaNotificationController@readAll. */
    public function readAll(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'readAll');
    }

    /** Shared operation: App\Http\Controllers\GrowyaNotificationController@open. */
    public function open(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'open');
    }

    /** Shared operation: App\Http\Controllers\GrowyaNotificationController@read. */
    public function read(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'read');
    }
}
