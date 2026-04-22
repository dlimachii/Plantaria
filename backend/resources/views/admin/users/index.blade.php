<x-admin.layout title="Usuarios · Plantaria Admin">
    <div class="actions" style="justify-content: space-between; margin-bottom: 16px;">
        <div>
            <h1 style="margin-bottom: 4px;">Usuarios</h1>
            <p class="muted" style="margin: 0;">Gestion basica de roles y estado de cuentas.</p>
        </div>
        <a class="button secondary" href="{{ route('admin.dashboard') }}">Volver</a>
    </div>

    <div class="card" style="margin-bottom: 16px;">
        <form method="get" class="actions">
            <label style="margin: 0; min-width: 220px;">
                Buscar
                <input name="q" value="{{ $search }}" placeholder="handle, nombre o email">
            </label>
            <label style="margin: 0; min-width: 150px;">
                Rol
                <select name="role" style="width: 100%; border: 1px solid #cdd8c7; border-radius: 10px; padding: 10px 12px; font: inherit;">
                    <option value="">Todos</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->value }}" @selected($selectedRole === $role->value)>{{ $role->value }}</option>
                    @endforeach
                </select>
            </label>
            <label style="margin: 0; min-width: 150px;">
                Estado
                <select name="status" style="width: 100%; border: 1px solid #cdd8c7; border-radius: 10px; padding: 10px 12px; font: inherit;">
                    <option value="">Todos</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected($selectedStatus === $status->value)>{{ $status->value }}</option>
                    @endforeach
                </select>
            </label>
            <button type="submit">Filtrar</button>
            <a class="button secondary" href="{{ route('admin.users.index') }}">Limpiar</a>
        </form>
    </div>

    <div class="card">
        <table>
            <thead><tr><th>Usuario</th><th>Email</th><th>Rol</th><th>Estado</th><th>Actividad</th><th></th></tr></thead>
            <tbody>
            @foreach ($users as $user)
                <tr>
                    <td><strong>{{ '@'.$user->handle }}</strong><br><span class="muted">{{ $user->display_name }}</span></td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->role->value }}</td>
                    <td>{{ $user->status->value }}</td>
                    <td>{{ $user->created_records_count }} registros · {{ $user->observations_count }} obs.</td>
                    <td><a class="button secondary" href="{{ route('admin.users.show', $user->handle) }}">Editar</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div style="margin-top: 14px;">{{ $users->links() }}</div>
    </div>
</x-admin.layout>
