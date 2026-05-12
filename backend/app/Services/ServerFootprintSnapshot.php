<?php

namespace App\Services;

class ServerFootprintSnapshot
{
    /**
     * @return array{
     *     available: bool,
     *     generated_at: string,
     *     uptime_label: string,
     *     load_1m: ?float,
     *     cpu_cores: int,
     *     cpu_load_percent: ?int,
     *     memory_used_mb: ?int,
     *     memory_total_mb: ?int,
     *     memory_percent: ?int,
     *     disk_used_gb: ?float,
     *     disk_total_gb: ?float,
     *     disk_percent: ?int,
     *     estimated_watts: ?float,
     *     estimated_energy_kwh: ?float,
     *     estimated_co2_g: ?float,
     *     carbon_intensity_g_per_kwh: float
     * }
     */
    public function read(): array
    {
        $uptimeSeconds = $this->uptimeSeconds();
        $loadAverage = $this->loadAverage();
        $cpuCores = max(1, $this->cpuCores());
        $memory = $this->memoryUsage();
        $disk = $this->diskUsage(base_path());
        $carbonIntensity = (float) config('services.plantaria_footprint.carbon_intensity_g_per_kwh', 233);

        $cpuLoadRatio = $loadAverage === null ? null : min(1.0, max(0.0, $loadAverage / $cpuCores));
        $memoryGb = isset($memory['used_bytes']) ? $memory['used_bytes'] / 1024 / 1024 / 1024 : null;
        $estimatedWatts = $this->estimatedWatts($cpuLoadRatio, $memoryGb);
        $estimatedEnergyKwh = $uptimeSeconds === null ? null : ($estimatedWatts / 1000) * ($uptimeSeconds / 3600);
        $estimatedCo2G = $estimatedEnergyKwh === null ? null : $estimatedEnergyKwh * $carbonIntensity;

        return [
            'available' => $uptimeSeconds !== null || $loadAverage !== null || $memory !== null || $disk !== null,
            'generated_at' => now()->format('Y-m-d H:i'),
            'uptime_label' => $this->formatUptime($uptimeSeconds),
            'load_1m' => $loadAverage,
            'cpu_cores' => $cpuCores,
            'cpu_load_percent' => $cpuLoadRatio === null ? null : (int) round($cpuLoadRatio * 100),
            'memory_used_mb' => isset($memory['used_bytes']) ? (int) round($memory['used_bytes'] / 1024 / 1024) : null,
            'memory_total_mb' => isset($memory['total_bytes']) ? (int) round($memory['total_bytes'] / 1024 / 1024) : null,
            'memory_percent' => $this->percent($memory['used_bytes'] ?? null, $memory['total_bytes'] ?? null),
            'disk_used_gb' => isset($disk['used_bytes']) ? round($disk['used_bytes'] / 1024 / 1024 / 1024, 1) : null,
            'disk_total_gb' => isset($disk['total_bytes']) ? round($disk['total_bytes'] / 1024 / 1024 / 1024, 1) : null,
            'disk_percent' => $this->percent($disk['used_bytes'] ?? null, $disk['total_bytes'] ?? null),
            'estimated_watts' => round($estimatedWatts, 1),
            'estimated_energy_kwh' => $estimatedEnergyKwh === null ? null : round($estimatedEnergyKwh, 3),
            'estimated_co2_g' => $estimatedCo2G === null ? null : round($estimatedCo2G, 1),
            'carbon_intensity_g_per_kwh' => $carbonIntensity,
        ];
    }

    private function uptimeSeconds(): ?float
    {
        $contents = $this->readFirstLine('/proc/uptime');

        if ($contents === null) {
            return null;
        }

        $parts = preg_split('/\s+/', trim($contents));

        return isset($parts[0]) && is_numeric($parts[0]) ? (float) $parts[0] : null;
    }

    private function loadAverage(): ?float
    {
        $load = sys_getloadavg();

        if ($load !== false && isset($load[0])) {
            return round((float) $load[0], 2);
        }

        $contents = $this->readFirstLine('/proc/loadavg');

        if ($contents === null) {
            return null;
        }

        $parts = preg_split('/\s+/', trim($contents));

        return isset($parts[0]) && is_numeric($parts[0]) ? round((float) $parts[0], 2) : null;
    }

    private function cpuCores(): int
    {
        if (! is_readable('/proc/cpuinfo')) {
            return 1;
        }

        $contents = @file_get_contents('/proc/cpuinfo');

        if (! is_string($contents) || $contents === '') {
            return 1;
        }

        preg_match_all('/^processor\s*:/m', $contents, $matches);

        return max(1, count($matches[0]));
    }

    /**
     * @return array{used_bytes: int, total_bytes: int}|null
     */
    private function memoryUsage(): ?array
    {
        if (! is_readable('/proc/meminfo')) {
            return null;
        }

        $lines = @file('/proc/meminfo', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            return null;
        }

        $values = [];

        foreach ($lines as $line) {
            if (preg_match('/^(MemTotal|MemAvailable):\s+(\d+)\s+kB$/', $line, $matches) !== 1) {
                continue;
            }

            $values[$matches[1]] = (int) $matches[2] * 1024;
        }

        if (! isset($values['MemTotal'], $values['MemAvailable'])) {
            return null;
        }

        return [
            'used_bytes' => max(0, $values['MemTotal'] - $values['MemAvailable']),
            'total_bytes' => $values['MemTotal'],
        ];
    }

    /**
     * @return array{used_bytes: int, total_bytes: int}|null
     */
    private function diskUsage(string $path): ?array
    {
        $total = @disk_total_space($path);
        $free = @disk_free_space($path);

        if ($total === false || $free === false || $total <= 0) {
            return null;
        }

        return [
            'used_bytes' => (int) max(0, $total - $free),
            'total_bytes' => (int) $total,
        ];
    }

    private function estimatedWatts(?float $cpuLoadRatio, ?float $memoryGb): float
    {
        $idleWatts = (float) config('services.plantaria_footprint.idle_watts', 8);
        $cpuWatts = (float) config('services.plantaria_footprint.cpu_peak_extra_watts', 32);
        $memoryWattsPerGb = (float) config('services.plantaria_footprint.memory_watts_per_gb', 0.35);

        return $idleWatts
            + ($cpuWatts * ($cpuLoadRatio ?? 0))
            + ($memoryWattsPerGb * ($memoryGb ?? 0));
    }

    private function percent(?int $used, ?int $total): ?int
    {
        if ($used === null || $total === null || $total <= 0) {
            return null;
        }

        return (int) round(($used / $total) * 100);
    }

    private function formatUptime(?float $seconds): string
    {
        if ($seconds === null) {
            return 'n/a';
        }

        $days = intdiv((int) $seconds, 86400);
        $hours = intdiv(((int) $seconds % 86400), 3600);

        if ($days > 0) {
            return $days.' d '.$hours.' h';
        }

        return $hours.' h';
    }

    private function readFirstLine(string $path): ?string
    {
        if (! is_readable($path)) {
            return null;
        }

        $handle = @fopen($path, 'r');

        if ($handle === false) {
            return null;
        }

        try {
            $line = fgets($handle);
        } finally {
            fclose($handle);
        }

        return is_string($line) ? $line : null;
    }
}
