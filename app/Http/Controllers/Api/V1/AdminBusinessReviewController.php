<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\AdminBusinessReviewController as DomainController;
use App\Http\Controllers\Controller;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class AdminBusinessReviewController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\AdminBusinessReviewController@index. */
    public function index(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'index');
    }

    /** Shared operation: App\Http\Controllers\AdminBusinessReviewController@update. */
    public function update(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'update');
    }
}
