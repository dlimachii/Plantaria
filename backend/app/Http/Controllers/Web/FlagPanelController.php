<?php

namespace App\Http\Controllers\Web;

use App\Enums\FlagStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\ModerationFlag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FlagPanelController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureModerator($request);

        $query = ModerationFlag::query()
            ->with(['reporter', 'resolver'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->value());
        }

        return view('admin.flags.index', [
            'flags' => $query->paginate(15)->withQueryString(),
            'statuses' => FlagStatus::cases(),
            'selectedStatus' => $request->string('status')->value(),
        ]);
    }

    public function update(Request $request, string $uid): RedirectResponse
    {
        $moderator = $this->ensureModerator($request);

        $validated = $request->validate([
            'status' => ['required', 'in:reviewing,resolved,rejected'],
        ]);

        $flag = ModerationFlag::query()
            ->where('uid', $uid)
            ->firstOrFail();

        $flag->forceFill([
            'status' => FlagStatus::from($validated['status']),
            'resolved_by_user_id' => $moderator->id,
            'resolved_at' => now(),
        ])->save();

        return back()->with('status', 'Flag actualizado.');
    }

    private function ensureModerator(Request $request)
    {
        $user = $request->user();

        abort_unless(
            $user && in_array($user->role, [UserRole::MOD, UserRole::ADMIN], true),
            403
        );

        return $user;
    }
}
