<?php

namespace App\Http\Controllers\Web;

use App\Enums\EventType;
use App\Enums\FlagStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\AppEvent;
use App\Models\ModerationFlag;
use App\Models\Observation;
use App\Models\PlantRecord;
use App\Models\User;
use App\Services\PandasAnalyticsReport;
use App\Services\ServerFootprintSnapshot;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(
        Request $request,
        PandasAnalyticsReport $pandasReport,
        ServerFootprintSnapshot $serverFootprint,
    ): View
    {
        $this->ensureModerator($request);

        $today = now()->startOfDay();
        $reviewWindowFrom = now()->subDays(7)->startOfDay();
        $trendFrom = now()->subDays(13)->startOfDay();

        $pendingRecords = PlantRecord::query()->where('verification_status', VerificationStatus::PENDING->value)->count();
        $verifiedRecords = PlantRecord::query()->where('verification_status', VerificationStatus::VERIFIED->value)->count();
        $rejectedRecords = PlantRecord::query()->where('verification_status', VerificationStatus::REJECTED->value)->count();
        $activeUsers = User::query()->where('status', UserStatus::ACTIVE->value)->count();
        $openFlags = ModerationFlag::query()->whereIn('status', [FlagStatus::OPEN->value, FlagStatus::REVIEWING->value])->count();
        [$averageReviewTime7d, $averageReviewSamples7d] = $this->buildAverageReviewTime($reviewWindowFrom);

        $dailyActivity = $this->buildDailyActivity($trendFrom, 14);
        $hourlyActivity = $this->buildHourlyActivity();
        $topSearches = AppEvent::query()
            ->select('search_query', 'search_type')
            ->selectRaw('COUNT(*) as total')
            ->where('event_type', EventType::MAP_SEARCH->value)
            ->whereNotNull('search_query')
            ->groupBy('search_query', 'search_type')
            ->orderByDesc('total')
            ->limit(6)
            ->get();
        $topCreators = User::query()
            ->withCount(['createdRecords', 'observations'])
            ->orderByDesc('created_records_count')
            ->orderByDesc('observations_count')
            ->limit(6)
            ->get(['id', 'handle', 'display_name']);

        $totalRecords = $pendingRecords + $verifiedRecords + $rejectedRecords;
        $reviewedRecords = $verifiedRecords + $rejectedRecords;

        return view('admin.dashboard', [
            'pendingRecords' => $pendingRecords,
            'verifiedRecords' => $verifiedRecords,
            'rejectedRecords' => $rejectedRecords,
            'activeUsers' => $activeUsers,
            'openFlags' => $openFlags,
            'recordsToday' => PlantRecord::query()->where('created_at', '>=', $today)->count(),
            'pendingRecordsToday' => PlantRecord::query()
                ->where('created_at', '>=', $today)
                ->where('verification_status', VerificationStatus::PENDING->value)
                ->count(),
            'observationsToday' => Observation::query()->where('created_at', '>=', $today)->count(),
            'newUsersToday' => User::query()->where('created_at', '>=', $today)->count(),
            'activeUsersToday' => AppEvent::query()
                ->where('occurred_at', '>=', $today)
                ->distinct('user_id')
                ->whereNotNull('user_id')
                ->count('user_id'),
            'averageReviewTime7d' => $averageReviewTime7d,
            'averageReviewSamples7d' => $averageReviewSamples7d,
            'reviewCoveragePercent' => $totalRecords > 0 ? (int) round(($reviewedRecords / $totalRecords) * 100) : 0,
            'dailyActivity' => $dailyActivity,
            'hourlyActivity' => $hourlyActivity,
            'topSearches' => $topSearches,
            'topCreators' => $topCreators,
            'recentEvents' => AppEvent::query()->latest('occurred_at')->limit(10)->get(),
            'pandasAnalytics' => $pandasReport->read(),
            'serverFootprint' => $serverFootprint->read(),
        ]);
    }

    private function buildDailyActivity(\DateTimeInterface $from, int $days): Collection
    {
        $rows = AppEvent::query()
            ->selectRaw('DATE(occurred_at) as day, COUNT(*) as total_events, COUNT(DISTINCT user_id) as active_users')
            ->where('occurred_at', '>=', $from)
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy(fn (object $row): string => substr((string) $row->day, 0, 10));

        $series = collect(range(0, $days - 1))->map(function (int $offset) use ($from, $rows): array {
            $day = Carbon::parse($from)->addDays($offset);
            $key = $day->toDateString();
            $row = $rows->get($key);

            return [
                'day' => $key,
                'label' => $day->format('d/m'),
                'total_events' => (int) ($row->total_events ?? 0),
                'active_users' => (int) ($row->active_users ?? 0),
            ];
        });

        $maxEvents = max(1, $series->max('total_events'));

        return $series->map(fn (array $item): array => [
            ...$item,
            'events_percent' => (int) round(($item['total_events'] / $maxEvents) * 100),
        ]);
    }

    private function buildHourlyActivity(): Collection
    {
        $hourlyActivity = AppEvent::query()
            ->selectRaw($this->hourExpression().' as hour, COUNT(*) as total_events')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        $rows = $hourlyActivity->keyBy(fn (object $row): string => str_pad((string) $row->hour, 2, '0', STR_PAD_LEFT));
        $series = collect(range(0, 23))->map(function (int $hour) use ($rows): array {
            $key = str_pad((string) $hour, 2, '0', STR_PAD_LEFT);
            $row = $rows->get($key);

            return [
                'hour' => $key,
                'total_events' => (int) ($row->total_events ?? 0),
            ];
        });

        $maxEvents = max(1, $series->max('total_events'));

        return $series->map(fn (array $item): array => [
            ...$item,
            'events_percent' => (int) round(($item['total_events'] / $maxEvents) * 100),
        ]);
    }

    private function hourExpression(): string
    {
        return DB::getDriverName() === 'pgsql'
            ? "TO_CHAR(occurred_at, 'HH24')"
            : "strftime('%H', occurred_at)";
    }

    /**
     * @return array{0: string, 1: int}
     */
    private function buildAverageReviewTime(\DateTimeInterface $from): array
    {
        $records = PlantRecord::query()
            ->whereNotNull('verified_at')
            ->where('verified_at', '>=', $from)
            ->whereIn('verification_status', [
                VerificationStatus::VERIFIED->value,
                VerificationStatus::REJECTED->value,
            ])
            ->latest('verified_at')
            ->limit(250)
            ->get(['created_at', 'verified_at']);

        if ($records->isEmpty()) {
            return ['n/a', 0];
        }

        $totalMinutes = 0;

        foreach ($records as $record) {
            if (! $record->verified_at || ! $record->created_at) {
                continue;
            }

            $totalMinutes += max(0, $record->created_at->diffInMinutes($record->verified_at));
        }

        $samples = max(0, $records->count());

        if ($samples === 0) {
            return ['n/a', 0];
        }

        $averageMinutes = (int) round($totalMinutes / $samples);

        if ($averageMinutes < 60) {
            return [$averageMinutes.' min', $samples];
        }

        $hours = intdiv($averageMinutes, 60);
        $minutes = $averageMinutes % 60;

        return [$hours.' h '.$minutes.' min', $samples];
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
