<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicBusinessController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class PublicBusinessController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\PublicBusinessController@home. */
    public function home(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'home');
    }

    /** Shared operation: App\Http\Controllers\PublicBusinessController@products. */
    public function products(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'products');
    }

    /** Shared operation: App\Http\Controllers\PublicBusinessController@services. */
    public function services(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'services');
    }

    /** Shared operation: App\Http\Controllers\PublicBusinessController@gallery. */
    public function gallery(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'gallery');
    }

    /** Shared operation: App\Http\Controllers\PublicBusinessController@contact. */
    public function contact(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'contact');
    }

    /** Shared operation: App\Http\Controllers\PublicBusinessController@product. */
    public function product(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'product');
    }

    /** Shared operation: App\Http\Controllers\PublicBusinessController@service. */
    public function service(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'service');
    }
}
