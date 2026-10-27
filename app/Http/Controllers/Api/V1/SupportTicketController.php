<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GrowyaSupportTicketController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class SupportTicketController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\GrowyaSupportTicketController@index. */
    public function index(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'index');
    }

    /** Shared operation: App\Http\Controllers\GrowyaSupportTicketController@store. */
    public function store(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'store');
    }

    /** Shared operation: App\Http\Controllers\GrowyaSupportTicketController@create. */
    public function create(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'create');
    }

    /** Shared operation: App\Http\Controllers\GrowyaSupportTicketController@show. */
    public function show(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'show');
    }

    /** Shared operation: App\Http\Controllers\GrowyaSupportTicketController@reply. */
    public function reply(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'reply');
    }

    /** Shared operation: App\Http\Controllers\GrowyaSupportTicketController@update. */
    public function update(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'update');
    }

    /** Shared operation: App\Http\Controllers\GrowyaSupportTicketController@assign. */
    public function assign(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'assign');
    }

    /** Shared operation: App\Http\Controllers\GrowyaSupportTicketController@attachment. */
    public function attachment(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'attachment');
    }
}
