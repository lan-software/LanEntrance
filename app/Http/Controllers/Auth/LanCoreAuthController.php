<?php

namespace App\Http\Controllers\Auth;

use App\Actions\SyncUserRolesFromLanCore;
use App\Http\Controllers\Controller;
use App\Services\UserSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use LanSoftware\LanCoreClient\Exceptions\LanCoreDisabledException;
use LanSoftware\LanCoreClient\Exceptions\LanCoreRequestException;
use LanSoftware\LanCoreClient\Exceptions\LanCoreUnavailableException;
use LanSoftware\LanCoreClient\LanCoreClient;
use Symfony\Component\HttpFoundation\Response;

class LanCoreAuthController extends Controller
{
    public function __construct(
        private readonly LanCoreClient $client,
        private readonly UserSyncService $syncService,
        private readonly SyncUserRolesFromLanCore $syncRoles,
    ) {}

    public function redirect(): Response
    {
        try {
            return Inertia::location($this->client->ssoAuthorizeUrl());
        } catch (LanCoreDisabledException) {
            return redirect()->route('login', ['local' => 1]);
        }
    }

    public function callback(Request $request): RedirectResponse
    {
        $code = $request->string('code')->toString();

        if (strlen($code) !== 64) {
            return redirect()->route('home')->with('error', 'Invalid SSO callback. Please try again.');
        }

        try {
            $lanCoreUser = $this->client->exchangeCode($code);
            $user = $this->syncService->resolveFromLanCore($lanCoreUser);
            $this->syncRoles->handle($user, $lanCoreUser->roles);
        } catch (LanCoreRequestException $e) {
            return redirect()->route('home')->with('error', $e->statusCode === 400
                ? 'The login link has expired. Please try again.'
                : 'Could not connect to authentication service. Please try again later.');
        } catch (LanCoreUnavailableException) {
            return redirect()->route('home')->with('error', 'Could not connect to authentication service. Please try again later.');
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function status(): JsonResponse
    {
        return response()->json(['enabled' => (bool) config('lancore.enabled')]);
    }
}
