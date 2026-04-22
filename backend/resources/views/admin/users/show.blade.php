<x-admin.layout title="Usuario {{ $managedUser->handle }} · Plantaria Admin">
    <div class="actions" style="justify-content: space-between; margin-bottom: 16px;">
        <div>
            <h1 style="margin-bottom: 4px;">{{ '@'.$managedUser->handle }}</h1>
            <p class="muted" style="margin: 0;">{{ $managedUser->email }} · {{ $managedUser->created_records_count }} registros · {{ $managedUser->observations_count }} observaciones</p>
        </div>
        <a class="button secondary" href="{{ route('admin.users.index') }}">Volver a usuarios</a>
    </div>

    <div class="card" style="max-width: 760px;">
        <form method="post" action="{{ route('admin.users.update', $managedUser->handle) }}">
            @csrf
            <label>
                Nombre visible
                <input name="display_name" value="{{ old('display_name', $managedUser->display_name) }}" required>
                @error('display_name')<span class="error">{{ $message }}</span>@enderror
            </label>
            <div class="grid">
                <label>
                    Rol
                    <select name="role" style="width: 100%; border: 1px solid #cdd8c7; border-radius: 10px; padding: 10px 12px; font: inherit;">
                        @foreach ($roles as $role)
                            <option value="{{ $role->value }}" @selected(old('role', $managedUser->role->value) === $role->value)>{{ $role->value }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    Estado
                    <select name="status" style="width: 100%; border: 1px solid #cdd8c7; border-radius: 10px; padding: 10px 12px; font: inherit;">
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(old('status', $managedUser->status->value) === $status->value)>{{ $status->value }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
            <div class="grid">
                <label>
                    Pais
                    <input name="country" value="{{ old('country', $managedUser->country) }}" required>
                </label>
                <label>
                    Provincia
                    <input name="province" value="{{ old('province', $managedUser->province) }}">
                </label>
                <label>
                    Ciudad
                    <input name="city" value="{{ old('city', $managedUser->city) }}">
                </label>
            </div>
            <button type="submit">Guardar usuario</button>
        </form>
    </div>
</x-admin.layout>
