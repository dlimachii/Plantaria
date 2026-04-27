<x-admin.layout title="Registros · Plantaria Admin">
    <div class="actions" style="justify-content: space-between; margin-bottom: 16px;">
        <div>
            <h1 style="margin-bottom: 4px;">Registros del panel</h1>
            <p class="muted" style="margin: 0;">Moderacion operativa con filtro por estado y busqueda por ID o nombre.</p>
        </div>
        <a class="button secondary" href="{{ route('admin.dashboard') }}">Volver</a>
    </div>

    <div class="card" style="margin-bottom: 16px;">
        <form class="actions" method="get" action="{{ route('admin.moderation.pending') }}">
            <label style="margin: 0; min-width: 220px; flex: 1 1 220px;">
                Buscar
                <input name="q" value="{{ $search }}" placeholder="ID publico o nombre">
            </label>
            <label style="margin: 0; min-width: 200px;">
                Estado
                <select name="status">
                    <option value="all" @selected($selectedStatus === 'all')>Todos</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected($selectedStatus === $status->value)>{{ $status->value }}</option>
                    @endforeach
                </select>
            </label>
            <div class="actions" style="margin-left: auto; align-self: end;">
                <button type="submit">Aplicar</button>
                <a class="button secondary" href="{{ route('admin.moderation.pending') }}">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="card">
        @if ($records->isEmpty())
            <p class="muted">No hay registros para ese filtro ahora mismo.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Foto</th>
                        <th>Registro</th>
                        <th>Estado</th>
                        <th>Autor</th>
                        <th>Ubicacion</th>
                        <th>Fecha</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($records as $record)
                    <tr>
                        <td><img class="thumb" src="{{ asset('storage/'.$record->primary_photo_path) }}" alt="Foto {{ $record->public_id }}"></td>
                        <td>
                            <strong>{{ $record->verified_common_name ?? $record->provisional_common_name }}</strong><br>
                            <span class="muted">{{ $record->public_id }}</span>
                        </td>
                        <td>{{ $record->verification_status->value }}</td>
                        <td>{{ $record->author?->handle ? '@'.$record->author->handle : 'sin autor' }}</td>
                        <td>{{ number_format((float) $record->latitude, 5) }}, {{ number_format((float) $record->longitude, 5) }}</td>
                        <td>{{ optional($record->created_at)->format('Y-m-d H:i') }}</td>
                        <td><a class="button secondary" href="{{ route('admin.moderation.show', $record->public_id) }}">Revisar</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <div style="margin-top: 14px;">{{ $records->links() }}</div>
        @endif
    </div>
</x-admin.layout>
