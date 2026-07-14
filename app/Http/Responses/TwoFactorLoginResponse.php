<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Http\Responses\Concerns\RedirectsToCurrentCompany;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

final class TwoFactorLoginResponse implements TwoFactorLoginResponseContract
{
    use RedirectsToCurrentCompany;

    public function toResponse($request): Response
    {
        return $request->wantsJson()
            ? new JsonResponse(['two_factor' => false], 200)
            : redirect()->intended($this->redirectPathForCurrentCompany($request, Fortify::redirects('login')));
    }
}
