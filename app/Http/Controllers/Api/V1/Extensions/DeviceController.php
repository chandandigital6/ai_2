<?php

namespace App\Http\Controllers\Api\V1\Extensions;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Extensions\DeviceController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\Extensions\DeviceController@index. */
    public function index(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'index', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\DeviceController@store. */
    public function store(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'store', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\DeviceController@destroy. */
    public function destroy(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'destroy', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\DeviceController@acknowledge. */
    public function acknowledge(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'acknowledge', adaptResponse: false);
    }
}
