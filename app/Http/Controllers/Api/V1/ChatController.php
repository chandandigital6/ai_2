<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GrowyaChatController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\GrowyaChatController@mine. */
    public function mine(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'mine');
    }

    /** Shared operation: App\Http\Controllers\GrowyaChatController@show. */
    public function show(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'show');
    }

    /** Shared operation: App\Http\Controllers\GrowyaChatController@send. */
    public function send(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'send');
    }

    /** Shared operation: App\Http\Controllers\GrowyaChatController@inbox. */
    public function inbox(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'inbox');
    }

    /** Shared operation: App\Http\Controllers\GrowyaChatController@poll. */
    public function poll(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'poll');
    }

    /** Shared operation: App\Http\Controllers\GrowyaChatController@start. */
    public function start(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'start');
    }

    /** Shared operation: App\Http\Controllers\GrowyaChatController@file. */
    public function file(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'file');
    }
}
