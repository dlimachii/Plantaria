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
            <label style="margin: 0; min-width: 220px;">
                Estado
                <select name="status" style="width: 100%; border: 1px solid #cdd8c7; border-radius: 10px; padding: 10px 12px; font: inherit;">
                    <option value="">Todos</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected($selectedStatus === $status->value)>{{ $status->value }}</option>
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
                    <tr>
                        <td><strong>{{ $flag->target_type->value }}</strong><br><span class="muted">#{{ $flag->target_id }}</span></td>
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
