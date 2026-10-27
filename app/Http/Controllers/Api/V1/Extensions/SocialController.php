<?php

namespace App\Http\Controllers\Api\V1\Extensions;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Extensions\SocialController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class SocialController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\Extensions\SocialController@accounts. */
    public function accounts(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'accounts', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\SocialController@connect. */
    public function connect(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'connect', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\SocialController@candidates. */
    public function candidates(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'candidates', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\SocialController@select. */
    public function select(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'select', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\SocialController@disconnect. */
    public function disconnect(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'disconnect', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\SocialController@posts. */
    public function posts(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'posts', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\SocialController@store. */
    public function store(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'store', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\SocialController@publish. */
    public function publish(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'publish', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\SocialController@cancel. */
    public function cancel(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'cancel', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\SocialController@schedules. */
    public function schedules(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'schedules', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\SocialController@schedule. */
    public function schedule(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'schedule', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\SocialController@stopSchedule. */
    public function stopSchedule(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'stopSchedule', adaptResponse: false);
    }
}
