<?php

namespace App\Http\Controllers\Web;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class AdminAnalyticsController extends Controller
{
    public function rebuild(Request $request): RedirectResponse
    {
        $this->ensureAdmin($request);

        try {
            $exitCode = Artisan::call('plantaria:analytics:build');
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('status', 'No se pudo generar la analitica de pandas. Revisa pandas/entorno Python.');
        }

        if ($exitCode !== 0) {
            return back()->with('status', 'La exportacion termino, pero pandas devolvio error. Ejecuta php artisan plantaria:analytics:build para ver el detalle.');
        }

        return back()->with('status', 'Analitica de pandas actualizada.');
    }

    private function ensureAdmin(Request $request): User
    {
        /** @var User|null $user */
        $user = $request->user();

        abort_unless($user && $user->role === UserRole::ADMIN, 403);

        return $user;
    }
}
