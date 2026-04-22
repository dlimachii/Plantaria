<x-admin.layout title="Entrar · Plantaria Admin">
    <div class="card" style="max-width: 460px; margin: 48px auto;">
        <h1>Panel de moderacion</h1>
        <p class="muted">Acceso para moderadores y administradores de Plantaria.</p>

        <form method="post" action="{{ route('admin.login.store') }}">
            @csrf
            <label>
                Handle o email
                <input name="login" value="{{ old('login') }}" autocomplete="username" required autofocus>
                @error('login')<span class="error">{{ $message }}</span>@enderror
            </label>
            <label>
                Contrasena
                <input name="password" type="password" autocomplete="current-password" required>
                @error('password')<span class="error">{{ $message }}</span>@enderror
            </label>
            <label style="display: flex; align-items: center; gap: 8px; font-weight: 600;">
                <input type="checkbox" name="remember" value="1" style="width: auto;"> Recordar sesion
            </label>
            <button type="submit">Entrar</button>
        </form>
    </div>
</x-admin.layout>
