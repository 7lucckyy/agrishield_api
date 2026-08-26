<?php

namespace App\Http\Controllers\Web\Auth;

use App\Enums\GlobalRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\LoginRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SessionController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectForAuthenticatedUser();
        }

        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        return $this->redirectForAuthenticatedUser();
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function redirectForAuthenticatedUser(): RedirectResponse
    {
        $user = Auth::user();

        if ($user?->hasRole(GlobalRole::PlatformAdmin->value)) {
            return redirect()->route('platform.dashboard');
        }

        $organizationId = $user?->primaryOrganizationId();

        if ($organizationId !== null) {
            return redirect()->route('organization.dashboard', $organizationId);
        }

        return redirect()->route('home')->with('status', 'Your account is active but is not assigned to an organization yet.');
    }
}
