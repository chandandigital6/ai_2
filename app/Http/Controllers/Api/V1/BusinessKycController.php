<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GrowyaBusinessKycController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class BusinessKycController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\GrowyaBusinessKycController@admin. */
    public function admin(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'admin');
    }

    /** Shared operation: App\Http\Controllers\GrowyaBusinessKycController@owner. */
    public function owner(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'owner');
    }

    /** Shared operation: App\Http\Controllers\GrowyaBusinessKycController@submit. */
    public function submit(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'submit');
    }

    /** Shared operation: App\Http\Controllers\GrowyaBusinessKycController@adminShow. */
    public function adminShow(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'adminShow');
    }

    /** Shared operation: App\Http\Controllers\GrowyaBusinessKycController@review. */
    public function review(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'review');
    }

    /** Shared operation: App\Http\Controllers\GrowyaBusinessKycController@download. */
    public function download(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'download');
    }
}
