<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ProductEnquiryController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class ProductEnquiryController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\ProductEnquiryController@index. */
    public function index(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'index');
    }

    /** Shared operation: App\Http\Controllers\ProductEnquiryController@create. */
    public function create(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'create');
    }

    /** Shared operation: App\Http\Controllers\ProductEnquiryController@store. */
    public function store(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'store');
    }

    /** Shared operation: App\Http\Controllers\ProductEnquiryController@show. */
    public function show(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'show');
    }

    /** Shared operation: App\Http\Controllers\ProductEnquiryController@status. */
    public function status(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'status');
    }

    /** Shared operation: App\Http\Controllers\ProductEnquiryController@note. */
    public function note(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'note');
    }

    /** Shared operation: App\Http\Controllers\ProductEnquiryController@complete. */
    public function complete(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'complete');
    }
}
