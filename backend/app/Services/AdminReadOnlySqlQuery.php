<?php

namespace App\Services;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use stdClass;

class AdminReadOnlySqlQuery
{
    public function execute(string $sql): array
    {
        if (! (bool) config('services.admin_sql.enabled', true)) {
            throw new RuntimeException('Las consultas SQL de solo lectura están deshabilitadas en este entorno.');
        }

        $preparedSql = $this->prepareSql($sql);
        $connection = DB::connection();
        $rows = $this->runReadOnlySelect($connection, $preparedSql);

        return $this->formatRows($rows);
    }

    private function prepareSql(string $sql): string
    {
        $sql = str_replace(["\r\n", "\r"], "\n", trim($sql));
        $sql = rtrim($sql, " \t\n\r\0\x0B;");
        $maxLength = max(200, (int) config('services.admin_sql.max_length', 4000));

        if ($sql === '') {
            throw new RuntimeException('La consulta SQL está vacía.');
        }

        if (strlen($sql) > $maxLength) {
            throw new RuntimeException("La consulta supera el máximo de {$maxLength} caracteres.");
        }

        if (str_contains($sql, ';')) {
            throw new RuntimeException('Solo se permite una única sentencia SQL de lectura.');
        }

        if (preg_match('/(--|\/\*|\*\/|#)/', $sql)) {
            throw new RuntimeException('No se permiten comentarios SQL en este modo seguro.');
        }

        if (! preg_match('/^\s*(select|with|explain)\b/i', $sql)) {
            throw new RuntimeException('Solo se permiten consultas SELECT, WITH o EXPLAIN.');
        }

        $forbiddenPatterns = [
            '/\b(insert|update|delete|drop|alter|truncate|create|replace|grant|revoke|comment|copy|call|execute|prepare|deallocate|vacuum|reindex|attach|detach|pragma|set|reset|show|use|merge|lock|listen|notify|do|commit|rollback|begin)\b/i',
            '/\binto\b/i',
            '/\bpg_read_file\b/i',
            '/\bpg_ls_dir\b/i',
            '/\bdblink\b/i',
            '/\blo_import\b/i',
            '/\bload_file\b/i',
            '/\boutfile\b/i',
            '/\binfile\b/i',
            '/\bxp_cmdshell\b/i',
        ];

        foreach ($forbiddenPatterns as $pattern) {
            if (preg_match($pattern, $sql)) {
                throw new RuntimeException('La consulta contiene instrucciones no permitidas para el modo de solo lectura.');
            }
        }

        return $sql;
    }

    /**
     * @return array<int, stdClass>
     */
    private function runReadOnlySelect(ConnectionInterface $connection, string $sql): array
    {
        $driver = $connection->getDriverName();
        $timeoutMs = max(1000, (int) config('services.admin_sql.timeout_ms', 4000));

        if ($driver === 'pgsql') {
            return $connection->transaction(function () use ($connection, $sql, $timeoutMs): array {
                $connection->statement("SET LOCAL statement_timeout = '{$timeoutMs}ms'");
                $connection->statement('SET LOCAL TRANSACTION READ ONLY');

                return $connection->select($sql);
            });
        }

        if ($driver === 'mysql') {
            return $connection->transaction(function () use ($connection, $sql): array {
                $connection->statement('SET TRANSACTION READ ONLY');

                return $connection->select($sql);
            });
        }

        return $connection->select($sql);
    }

    /**
     * @param array<int, stdClass> $rows
     * @return array{columns: array<int, string>, rows: array<int, array<string, mixed>>, row_count: int, truncated: bool}
     */
    private function formatRows(array $rows): array
    {
        $maxRows = max(1, (int) config('services.admin_sql.max_rows', 200));
        $rowCount = count($rows);
        $truncated = $rowCount > $maxRows;
        $rows = array_slice($rows, 0, $maxRows);
        $formattedRows = array_map(function (stdClass $row): array {
            $values = [];

            foreach (get_object_vars($row) as $column => $value) {
                if (is_bool($value)) {
                    $values[$column] = $value ? 'true' : 'false';
                } elseif (is_scalar($value) || $value === null) {
                    $values[$column] = $value;
                } else {
                    $values[$column] = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
            }

            return $values;
        }, $rows);

        return [
            'columns' => array_keys($formattedRows[0] ?? []),
            'rows' => $formattedRows,
            'row_count' => $rowCount,
            'truncated' => $truncated,
        ];
    }
}
