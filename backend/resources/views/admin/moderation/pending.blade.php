<x-admin.layout title="Pendientes · Plantaria Admin">
    <div class="actions" style="justify-content: space-between; margin-bottom: 16px;">
        <div>
            <h1 style="margin-bottom: 4px;">Registros pendientes</h1>
            <p class="muted" style="margin: 0;">Cola basica para validar nombre comun y cientifico.</p>
        </div>
        <a class="button secondary" href="{{ route('admin.dashboard') }}">Volver</a>
    </div>

    <div class="card">
        @if ($records->isEmpty())
            <p class="muted">No hay registros pendientes ahora mismo.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Foto</th>
                        <th>Registro</th>
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
                        <td><strong>{{ $record->provisional_common_name }}</strong><br><span class="muted">{{ $record->public_id }}</span></td>
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
