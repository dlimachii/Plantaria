<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\EventType;
use App\Enums\UserRole;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\AppEvent;
use App\Models\Observation;
use App\Models\PlantRecord;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $this->ensureAdmin();

        $today = now()->startOfDay();

        return response()->json([
            'summary' => [
                'active_users_today' => AppEvent::query()
                    ->where('occurred_at', '>=', $today)
                    ->distinct('user_id')
                    ->whereNotNull('user_id')
                    ->count('user_id'),
                'new_users_today' => User::query()->where('created_at', '>=', $today)->count(),
                'pending_records' => PlantRecord::query()
                    ->where('verification_status', VerificationStatus::PENDING->value)
                    ->count(),
                'records_today' => PlantRecord::query()->where('created_at', '>=', $today)->count(),
                'observations_today' => Observation::query()->where('created_at', '>=', $today)->count(),
            ],
        ]);
    }

    public function trends(Request $request): JsonResponse
    {
        $this->ensureAdmin();

        $days = max(1, min(60, (int) $request->integer('days', 14)));
        $from = now()->subDays($days - 1)->startOfDay();

        $dailyActivity = AppEvent::query()
            ->selectRaw("DATE(occurred_at) as day, COUNT(*) as total_events, COUNT(DISTINCT user_id) as active_users")
            ->where('occurred_at', '>=', $from)
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $hourlyActivity = AppEvent::query()
            ->selectRaw("strftime('%H', occurred_at) as hour, COUNT(*) as total_events")
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        if (DB::getDriverName() === 'pgsql') {
            $hourlyActivity = AppEvent::query()
                ->selectRaw("TO_CHAR(occurred_at, 'HH24') as hour, COUNT(*) as total_events")
                ->groupBy('hour')
                ->orderBy('hour')
                ->get();
        }

        return response()->json([
            'daily_activity' => $dailyActivity,
            'hourly_activity' => $hourlyActivity,
        ]);
    }

    public function topSearches(Request $request): JsonResponse
    {
        $this->ensureAdmin();

        $limit = max(1, min(20, (int) $request->integer('limit', 10)));

        $topSearches = AppEvent::query()
            ->select('search_query', 'search_type')
            ->selectRaw('COUNT(*) as total')
            ->where('event_type', EventType::MAP_SEARCH->value)
            ->whereNotNull('search_query')
            ->groupBy('search_query', 'search_type')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();

        $topCreators = User::query()
            ->select('handle', 'display_name')
            ->selectRaw('(SELECT COUNT(*) FROM plant_records WHERE plant_records.created_by_user_id = users.id) as records_count')
            ->orderByDesc('records_count')
            ->limit($limit)
            ->get();

        return response()->json([
            'top_searches' => $topSearches,
            'top_creators' => $topCreators,
        ]);
    }

    private function ensureAdmin(): void
    {
        /** @var User|null $user */
        $user = auth()->user();

        abort_unless($user && $user->role === UserRole::ADMIN, 403, 'Solo administracion.');
    }
}
