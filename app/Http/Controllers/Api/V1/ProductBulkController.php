<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ProductBulkController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class ProductBulkController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\ProductBulkController@index. */
    public function index(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'index');
    }

    /** Shared operation: App\Http\Controllers\ProductBulkController@template. */
    public function template(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'template');
    }

    /** Shared operation: App\Http\Controllers\ProductBulkController@import. */
    public function import(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'import');
    }

    /** Shared operation: App\Http\Controllers\ProductBulkController@export. */
    public function export(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'export');
    }

    /** Shared operation: App\Http\Controllers\ProductBulkController@errorReport. */
    public function errorReport(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'errorReport');
    }
}
