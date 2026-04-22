<?php

namespace App\Http\Controllers\Web;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AdminSessionController extends Controller
{
    public function create(): View
    {
        return view('admin.auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $login = strtolower(trim($credentials['login']));
        $user = User::query()
            ->where('handle', $login)
            ->orWhere('email', $login)
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return back()
                ->withErrors(['login' => 'Credenciales incorrectas.'])
                ->onlyInput('login');
        }

        if ($user->status !== UserStatus::ACTIVE || ! $user->isModerator()) {
            return back()
                ->withErrors(['login' => 'Esta cuenta no tiene acceso al panel.'])
                ->onlyInput('login');
        }

        Auth::login($user, (bool) $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
