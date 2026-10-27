<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GrowyaBusinessCrmController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class BusinessCrmController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\GrowyaBusinessCrmController@index. */
    public function index(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'index');
    }

    /** Shared operation: App\Http\Controllers\GrowyaBusinessCrmController@store. */
    public function store(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'store');
    }

    /** Shared operation: App\Http\Controllers\GrowyaBusinessCrmController@create. */
    public function create(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'create');
    }

    /** Shared operation: App\Http\Controllers\GrowyaBusinessCrmController@export. */
    public function export(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'export');
    }

    /** Shared operation: App\Http\Controllers\GrowyaBusinessCrmController@import. */
    public function import(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'import');
    }

    /** Shared operation: App\Http\Controllers\GrowyaBusinessCrmController@show. */
    public function show(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'show');
    }

    /** Shared operation: App\Http\Controllers\GrowyaBusinessCrmController@update. */
    public function update(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'update');
    }

    /** Shared operation: App\Http\Controllers\GrowyaBusinessCrmController@edit. */
    public function edit(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'edit');
    }

    /** Shared operation: App\Http\Controllers\GrowyaBusinessCrmController@note. */
    public function note(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'note');
    }
}
