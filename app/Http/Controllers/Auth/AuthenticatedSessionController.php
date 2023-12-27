<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(Request $request): Response
    {
        $request->session()->put('back_url', url()->previous());
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'phone' => 'required|',
        ]);

        $res = Http::post('http://chat.loc:8787/auth/register', $request->all());

        if ($res->status() !== 200) {
            back()->withErrors([
                'phone' => $res->json()['message'],
            ]);
        }

        $user = User::query()->createOrFirst([
            'phone' => $request->phone,
        ]);

        Auth::login($user);

        $backUrl = $request->session()->get('back_url', null);
        $request->session()->regenerate();
        if ($backUrl !== null) {
            return redirect()->intended($backUrl);
        }

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
