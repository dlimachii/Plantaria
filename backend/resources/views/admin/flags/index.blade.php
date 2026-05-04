<x-admin.layout title="Flags · Plantaria Admin">
    <div class="actions" style="justify-content: space-between; margin-bottom: 16px;">
        <div>
            <h1 style="margin-bottom: 4px;">Flags de moderacion</h1>
            <p class="muted" style="margin: 0;">Denuncias creadas por usuarios sobre registros, observaciones o cuentas.</p>
        </div>
        <a class="button secondary" href="{{ route('admin.dashboard') }}">Volver</a>
    </div>

    <div class="card" style="margin-bottom: 16px;">
        <form method="get" class="actions">
            <label style="margin: 0; min-width: 220px; flex: 1 1 220px;">
                Buscar
                <input name="q" value="{{ $search }}" placeholder="motivo, usuario, ID o nombre">
            </label>
            <label style="margin: 0; min-width: 220px;">
                Estado
                <select name="status" style="width: 100%; border: 1px solid #cdd8c7; border-radius: 10px; padding: 10px 12px; font: inherit;">
                    <option value="">Todos</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected($selectedStatus === $status->value)>{{ $status->value }}</option>
                    @endforeach
                </select>
            </label>
            <label style="margin: 0; min-width: 190px;">
                Tipo
                <select name="target_type" style="width: 100%; border: 1px solid #cdd8c7; border-radius: 10px; padding: 10px 12px; font: inherit;">
                    <option value="">Todos</option>
                    @foreach ($targetTypes as $targetType)
                        <option value="{{ $targetType->value }}" @selected($selectedTargetType === $targetType->value)>{{ $targetType->value }}</option>
                    @endforeach
                </select>
            </label>
            <button type="submit">Filtrar</button>
            <a class="button secondary" href="{{ route('admin.flags.index') }}">Limpiar</a>
        </form>
    </div>

    <div class="card">
        @if ($flags->isEmpty())
            <p class="muted">No hay flags con este filtro.</p>
        @else
            <table>
                <thead><tr><th>Target</th><th>Motivo</th><th>Estado</th><th>Reporta</th><th>Fecha</th><th>Accion</th></tr></thead>
                <tbody>
                @foreach ($flags as $flag)
                    @php
                        $targetTitle = '#'.$flag->target_id;
                        $targetMeta = null;
                        $targetUrl = null;

                        if ($flag->target_type === \App\Enums\FlagTargetType::RECORD && $flag->record) {
                            $targetTitle = $flag->record->public_id;
                            $targetMeta = $flag->record->verified_common_name ?? $flag->record->provisional_common_name;
                            $targetUrl = route('admin.moderation.show', $flag->record->public_id);
                        } elseif ($flag->target_type === \App\Enums\FlagTargetType::OBSERVATION && $flag->observation) {
                            $targetTitle = $flag->observation->public_id;
                            $targetMeta = $flag->observation->plantRecord
                                ? 'Observacion de '.$flag->observation->plantRecord->public_id
                                : 'Observacion sin registro asociado';
                            $targetUrl = $flag->observation->plantRecord
                                ? route('admin.moderation.show', $flag->observation->plantRecord->public_id).'#observation-'.$flag->observation->uid
                                : null;
                        } elseif ($flag->target_type === \App\Enums\FlagTargetType::USER && $flag->userTarget) {
                            $targetTitle = '@'.$flag->userTarget->handle;
                            $targetMeta = $flag->userTarget->display_name;
                            $targetUrl = auth()->user()->isAdmin()
                                ? route('admin.users.show', $flag->userTarget->handle)
                                : null;
                        }
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $flag->target_type->value }}</strong><br>
                            @if ($targetUrl)
                                <a href="{{ $targetUrl }}">{{ $targetTitle }}</a>
                            @else
                                <span>{{ $targetTitle }}</span>
                            @endif
                            @if ($targetMeta)
                                <br><span class="muted">{{ $targetMeta }}</span>
                            @endif
                        </td>
                        <td>{{ $flag->reason }}</td>
                        <td>{{ $flag->status->value }}</td>
                        <td>{{ $flag->reporter?->handle ? '@'.$flag->reporter->handle : 'sin usuario' }}</td>
                        <td>{{ optional($flag->created_at)->format('Y-m-d H:i') }}</td>
                        <td>
                            <form method="post" action="{{ route('admin.flags.update', $flag->uid) }}" class="actions">
                                @csrf
                                <select name="status" style="border: 1px solid #cdd8c7; border-radius: 10px; padding: 9px; font: inherit;">
                                    <option value="reviewing" @selected($flag->status->value === 'reviewing')>reviewing</option>
                                    <option value="resolved" @selected($flag->status->value === 'resolved')>resolved</option>
                                    <option value="rejected" @selected($flag->status->value === 'rejected')>rejected</option>
                                </select>
                                <button class="secondary" type="submit">Guardar</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <div style="margin-top: 14px;">{{ $flags->links() }}</div>
        @endif
    </div>
</x-admin.layout>
