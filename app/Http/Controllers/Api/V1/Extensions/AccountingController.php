<?php

namespace App\Http\Controllers\Api\V1\Extensions;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Extensions\AccountingController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class AccountingController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\Extensions\AccountingController@accounts. */
    public function accounts(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'accounts', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\AccountingController@account. */
    public function account(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'account', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\AccountingController@index. */
    public function index(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'index', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\AccountingController@store. */
    public function store(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'store', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\AccountingController@reverse. */
    public function reverse(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'reverse', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\AccountingController@reports. */
    public function reports(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'reports', adaptResponse: false);
    }
}
