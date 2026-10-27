<?php

namespace App\Http\Controllers\Api\V1\Extensions;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Extensions\ReviewPhotoController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class ReviewPhotoController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\Extensions\ReviewPhotoController@store. */
    public function store(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'store', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\ReviewPhotoController@destroy. */
    public function destroy(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'destroy', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\ReviewPhotoController@file. */
    public function file(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'file', adaptResponse: false);
    }
}
