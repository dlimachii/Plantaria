<?php

namespace App\Http\Controllers\Web;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserPanelController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureAdmin($request);

        $query = User::query()
            ->withCount(['createdRecords', 'observations'])
            ->orderBy('handle');

        if ($request->filled('q')) {
            $term = $request->string('q')->trim()->value();
            $query->where(function ($builder) use ($term): void {
                $builder
                    ->where('handle', 'like', "%{$term}%")
                    ->orWhere('display_name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->string('role')->value());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->value());
        }

        return view('admin.users.index', [
            'users' => $query->paginate(20)->withQueryString(),
            'roles' => UserRole::cases(),
            'statuses' => UserStatus::cases(),
            'selectedRole' => $request->string('role')->value(),
            'selectedStatus' => $request->string('status')->value(),
            'search' => $request->string('q')->value(),
        ]);
    }

    public function show(Request $request, string $handle): View
    {
        $this->ensureAdmin($request);

        $user = User::query()
            ->withCount(['createdRecords', 'observations'])
            ->where('handle', Str::lower($handle))
            ->firstOrFail();

        return view('admin.users.show', [
            'managedUser' => $user,
            'roles' => UserRole::cases(),
            'statuses' => UserStatus::cases(),
        ]);
    }

    public function update(Request $request, string $handle): RedirectResponse
    {
        $this->ensureAdmin($request);

        $user = User::query()
            ->where('handle', Str::lower($handle))
            ->firstOrFail();

        $validated = $request->validate([
            'display_name' => ['required', 'string', 'max:120'],
            'role' => ['required', 'in:user,mod,admin'],
            'status' => ['required', 'in:active,banned,deleted'],
            'country' => ['required', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
        ]);

        $user->forceFill([
            'display_name' => $validated['display_name'],
            'role' => UserRole::from($validated['role']),
            'status' => UserStatus::from($validated['status']),
            'country' => $validated['country'],
            'province' => $validated['province'] ?? null,
            'city' => $validated['city'] ?? null,
        ])->save();

        return redirect()
            ->route('admin.users.show', $user->handle)
            ->with('status', 'Usuario actualizado.');
    }

    private function ensureAdmin(Request $request): void
    {
        $user = $request->user();

        abort_unless($user && $user->role === UserRole::ADMIN, 403);
    }
}
