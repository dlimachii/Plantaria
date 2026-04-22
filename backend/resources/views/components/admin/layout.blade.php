<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Plantaria Admin' }}</title>
    <style>
        :root { color-scheme: light; --leaf: #2f6f3e; --earth: #8a5a2b; --bg: #f5f7f1; --card: #ffffff; --muted: #65715f; --danger: #a43c2f; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: var(--bg); color: #1f2a1f; }
        a { color: var(--leaf); text-decoration: none; font-weight: 650; }
        header { background: var(--card); border-bottom: 1px solid #dbe3d5; padding: 16px 24px; display: flex; align-items: center; justify-content: space-between; gap: 18px; }
        nav { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
        main { width: min(1120px, calc(100% - 32px)); margin: 24px auto 48px; }
        h1, h2, h3 { margin-top: 0; }
        .brand { color: var(--leaf); font-size: 1.25rem; font-weight: 800; }
        .card { background: var(--card); border: 1px solid #dbe3d5; border-radius: 14px; padding: 18px; box-shadow: 0 8px 24px rgba(20, 40, 20, 0.06); }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 14px; }
        .metric { font-size: 2rem; font-weight: 800; color: var(--leaf); }
        .muted { color: var(--muted); }
        .actions { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
        .button, button { border: 0; border-radius: 10px; background: var(--leaf); color: white; padding: 10px 14px; font-weight: 750; cursor: pointer; }
        .button.secondary, button.secondary { background: #eef4eb; color: var(--leaf); border: 1px solid #cddcc8; }
        .button.danger, button.danger { background: var(--danger); }
        input, textarea { width: 100%; border: 1px solid #cdd8c7; border-radius: 10px; padding: 10px 12px; font: inherit; background: #fff; }
        label { display: grid; gap: 6px; font-weight: 700; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 10px; border-bottom: 1px solid #e1e8dc; text-align: left; vertical-align: top; }
        th { color: var(--muted); font-size: .85rem; text-transform: uppercase; letter-spacing: .04em; }
        .flash { margin-bottom: 16px; border-radius: 12px; padding: 12px 14px; background: #eaf6e9; color: var(--leaf); border: 1px solid #cce6c8; }
        .error { color: var(--danger); font-weight: 650; }
        .record-photo { width: 100%; max-height: 340px; object-fit: cover; border-radius: 12px; border: 1px solid #dbe3d5; background: #eef4eb; }
        .thumb { width: 78px; height: 58px; object-fit: cover; border-radius: 8px; border: 1px solid #dbe3d5; background: #eef4eb; }
        .split { display: grid; grid-template-columns: minmax(0, 1.1fr) minmax(280px, .9fr); gap: 18px; align-items: start; }
        @media (max-width: 760px) { header { align-items: flex-start; flex-direction: column; } .split { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<header>
    <div>
        <a class="brand" href="{{ route('admin.dashboard') }}">Plantaria Admin</a>
        @auth
            <div class="muted">{{ auth()->user()->display_name }} · {{ auth()->user()->role->value }}</div>
        @endauth
    </div>
    @auth
        <nav>
            <a href="{{ route('admin.dashboard') }}">Resumen</a>
            <a href="{{ route('admin.moderation.pending') }}">Pendientes</a>
            <form method="post" action="{{ route('admin.logout') }}">
                @csrf
                <button class="secondary" type="submit">Salir</button>
            </form>
        </nav>
    @endauth
</header>
<main>
    @if (session('status'))
        <div class="flash">{{ session('status') }}</div>
    @endif
    {{ $slot }}
</main>
</body>
</html>
