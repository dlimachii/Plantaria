<?php

namespace App\Services;

use App\Enums\ObservationSourceType;
use App\Enums\VerificationStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminAssistantDirectQuery
{
    public function answer(string $question): ?string
    {
        $normalized = $this->normalize($question);
        $sections = [];

        if ($this->asksForTopObservationAuthors($normalized)) {
            $sections[] = $this->topObservationAuthors();
        }

        if ($this->asksForVerifiedPlantsWithoutScientificName($normalized)) {
            $sections[] = $this->verifiedPlantsWithoutScientificName();
        }

        if ($sections === []) {
            return null;
        }

        return "Consulta directa a BBDD segura de Plantaria.\n\n".implode("\n\n", $sections);
    }

    private function normalize(string $question): string
    {
        return Str::of($question)
            ->ascii()
            ->lower()
            ->toString();
    }

    private function asksForTopObservationAuthors(string $question): bool
    {
        $asksForUsers = str_contains($question, 'usuario')
            || str_contains($question, 'creador')
            || str_contains($question, 'autor');

        $asksForObservationRanking = str_contains($question, 'observacion')
            && (
                str_contains($question, 'mas')
                || str_contains($question, 'mayor')
                || str_contains($question, 'top')
                || str_contains($question, 'ranking')
            );

        return $asksForUsers && $asksForObservationRanking;
    }

    private function asksForVerifiedPlantsWithoutScientificName(string $question): bool
    {
        $asksForPlants = str_contains($question, 'planta')
            || str_contains($question, 'registro');

        $asksForMissingScientificName = str_contains($question, 'cientifico')
            && (
                str_contains($question, 'sin')
                || str_contains($question, 'no tienen')
                || str_contains($question, 'no tiene')
                || str_contains($question, 'falta')
                || str_contains($question, 'vacio')
            );

        return $asksForPlants
            && str_contains($question, 'verificad')
            && $asksForMissingScientificName;
    }

    private function topObservationAuthors(): string
    {
        $rows = DB::table('observations')
            ->join('users', 'users.id', '=', 'observations.author_user_id')
            ->select([
                'users.handle',
                'users.display_name',
            ])
            ->selectRaw('COUNT(observations.id) as observations_count')
            ->selectRaw(
                'SUM(CASE WHEN observations.source_type = ? THEN 1 ELSE 0 END) as followup_observations_count',
                [ObservationSourceType::UPDATE->value],
            )
            ->groupBy('users.id', 'users.handle', 'users.display_name')
            ->orderByDesc('observations_count')
            ->orderBy('users.handle')
            ->limit(10)
            ->get();

        if ($rows->isEmpty()) {
            return 'Usuarios con más observaciones: no hay observaciones registradas todavía.';
        }

        $lines = $rows
            ->values()
            ->map(function (object $row, int $index): string {
                $total = (int) $row->observations_count;
                $followup = (int) $row->followup_observations_count;

                return sprintf(
                    '%d. %s (@%s): %s; %s de seguimiento.',
                    $index + 1,
                    $row->display_name,
                    $row->handle,
                    $this->plural($total, 'observación', 'observaciones'),
                    $followup,
                );
            })
            ->implode("\n");

        return "Usuarios con más observaciones:\n{$lines}\nNota: el total cuenta todas las filas de observations; la cifra de seguimiento excluye la observación inicial automática de cada reporte.";
    }

    private function verifiedPlantsWithoutScientificName(): string
    {
        $rows = DB::table('plant_records')
            ->leftJoin('users as authors', 'authors.id', '=', 'plant_records.created_by_user_id')
            ->select([
                'plant_records.public_id',
                'plant_records.provisional_common_name',
                'plant_records.verified_common_name',
                'authors.handle as author_handle',
            ])
            ->whereNull('plant_records.deleted_at')
            ->where('plant_records.verification_status', VerificationStatus::VERIFIED->value)
            ->whereRaw("TRIM(COALESCE(plant_records.verified_scientific_name, '')) = ''")
            ->orderByDesc('plant_records.verified_at')
            ->orderBy('plant_records.public_id')
            ->limit(20)
            ->get();

        if ($rows->isEmpty()) {
            return 'Plantas verificadas sin nombre científico: no hay registros verificados con el nombre científico vacío.';
        }

        $lines = $rows
            ->values()
            ->map(function (object $row, int $index): string {
                $name = $row->verified_common_name ?: $row->provisional_common_name;

                return sprintf(
                    '%d. %s (%s), creada por @%s.',
                    $index + 1,
                    $name,
                    $row->public_id,
                    $row->author_handle,
                );
            })
            ->implode("\n");

        return "Plantas verificadas sin nombre científico:\n{$lines}";
    }

    private function plural(int $count, string $singular, string $plural): string
    {
        return sprintf('%d %s', $count, $count === 1 ? $singular : $plural);
    }
}
