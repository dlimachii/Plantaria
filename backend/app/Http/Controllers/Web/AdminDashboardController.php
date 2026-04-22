<?php

namespace App\Http\Controllers\Web;

use App\Enums\FlagStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\AppEvent;
use App\Models\ModerationFlag;
use App\Models\PlantRecord;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $this->ensureModerator($request);

        return view('admin.dashboard', [
            'pendingRecords' => PlantRecord::query()->where('verification_status', VerificationStatus::PENDING->value)->count(),
            'verifiedRecords' => PlantRecord::query()->where('verification_status', VerificationStatus::VERIFIED->value)->count(),
            'rejectedRecords' => PlantRecord::query()->where('verification_status', VerificationStatus::REJECTED->value)->count(),
            'activeUsers' => User::query()->where('status', UserStatus::ACTIVE->value)->count(),
            'openFlags' => ModerationFlag::query()->whereIn('status', [FlagStatus::OPEN->value, FlagStatus::REVIEWING->value])->count(),
            'recentEvents' => AppEvent::query()->latest('occurred_at')->limit(8)->get(),
        ]);
    }

    private function ensureModerator(Request $request): void
    {
        $user = $request->user();

        abort_unless(
            $user && in_array($user->role, [UserRole::MOD, UserRole::ADMIN], true),
            403
        );
    }
}
