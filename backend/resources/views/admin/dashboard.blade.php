<x-admin.layout title="Resumen · Plantaria Admin">
    <style>
        .dashboard-stack { display: grid; gap: 18px; }
        .hero-panel { display: flex; align-items: flex-start; justify-content: space-between; gap: 18px; }
        .eyebrow { margin: 0 0 6px; font-size: .78rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: var(--earth); }
        .hero-panel h1 { margin-bottom: 8px; }
        .analytics-grid { display: grid; grid-template-columns: minmax(0, 1.6fr) minmax(300px, .9fr); gap: 18px; }
        .section-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 14px; }
        .section-head h2 { margin: 0; }
        .badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; border-radius: 999px; background: #eef4eb; color: var(--leaf); font-size: .82rem; font-weight: 700; }
        .metric-card { position: relative; overflow: hidden; }
        .metric-card::after { content: ""; position: absolute; inset: auto -20px -40px auto; width: 120px; height: 120px; border-radius: 999px; background: radial-gradient(circle, rgba(47,111,62,.14), transparent 68%); }
        .metric-sub { margin-top: 8px; font-size: .88rem; color: var(--muted); }
        .mini-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; }
        .mini-metric { padding: 16px; border-radius: 14px; background: linear-gradient(180deg, #ffffff 0%, #f0f5ec 100%); border: 1px solid #dbe3d5; }
        .mini-metric .value { font-size: 1.55rem; font-weight: 800; color: #1f2a1f; }
        .chart-card { display: grid; gap: 14px; }
        .bar-chart { display: grid; grid-template-columns: repeat(14, minmax(0, 1fr)); gap: 10px; align-items: end; min-height: 240px; padding-top: 18px; }
        .bar-column { display: grid; gap: 8px; justify-items: center; }
        .bar { width: 100%; max-width: 34px; min-height: 12px; border-radius: 10px 10px 4px 4px; background: linear-gradient(180deg, #7ebd89 0%, #2f6f3e 100%); box-shadow: inset 0 -8px 16px rgba(16, 44, 24, .18); }
        .bar-meta { font-size: .72rem; color: var(--muted); text-align: center; }
        .bar-value { font-size: .8rem; font-weight: 800; color: var(--leaf); }
        .hour-chart { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 10px; }
        .hour-cell { padding: 10px; border-radius: 12px; border: 1px solid #dbe3d5; background: #fafcf8; }
        .hour-top { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 8px; font-size: .82rem; color: var(--muted); }
        .hour-track { height: 8px; border-radius: 999px; background: #e6ede2; overflow: hidden; }
        .hour-fill { height: 100%; border-radius: 999px; background: linear-gradient(90deg, #c69a63 0%, #8a5a2b 100%); }
        .rank-table td strong { display: block; }
        .search-type { text-transform: uppercase; letter-spacing: .04em; font-size: .72rem; font-weight: 800; color: var(--earth); }
        .coverage { display: grid; gap: 8px; }
        .coverage-track { height: 10px; border-radius: 999px; background: #e1e8dc; overflow: hidden; }
        .coverage-fill { height: 100%; border-radius: 999px; background: linear-gradient(90deg, #a1c56f 0%, #2f6f3e 100%); }
        @media (max-width: 920px) { .hero-panel, .analytics-grid { grid-template-columns: 1fr; display: grid; } }
        @media (max-width: 760px) { .bar-chart { gap: 6px; } .hour-chart { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    </style>

    <div class="dashboard-stack">
        <section class="card hero-panel">
            <div>
                <p class="eyebrow">Panel de control</p>
                <h1>Resumen operativo</h1>
                <p class="muted" style="margin: 0; max-width: 62ch;">Moderación, uso diario y señales de actividad del sistema en una sola vista.</p>
            </div>
            <div class="actions">
                <a class="button" href="{{ route('admin.moderation.pending') }}">Abrir cola</a>
                <a class="button secondary" href="{{ route('admin.flags.index') }}">Ver flags</a>
                @if (auth()->user()->isAdmin())
                    <a class="button secondary" href="{{ route('admin.users.index') }}">Gestionar usuarios</a>
                @endif
            </div>
        </section>

        <div class="grid">
            <div class="card metric-card">
                <div class="metric">{{ $pendingRecords }}</div>
                <div class="muted">Registros pendientes</div>
                <div class="metric-sub">Bandeja que sigue necesitando revisión.</div>
            </div>
            <div class="card metric-card">
                <div class="metric">{{ $verifiedRecords }}</div>
                <div class="muted">Registros verificados</div>
                <div class="metric-sub">Contenido ya validado para la demo y el mapa.</div>
            </div>
            <div class="card metric-card">
                <div class="metric">{{ $rejectedRecords }}</div>
                <div class="muted">Registros rechazados</div>
                <div class="metric-sub">Elementos descartados por moderación.</div>
            </div>
            <div class="card metric-card">
                <div class="metric">{{ $activeUsers }}</div>
                <div class="muted">Usuarios activos</div>
                <div class="metric-sub">Cuentas en estado activo dentro del sistema.</div>
            </div>
            <div class="card metric-card">
                <div class="metric">{{ $openFlags }}</div>
                <div class="muted">Flags abiertos</div>
                <div class="metric-sub">Incidencias abiertas o en revisión.</div>
            </div>
        </div>

        <div class="mini-grid">
            <div class="mini-metric">
                <div class="value">{{ $activeUsersToday }}</div>
                <div class="muted">Usuarios activos hoy</div>
            </div>
            <div class="mini-metric">
                <div class="value">{{ $newUsersToday }}</div>
                <div class="muted">Altas hoy</div>
            </div>
            <div class="mini-metric">
                <div class="value">{{ $recordsToday }}</div>
                <div class="muted">Registros creados hoy</div>
            </div>
            <div class="mini-metric">
                <div class="value">{{ $observationsToday }}</div>
                <div class="muted">Observaciones hoy</div>
            </div>
        </div>

        <section class="analytics-grid">
            <div class="card chart-card">
                <div class="section-head">
                    <div>
                        <h2>Actividad diaria</h2>
                        <div class="muted">Últimos 14 días de eventos y uso.</div>
                    </div>
                    <span class="badge">Cobertura revisada {{ $reviewCoveragePercent }}%</span>
                </div>

                @if ($dailyActivity->every(fn ($point) => $point['total_events'] === 0))
                    <p class="muted">Todavía no hay suficiente actividad para dibujar la serie diaria.</p>
                @else
                    <div class="bar-chart">
                        @foreach ($dailyActivity as $point)
                            <div class="bar-column" title="{{ $point['day'] }} · {{ $point['total_events'] }} eventos · {{ $point['active_users'] }} usuarios activos">
                                <div class="bar-value">{{ $point['total_events'] }}</div>
                                <div class="bar" style="height: {{ max($point['events_percent'], 8) }}%;"></div>
                                <div class="bar-meta">
                                    <div>{{ $point['label'] }}</div>
                                    <div>{{ $point['active_users'] }} u.</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="card chart-card">
                <div class="section-head">
                    <div>
                        <h2>Actividad por hora</h2>
                        <div class="muted">Franja horaria más cargada del sistema.</div>
                    </div>
                </div>

                <div class="hour-chart">
                    @foreach ($hourlyActivity as $point)
                        <div class="hour-cell" title="{{ $point['hour'] }}:00 · {{ $point['total_events'] }} eventos">
                            <div class="hour-top">
                                <span>{{ $point['hour'] }}:00</span>
                                <strong>{{ $point['total_events'] }}</strong>
                            </div>
                            <div class="hour-track">
                                <div class="hour-fill" style="width: {{ max($point['events_percent'], 4) }}%;"></div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="coverage">
                    <div class="muted">Cobertura de moderación acumulada</div>
                    <div class="coverage-track">
                        <div class="coverage-fill" style="width: {{ $reviewCoveragePercent }}%;"></div>
                    </div>
                </div>
            </div>
        </section>

        <div class="split">
            <div class="card">
                <div class="section-head">
                    <div>
                        <h2>Top búsquedas</h2>
                        <div class="muted">Consultas más repetidas en mapa.</div>
                    </div>
                </div>
                @if ($topSearches->isEmpty())
                    <p class="muted">Todavía no hay búsquedas registradas.</p>
                @else
                    <table class="rank-table">
                        <thead>
                        <tr><th>Consulta</th><th>Tipo</th><th>Total</th></tr>
                        </thead>
                        <tbody>
                        @foreach ($topSearches as $search)
                            <tr>
                                <td><strong>{{ $search->search_query }}</strong></td>
                                <td><span class="search-type">{{ $search->search_type ?? 'n/a' }}</span></td>
                                <td>{{ $search->total }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            <div class="card">
                <div class="section-head">
                    <div>
                        <h2>Creadores destacados</h2>
                        <div class="muted">Usuarios con más registros creados.</div>
                    </div>
                </div>
                @if ($topCreators->isEmpty())
                    <p class="muted">Todavía no hay usuarios con actividad suficiente.</p>
                @else
                    <table class="rank-table">
                        <thead>
                        <tr><th>Usuario</th><th>Registros</th><th>Observaciones</th></tr>
                        </thead>
                        <tbody>
                        @foreach ($topCreators as $creator)
                            <tr>
                                <td>
                                    <strong>{{ '@'.$creator->handle }}</strong>
                                    <span class="muted">{{ $creator->display_name }}</span>
                                </td>
                                <td>{{ $creator->created_records_count }}</td>
                                <td>{{ $creator->observations_count }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="section-head">
                <div>
                    <h2>Actividad reciente</h2>
                    <div class="muted">Últimos eventos registrados por el sistema.</div>
                </div>
            </div>
            @if ($recentEvents->isEmpty())
                <p class="muted">Todavía no hay eventos registrados.</p>
            @else
                <table>
                    <thead><tr><th>Evento</th><th>Rol</th><th>Búsqueda</th><th>Fecha</th></tr></thead>
                    <tbody>
                    @foreach ($recentEvents as $event)
                        <tr>
                            <td>{{ $event->event_type->value }}</td>
                            <td>{{ $event->role_snapshot ?? 'sin usuario' }}</td>
                            <td>{{ $event->search_query ?? '—' }}</td>
                            <td>{{ optional($event->occurred_at)->format('Y-m-d H:i') }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</x-admin.layout>
