<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('plantaria:analytics:build {--skip-python : Export CSV datasets without running pandas}', function (): int {
    $analyticsDir = storage_path('app/analytics');
    $inputDir = storage_path('app/analytics/input');
    $outputDir = storage_path('app/analytics/output');
    $outputPath = $outputDir . DIRECTORY_SEPARATOR . 'admin_dashboard.json';

    File::ensureDirectoryExists($analyticsDir);
    File::ensureDirectoryExists($inputDir);
    File::ensureDirectoryExists($outputDir);

    $writeCsv = function (string $filename, array $headers, iterable $rows) use ($inputDir): int {
        $path = $inputDir . DIRECTORY_SEPARATOR . $filename;
        $handle = fopen($path, 'w');

        if ($handle === false) {
            throw new RuntimeException("No se pudo escribir {$path}");
        }

        fputcsv($handle, $headers);
        $count = 0;

        foreach ($rows as $row) {
            $arrayRow = (array) $row;
            fputcsv($handle, array_map(
                fn (string $header): mixed => $arrayRow[$header] ?? null,
                $headers,
            ));
            $count++;
        }

        fclose($handle);

        return $count;
    };

    $exports = [
        'users.csv' => $writeCsv('users.csv', [
            'id',
            'handle',
            'display_name',
            'role',
            'status',
            'country',
            'province',
            'city',
            'created_at',
            'last_login_at',
            'deleted_at',
        ], DB::table('users')
            ->select('id', 'handle', 'display_name', 'role', 'status', 'country', 'province', 'city', 'created_at', 'last_login_at', 'deleted_at')
            ->orderBy('id')
            ->cursor()),
        'plant_records.csv' => $writeCsv('plant_records.csv', [
            'id',
            'public_id',
            'created_by_user_id',
            'provisional_common_name',
            'verified_common_name',
            'verification_status',
            'plant_condition',
            'latitude',
            'longitude',
            'created_at',
            'verified_at',
            'deleted_at',
        ], DB::table('plant_records')
            ->select('id', 'public_id', 'created_by_user_id', 'provisional_common_name', 'verified_common_name', 'verification_status', 'plant_condition', 'latitude', 'longitude', 'created_at', 'verified_at', 'deleted_at')
            ->orderBy('id')
            ->cursor()),
        'observations.csv' => $writeCsv('observations.csv', [
            'id',
            'plant_record_id',
            'author_user_id',
            'plant_condition',
            'source_type',
            'observed_at',
            'created_at',
        ], DB::table('observations')
            ->select('id', 'plant_record_id', 'author_user_id', 'plant_condition', 'source_type', 'observed_at', 'created_at')
            ->orderBy('id')
            ->cursor()),
        'moderation_flags.csv' => $writeCsv('moderation_flags.csv', [
            'id',
            'target_type',
            'target_id',
            'created_by_user_id',
            'status',
            'resolved_by_user_id',
            'resolved_at',
            'created_at',
        ], DB::table('moderation_flags')
            ->select('id', 'target_type', 'target_id', 'created_by_user_id', 'status', 'resolved_by_user_id', 'resolved_at', 'created_at')
            ->orderBy('id')
            ->cursor()),
        'app_events.csv' => $writeCsv('app_events.csv', [
            'id',
            'event_type',
            'user_id',
            'role_snapshot',
            'plant_record_id',
            'search_query',
            'search_type',
            'occurred_at',
            'created_at',
        ], DB::table('app_events')
            ->select('id', 'event_type', 'user_id', 'role_snapshot', 'plant_record_id', 'search_query', 'search_type', 'occurred_at', 'created_at')
            ->orderBy('id')
            ->cursor()),
    ];

    foreach ($exports as $file => $count) {
        $this->line("{$file}: {$count} filas");
    }

    if ($this->option('skip-python')) {
        $this->info("Datasets exportados en {$inputDir}");

        return 0;
    }

    $script = dirname(base_path()) . DIRECTORY_SEPARATOR . 'analytics' . DIRECTORY_SEPARATOR . 'build_admin_analytics.py';
    $python = config('services.plantaria_analytics.python_bin', 'python3');

    if (! file_exists($script)) {
        $this->error("No existe el script de pandas: {$script}");

        return 1;
    }

    $process = new Process([
        $python,
        $script,
        '--input',
        $inputDir,
        '--output',
        $outputPath,
    ]);
    $process->setTimeout(90);
    $process->run();

    if (! $process->isSuccessful()) {
        $this->error(trim($process->getErrorOutput() ?: $process->getOutput()));

        return 1;
    }

    $this->info(trim($process->getOutput()));

    return 0;
})->purpose('Export Plantaria datasets and build the admin analytics report with pandas');
