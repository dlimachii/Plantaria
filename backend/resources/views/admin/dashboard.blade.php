<x-admin.layout title="Resumen · Plantaria Admin">
    <h1>Resumen</h1>
    <div class="grid">
        <div class="card"><div class="metric">{{ $pendingRecords }}</div><div class="muted">Registros pendientes</div></div>
        <div class="card"><div class="metric">{{ $verifiedRecords }}</div><div class="muted">Registros verificados</div></div>
        <div class="card"><div class="metric">{{ $rejectedRecords }}</div><div class="muted">Registros rechazados</div></div>
        <div class="card"><div class="metric">{{ $activeUsers }}</div><div class="muted">Usuarios registrados</div></div>
        <div class="card"><div class="metric">{{ $openFlags }}</div><div class="muted">Flags abiertos/revision</div></div>
    </div>

    <div class="card" style="margin-top: 18px;">
        <div class="actions" style="justify-content: space-between;">
            <h2 style="margin: 0;">Moderacion pendiente</h2>
            <div class="actions">
                <a class="button" href="{{ route('admin.moderation.pending') }}">Abrir cola</a>
                <a class="button secondary" href="{{ route('admin.flags.index') }}">Ver flags</a>
                @if (auth()->user()->isAdmin())
                    <a class="button secondary" href="{{ route('admin.users.index') }}">Gestionar usuarios</a>
                @endif
            </div>
        </div>
    </div>

    <div class="card" style="margin-top: 18px;">
        <h2>Actividad reciente</h2>
        @if ($recentEvents->isEmpty())
            <p class="muted">Todavia no hay eventos registrados.</p>
        @else
            <table>
                <thead><tr><th>Evento</th><th>Rol</th><th>Fecha</th></tr></thead>
                <tbody>
                @foreach ($recentEvents as $event)
                    <tr>
                        <td>{{ $event->event_type->value }}</td>
                        <td>{{ $event->role_snapshot ?? 'sin usuario' }}</td>
                        <td>{{ optional($event->occurred_at)->format('Y-m-d H:i') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
</x-admin.layout>
