<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\TagController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\TagController@index. */
    public function index(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'index');
    }

    /** Shared operation: App\Http\Controllers\TagController@store. */
    public function store(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'store');
    }

    /** Shared operation: App\Http\Controllers\TagController@update. */
    public function update(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'update');
    }

    /** Shared operation: App\Http\Controllers\TagController@destroy. */
    public function destroy(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'destroy');
    }
}
