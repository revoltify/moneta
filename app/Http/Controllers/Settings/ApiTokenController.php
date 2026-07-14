<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Setup\EnsurePersonalAccessClient;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Passport\Token;

final class ApiTokenController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('settings/api-tokens', [
            'tokens' => $request->user()->tokens()
                ->where('revoked', false)
                ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->orderByDesc('created_at')
                ->get()
                ->map(fn (Token $token) => [
                    'id' => $token->getKey(),
                    'name' => $token->name,
                    'createdAt' => $token->created_at?->toDateString(),
                    'expiresAt' => $token->expires_at?->toDateString(),
                ]),
            'plainTextToken' => $request->session()->get('plainTextToken'),
            'mcpUrl' => url('/mcp'),
        ]);
    }

    public function store(Request $request, EnsurePersonalAccessClient $ensurePersonalAccessClient): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $ensurePersonalAccessClient->handle();

        $token = $request->user()->createToken($validated['name']);

        return back()->with('plainTextToken', $token->accessToken);
    }

    public function destroy(Request $request, string $tokenId): RedirectResponse
    {
        $request->user()->tokens()->find($tokenId)?->revoke();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('API token revoked.')]);

        return back();
    }
}
