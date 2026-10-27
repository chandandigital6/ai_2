<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GrowyaBusinessRequestController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class BusinessRequestController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\GrowyaBusinessRequestController@index. */
    public function index(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'index');
    }
}
