<x-admin.layout title="Revisar {{ $record->public_id }} · Plantaria Admin">
    <div class="actions" style="justify-content: space-between; margin-bottom: 16px;">
        <div>
            <h1 style="margin-bottom: 4px;">{{ $record->provisional_common_name }}</h1>
            <p class="muted" style="margin: 0;">{{ $record->public_id }} · estado {{ $record->verification_status->value }}</p>
        </div>
        <a class="button secondary" href="{{ route('admin.moderation.pending') }}">Volver a pendientes</a>
    </div>

    <div class="split">
        <section class="card">
            <img class="record-photo" src="{{ asset('storage/'.$record->primary_photo_path) }}" alt="Foto principal">
            <h2 style="margin-top: 16px;">Datos del reporte</h2>
            <p><strong>Autor:</strong> {{ $record->author?->handle ? '@'.$record->author->handle : 'sin autor' }}</p>
            <p><strong>Coordenadas:</strong> {{ number_format((float) $record->latitude, 7) }}, {{ number_format((float) $record->longitude, 7) }}</p>
            <p><strong>Creado:</strong> {{ optional($record->created_at)->format('Y-m-d H:i') }}</p>
            <p><strong>Descripcion:</strong><br>{{ $record->description ?: 'Sin descripcion.' }}</p>

            <h2>Observaciones</h2>
            @forelse ($record->observations as $observation)
                <div style="display: flex; gap: 12px; margin-bottom: 12px;">
                    <img class="thumb" src="{{ asset('storage/'.$observation->photo_path) }}" alt="Observacion">
                    <div>
                        <strong>{{ optional($observation->observed_at)->format('Y-m-d H:i') }}</strong><br>
                        <span class="muted">{{ $observation->author?->handle ? '@'.$observation->author->handle : 'sin autor' }}</span><br>
                        {{ $observation->note ?: 'Sin nota.' }}
                    </div>
                </div>
            @empty
                <p class="muted">Sin observaciones.</p>
            @endforelse
        </section>

        <aside class="card">
            <h2>Validar ficha</h2>
            <form method="post" action="{{ route('admin.moderation.verify', $record->public_id) }}">
                @csrf
                <label>
                    Nombre comun validado
                    <input name="verified_common_name" value="{{ old('verified_common_name', $record->verified_common_name ?? $record->provisional_common_name) }}" required>
                    @error('verified_common_name')<span class="error">{{ $message }}</span>@enderror
                </label>
                <label>
                    Nombre cientifico
                    <input name="verified_scientific_name" value="{{ old('verified_scientific_name', $record->verified_scientific_name) }}" required>
                    @error('verified_scientific_name')<span class="error">{{ $message }}</span>@enderror
                </label>
                <label>
                    Descripcion revisada
                    <textarea name="description" rows="5">{{ old('description', $record->description) }}</textarea>
                    @error('description')<span class="error">{{ $message }}</span>@enderror
                </label>
                <button type="submit">Verificar registro</button>
            </form>

            <hr style="border: 0; border-top: 1px solid #dbe3d5; margin: 20px 0;">

            <h2>Rechazar</h2>
            <form method="post" action="{{ route('admin.moderation.reject', $record->public_id) }}">
                @csrf
                <label>
                    Nota interna o descripcion corregida
                    <textarea name="description" rows="4">{{ old('description', $record->description) }}</textarea>
                </label>
                <button class="danger" type="submit">Rechazar registro</button>
            </form>
        </aside>
    </div>
</x-admin.layout>
