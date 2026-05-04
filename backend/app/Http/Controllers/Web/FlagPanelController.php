<?php

namespace App\Http\Controllers\Web;

use App\Enums\EventType;
use App\Enums\FlagStatus;
use App\Enums\FlagTargetType;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AppEvent;
use App\Models\ModerationFlag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FlagPanelController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureModerator($request);

        $search = trim($request->string('q')->value());
        $selectedTargetType = $request->string('target_type')->value();

        $query = ModerationFlag::query()
            ->with([
                'reporter',
                'resolver',
                'record.author',
                'observation.author',
                'observation.plantRecord',
                'userTarget',
            ])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->value());
        }

        if ($selectedTargetType !== '') {
            $targetType = FlagTargetType::tryFrom($selectedTargetType);

            if ($targetType) {
                $query->where('target_type', $targetType->value);
            } else {
                $selectedTargetType = '';
            }
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('reason', 'like', "%{$search}%")
                    ->orWhereHas('reporter', function ($reporterQuery) use ($search): void {
                        $reporterQuery
                            ->where('handle', 'like', "%{$search}%")
                            ->orWhere('display_name', 'like', "%{$search}%");
                    })
                    ->orWhere(function ($flagQuery) use ($search): void {
                        $flagQuery
                            ->where('target_type', FlagTargetType::RECORD->value)
                            ->whereHas('record', function ($recordQuery) use ($search): void {
                                $recordQuery
                                    ->where('public_id', 'like', "%{$search}%")
                                    ->orWhere('provisional_common_name', 'like', "%{$search}%")
                                    ->orWhere('verified_common_name', 'like', "%{$search}%")
                                    ->orWhere('verified_scientific_name', 'like', "%{$search}%");
                            });
                    })
                    ->orWhere(function ($flagQuery) use ($search): void {
                        $flagQuery
                            ->where('target_type', FlagTargetType::OBSERVATION->value)
                            ->whereHas('observation', function ($observationQuery) use ($search): void {
                                $observationQuery
                                    ->where('public_id', 'like', "%{$search}%")
                                    ->orWhere('note', 'like', "%{$search}%");
                            });
                    })
                    ->orWhere(function ($flagQuery) use ($search): void {
                        $flagQuery
                            ->where('target_type', FlagTargetType::USER->value)
                            ->whereHas('userTarget', function ($userQuery) use ($search): void {
                                $userQuery
                                    ->where('handle', 'like', "%{$search}%")
                                    ->orWhere('display_name', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%");
                            });
                    });
            });
        }

        return view('admin.flags.index', [
            'flags' => $query->paginate(15)->withQueryString(),
            'statuses' => FlagStatus::cases(),
            'selectedStatus' => $request->string('status')->value(),
            'targetTypes' => FlagTargetType::cases(),
            'selectedTargetType' => $selectedTargetType,
            'search' => $search,
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

        AppEvent::record(EventType::FLAG_UPDATED, $moderator, metadata: [
            'flag_uid' => $flag->uid,
            'status' => $flag->status->value,
            'target_type' => $flag->target_type?->value,
            'target_id' => $flag->target_id,
            'source' => 'web_admin',
        ]);

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
