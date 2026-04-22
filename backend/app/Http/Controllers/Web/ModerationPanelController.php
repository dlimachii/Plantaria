<?php

namespace App\Http\Controllers\Web;

use App\Enums\EventType;
use App\Enums\UserRole;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\AppEvent;
use App\Models\PlantRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModerationPanelController extends Controller
{
    public function pending(Request $request): View
    {
        $this->ensureModerator($request);

        $records = PlantRecord::query()
            ->with('author')
            ->where('verification_status', VerificationStatus::PENDING->value)
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('admin.moderation.pending', [
            'records' => $records,
        ]);
    }

    public function show(Request $request, string $publicId): View
    {
        $this->ensureModerator($request);

        $record = PlantRecord::query()
            ->with(['author', 'observations.author', 'verifier'])
            ->where('public_id', $publicId)
            ->firstOrFail();

        return view('admin.moderation.show', [
            'record' => $record,
        ]);
    }

    public function verify(Request $request, string $publicId): RedirectResponse
    {
        $moderator = $this->ensureModerator($request);

        $validated = $request->validate([
            'verified_common_name' => ['required', 'string', 'max:120'],
            'verified_scientific_name' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $record = PlantRecord::query()
            ->where('public_id', $publicId)
            ->firstOrFail();

        $record->forceFill([
            'verification_status' => VerificationStatus::VERIFIED,
            'verified_common_name' => $validated['verified_common_name'],
            'verified_scientific_name' => $validated['verified_scientific_name'],
            'description' => $validated['description'] ?? $record->description,
            'verified_by_user_id' => $moderator->id,
            'verified_at' => now(),
        ])->save();

        AppEvent::record(EventType::RECORD_VERIFIED, $moderator, $record, metadata: [
            'verification_status' => VerificationStatus::VERIFIED->value,
            'source' => 'web_admin',
        ]);

        return redirect()
            ->route('admin.moderation.show', $record->public_id)
            ->with('status', 'Registro verificado.');
    }

    public function reject(Request $request, string $publicId): RedirectResponse
    {
        $moderator = $this->ensureModerator($request);

        $validated = $request->validate([
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $record = PlantRecord::query()
            ->where('public_id', $publicId)
            ->firstOrFail();

        $record->forceFill([
            'verification_status' => VerificationStatus::REJECTED,
            'verified_common_name' => null,
            'verified_scientific_name' => null,
            'description' => $validated['description'] ?? $record->description,
            'verified_by_user_id' => $moderator->id,
            'verified_at' => now(),
        ])->save();

        AppEvent::record(EventType::RECORD_VERIFIED, $moderator, $record, metadata: [
            'verification_status' => VerificationStatus::REJECTED->value,
            'source' => 'web_admin',
        ]);

        return redirect()
            ->route('admin.moderation.show', $record->public_id)
            ->with('status', 'Registro rechazado.');
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
