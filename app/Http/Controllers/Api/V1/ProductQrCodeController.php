<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ProductQrCodeController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class ProductQrCodeController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\ProductQrCodeController@bulkPrint. */
    public function bulkPrint(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'bulkPrint');
    }

    /** Shared operation: App\Http\Controllers\ProductQrCodeController@show. */
    public function show(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'show');
    }

    /** Shared operation: App\Http\Controllers\ProductQrCodeController@generate. */
    public function generate(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'generate');
    }

    /** Shared operation: App\Http\Controllers\ProductQrCodeController@download. */
    public function download(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'download');
    }

    /** Shared operation: App\Http\Controllers\ProductQrCodeController@print. */
    public function print(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'print');
    }
}
