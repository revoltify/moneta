<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Http\Responses\Concerns\RedirectsToCurrentCompany;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Fortify;
use Laravel\Passkeys\Contracts\PasskeyLoginResponse as PasskeyLoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

final class PasskeyLoginResponse implements PasskeyLoginResponseContract
{
    use RedirectsToCurrentCompany;

    public function toResponse($request): Response
    {
        $redirect = $this->redirectPathForCurrentCompany($request, Fortify::redirects('login'));

        return $request->wantsJson()
            ? new JsonResponse(['redirect' => redirect()->intended($redirect)->getTargetUrl()], 200)
            : redirect()->intended($redirect);
    }
}
