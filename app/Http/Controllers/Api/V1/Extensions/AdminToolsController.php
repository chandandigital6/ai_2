<?php

namespace App\Http\Controllers\Api\V1\Extensions;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Extensions\AdminToolsController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class AdminToolsController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\Extensions\AdminToolsController@publicContent. */
    public function publicContent(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'publicContent', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\AdminToolsController@report. */
    public function report(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'report', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\AdminToolsController@index. */
    public function index(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'index', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\AdminToolsController@save. */
    public function save(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'save', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\AdminToolsController@destroy. */
    public function destroy(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'destroy', adaptResponse: false);
    }
}
