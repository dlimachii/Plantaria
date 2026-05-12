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
            <p><strong>Condición:</strong> {{ $record->plant_condition?->value ?? 'unknown' }}</p>
            <p><strong>Coordenadas:</strong> {{ number_format((float) $record->latitude, 7) }}, {{ number_format((float) $record->longitude, 7) }}</p>
            <p><strong>Creado:</strong> {{ optional($record->created_at)->format('Y-m-d H:i') }}</p>
            <p><strong>Última observación:</strong> {{ optional($record->latest_observation_at)->format('Y-m-d H:i') ?: 'Sin datos' }}</p>
            <p><strong>Validado por:</strong> {{ $record->verifier?->handle ? '@'.$record->verifier->handle : 'sin validar' }}</p>
            <p><strong>Descripción:</strong><br>{{ $record->description ?: 'Sin descripción.' }}</p>

            <h2>Observaciones</h2>
            @forelse ($record->observations as $observation)
                <div id="observation-{{ $observation->uid }}" style="display: flex; gap: 12px; margin-bottom: 12px;">
                    <img class="thumb" src="{{ asset('storage/'.$observation->photo_path) }}" alt="Observación">
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
            <h2>Flags relacionados</h2>
            @if ($relatedFlags->isEmpty())
                <p class="muted">No hay denuncias asociadas a este registro.</p>
            @else
                <div style="display: grid; gap: 12px; margin-bottom: 20px;">
                    @foreach ($relatedFlags as $flag)
                        <div style="border: 1px solid #dbe3d5; border-radius: 12px; padding: 12px; background: #fafcf8;">
                            <div class="actions" style="justify-content: space-between; align-items: flex-start;">
                                <strong>{{ $flag->status->value }}</strong>
                                <span class="muted">{{ optional($flag->created_at)->format('Y-m-d H:i') }}</span>
                            </div>
                            <p style="margin: 8px 0;">{{ $flag->reason }}</p>
                            <p class="muted" style="margin: 0;">
                                {{ $flag->target_type->value }}
                                @if ($flag->target_type === \App\Enums\FlagTargetType::OBSERVATION && $flag->observation)
                                    · {{ $flag->observation->public_id }}
                                @endif
                                · reporta {{ $flag->reporter?->handle ? '@'.$flag->reporter->handle : 'sin usuario' }}
                            </p>
                        </div>
                    @endforeach
                </div>
            @endif

            <h2>Historial</h2>
            @if (($moderationEvents ?? collect())->isEmpty())
                <p class="muted">Todavía no hay eventos registrados para este reporte.</p>
            @else
                <div style="display: grid; gap: 10px; margin-bottom: 20px;">
                    @foreach ($moderationEvents as $event)
                        @php
                            $metadata = $event->metadata ?? [];
                            $label = match ($event->event_type) {
                                \App\Enums\EventType::RECORD_CREATED => 'Reporte creado',
                                \App\Enums\EventType::OBSERVATION_CREATED => 'Observación añadida',
                                \App\Enums\EventType::RECORD_UPDATED => 'Edición en panel',
                                \App\Enums\EventType::RECORD_VERIFIED => match ($metadata['verification_status'] ?? null) {
                                    'rejected' => 'Registro rechazado',
                                    default => 'Registro verificado',
                                },
                                default => 'Evento',
                            };
                            $actor = $event->user?->handle ? '@'.$event->user->handle : ($event->role_snapshot ? 'rol '.$event->role_snapshot : 'sistema');
                            $detail = null;

                            if ($event->event_type === \App\Enums\EventType::RECORD_UPDATED) {
                                $previous = $metadata['previous_verification_status'] ?? null;
                                $current = $metadata['verification_status'] ?? null;
                                $detail = ($previous && $current) ? "estado {$previous} → {$current}" : null;
                            }
                        @endphp
                        <div style="border: 1px solid #dbe3d5; border-radius: 12px; padding: 12px; background: #fafcf8;">
                            <div class="actions" style="justify-content: space-between; align-items: flex-start;">
                                <strong>{{ $label }}</strong>
                                <span class="muted">{{ optional($event->occurred_at)->format('Y-m-d H:i') }}</span>
                            </div>
                            <p class="muted" style="margin: 8px 0 0;">
                                {{ $actor }}@if ($detail) · {{ $detail }}@endif
                            </p>
                        </div>
                    @endforeach
                </div>
            @endif

            <hr style="border: 0; border-top: 1px solid #dbe3d5; margin: 20px 0;">

            <h2>Validar ficha</h2>
            <form method="post" action="{{ route('admin.moderation.verify', $record->public_id) }}">
                @csrf
                <label>
                    Nombre común validado
                    <input name="verified_common_name" value="{{ old('verified_common_name', $record->verified_common_name ?? $record->provisional_common_name) }}" required>
                    @error('verified_common_name')<span class="error">{{ $message }}</span>@enderror
                </label>
                <label>
                    Nombre científico
                    <input name="verified_scientific_name" value="{{ old('verified_scientific_name', $record->verified_scientific_name) }}" required>
                    @error('verified_scientific_name')<span class="error">{{ $message }}</span>@enderror
                </label>
                <label>
                    Descripción revisada
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
                    Nota interna o descripción corregida
                    <textarea name="description" rows="4">{{ old('description', $record->description) }}</textarea>
                </label>
                <button class="danger" type="submit">Rechazar registro</button>
            </form>

            @if (auth()->user()->isAdmin())
                <hr style="border: 0; border-top: 1px solid #dbe3d5; margin: 20px 0;">

                <h2>Edición avanzada</h2>
                <form method="post" action="{{ route('admin.moderation.update', $record->public_id) }}">
                    @csrf
                    <label>
                        Nombre provisional
                        <input name="provisional_common_name" value="{{ old('provisional_common_name', $record->provisional_common_name) }}" required>
                        @error('provisional_common_name', 'recordUpdate')<span class="error">{{ $message }}</span>@enderror
                    </label>
                    <div class="grid">
                        <label>
                            Estado
                            <select name="verification_status">
                                @foreach ($statuses as $status)
                                    <option value="{{ $status->value }}" @selected(old('verification_status', $record->verification_status->value) === $status->value)>{{ $status->value }}</option>
                                @endforeach
                            </select>
                            @error('verification_status', 'recordUpdate')<span class="error">{{ $message }}</span>@enderror
                        </label>
                        <label>
                            Condición
                            <select name="plant_condition">
                                @foreach ($conditions as $condition)
                                    <option value="{{ $condition->value }}" @selected(old('plant_condition', $record->plant_condition?->value) === $condition->value)>{{ $condition->value }}</option>
                                @endforeach
                            </select>
                            @error('plant_condition', 'recordUpdate')<span class="error">{{ $message }}</span>@enderror
                        </label>
                    </div>
                    <div class="grid">
                        <label>
                            Nombre común validado
                            <input name="verified_common_name" value="{{ old('verified_common_name', $record->verified_common_name) }}">
                            @error('verified_common_name', 'recordUpdate')<span class="error">{{ $message }}</span>@enderror
                        </label>
                        <label>
                            Nombre científico validado
                            <input name="verified_scientific_name" value="{{ old('verified_scientific_name', $record->verified_scientific_name) }}">
                            @error('verified_scientific_name', 'recordUpdate')<span class="error">{{ $message }}</span>@enderror
                        </label>
                    </div>
                    <label>
                        Ruta de foto principal
                        <input name="primary_photo_path" value="{{ old('primary_photo_path', $record->primary_photo_path) }}" required>
                        @error('primary_photo_path', 'recordUpdate')<span class="error">{{ $message }}</span>@enderror
                    </label>
                    <div class="grid">
                        <label>
                            Latitud
                            <input name="latitude" type="number" step="0.0000001" value="{{ old('latitude', $record->latitude) }}" required>
                            @error('latitude', 'recordUpdate')<span class="error">{{ $message }}</span>@enderror
                        </label>
                        <label>
                            Longitud
                            <input name="longitude" type="number" step="0.0000001" value="{{ old('longitude', $record->longitude) }}" required>
                            @error('longitude', 'recordUpdate')<span class="error">{{ $message }}</span>@enderror
                        </label>
                    </div>
                    <label>
                        Descripción
                        <textarea name="description" rows="5">{{ old('description', $record->description) }}</textarea>
                        @error('description', 'recordUpdate')<span class="error">{{ $message }}</span>@enderror
                    </label>
                    <button type="submit">Guardar cambios</button>
                </form>
            @endif
        </aside>
    </div>
</x-admin.layout>
