<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

class RegisterResponse implements RegisterResponseContract
{
    public function toResponse($request): Response
    {
        return $request->wantsJson()
            ? new JsonResponse(['two_factor' => false], 201)
            : ($request->input('account_type') === 'business' ? redirect()->route('businesses.create') : redirect()->intended(Fortify::redirects('register')));
    }
}
