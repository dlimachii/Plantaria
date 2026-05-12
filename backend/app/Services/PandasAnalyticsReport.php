<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class PandasAnalyticsReport
{
    public function path(): string
    {
        return storage_path('app/analytics/output/admin_dashboard.json');
    }

    public function read(): array
    {
        $path = $this->path();

        if (! File::exists($path)) {
            return [
                'available' => false,
                'path' => $path,
                'message' => 'Todavía no se ha generado la analítica de pandas.',
            ];
        }

        $decoded = json_decode(File::get($path), true);

        if (! is_array($decoded)) {
            return [
                'available' => false,
                'path' => $path,
                'message' => 'El fichero de analítica existe, pero no contiene JSON válido.',
            ];
        }

        return [
            'available' => true,
            'path' => $path,
            ...$decoded,
        ];
    }

    public function compactContext(): array
    {
        $report = $this->read();

        if (! ($report['available'] ?? false)) {
            return [
                'available' => false,
                'message' => $report['message'] ?? 'No hay analítica disponible.',
            ];
        }

        return [
            'available' => true,
            'generated_at' => $report['generated_at'] ?? null,
            'source_counts' => Arr::get($report, 'source_counts', []),
            'kpis' => Arr::get($report, 'kpis', []),
            'risk_signals' => Arr::get($report, 'risk_signals', []),
            'top_searches' => array_slice(Arr::get($report, 'top_searches', []), 0, 5),
            'top_creators' => array_slice(Arr::get($report, 'top_creators', []), 0, 5),
        ];
    }
}
