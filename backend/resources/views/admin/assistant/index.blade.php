<x-admin.layout title="Asistente · Plantaria Admin">
    <style>
        .assistant-stack { display: grid; gap: 18px; }
        .assistant-grid { display: grid; grid-template-columns: minmax(0, 1.2fr) minmax(280px, .8fr); gap: 18px; align-items: start; }
        .assistant-meta { display: grid; gap: 10px; }
        .meta-row { padding: 12px; border-radius: 12px; border: 1px solid #dbe3d5; background: #fafcf8; }
        .answer-box { white-space: pre-wrap; line-height: 1.55; }
        .prompt-help { margin-top: 8px; font-size: .9rem; color: var(--muted); }
        .sql-table { display: block; overflow-x: auto; }
        .sql-table table { width: 100%; border-collapse: collapse; }
        .sql-table th, .sql-table td { padding: 8px 10px; border-bottom: 1px solid #dbe3d5; text-align: left; vertical-align: top; }
        .sql-table code { white-space: pre-wrap; word-break: break-word; }
        @media (max-width: 860px) { .assistant-grid { grid-template-columns: 1fr; } }
    </style>

    <div class="assistant-stack">
        <section class="card">
            <div class="actions" style="justify-content: space-between;">
                <div>
                    <p class="muted" style="margin: 0 0 6px;">BBDD segura + Ollama local</p>
                    <h1 style="margin-bottom: 6px;">Asistente de analítica</h1>
                    <p class="muted" style="margin: 0;">Consulta datos administrativos de Plantaria. El snapshot Python+pandas aporta contexto extra para preguntas abiertas.</p>
                </div>
                <a class="button secondary" href="{{ route('admin.dashboard') }}">Volver al resumen</a>
            </div>
        </section>

        <div class="assistant-grid">
            <section class="card">
                <h2>Pregunta</h2>
                <form method="post" action="{{ route('admin.assistant.ask') }}">
                    @csrf
                    @php $assistantAvailable = $ollamaStatus['available'] ?? false; @endphp
                    <label>
                        Consulta administrativa
                        <textarea
                            name="question"
                            rows="5"
                            required
                            placeholder="Ejemplo: ¿qué debería revisar primero antes de la demo?"
                        >{{ old('question', $question) }}</textarea>
                    </label>
                    @error('question')
                        <p class="error">{{ $message }}</p>
                    @enderror
                    <button type="submit">Consultar</button>
                    <p class="prompt-help">Las consultas directas usan tablas acotadas de Plantaria y siguen funcionando aunque Ollama esté apagado. Para preguntas abiertas, modelo: <strong>{{ $ollamaModel }}</strong>. Endpoint: <strong>{{ $ollamaBaseUrl }}</strong>.</p>
                    @if (! $assistantAvailable)
                        <p class="muted" style="margin-top: 10px;">
                            Asistente IA deshabilitado para demo. Las consultas directas seguras siguen disponibles.
                            <span style="display:block; margin-top:6px;">{{ $ollamaStatus['message'] ?? '' }}</span>
                        </p>
                    @endif
                </form>
            </section>

            <section class="card">
                <h2>SQL de solo lectura</h2>
                @if (! $sqlEnabled)
                    <p class="muted">Las consultas SQL seguras están deshabilitadas en este entorno.</p>
                @else
                    <form method="post" action="{{ route('admin.assistant.sql') }}">
                        @csrf
                        <label>
                            Consulta SQL
                            <textarea
                                name="sql_query"
                                rows="6"
                                required
                                placeholder="SELECT public_id, provisional_common_name, verification_status FROM plant_records ORDER BY created_at DESC LIMIT 10"
                            >{{ old('sql_query', $sqlQuery) }}</textarea>
                        </label>
                        @error('sql_query')
                            <p class="error">{{ $message }}</p>
                        @enderror
                        <button type="submit">Ejecutar SQL segura</button>
                        <p class="prompt-help">Solo `SELECT`, `WITH` o `EXPLAIN`. Se bloquean escrituras, múltiples sentencias y palabras clave peligrosas.</p>
                    </form>
                @endif
            </section>

            <aside class="card">
                <h2>Contexto disponible</h2>
                @if ($report['available'] ?? false)
                    @php $kpis = $report['kpis'] ?? []; @endphp
                    <div class="assistant-meta">
                        <div class="meta-row">
                            <strong>{{ $kpis['events_7d'] ?? 0 }}</strong>
                            <div class="muted">eventos en 7 días</div>
                        </div>
                        <div class="meta-row">
                            <strong>{{ $kpis['pending_records'] ?? 0 }}</strong>
                            <div class="muted">registros pendientes</div>
                        </div>
                        <div class="meta-row">
                            <strong>{{ $kpis['open_flags'] ?? 0 }}</strong>
                            <div class="muted">flags abiertos</div>
                        </div>
                        <div class="meta-row">
                            <strong>{{ $kpis['verification_rate'] ?? 0 }}%</strong>
                            <div class="muted">cobertura revisada</div>
                        </div>
                    </div>
                @else
                    <p class="muted">{{ $report['message'] ?? 'No hay analítica generada.' }}</p>
                    <p class="muted">La consulta directa a BBDD funciona igualmente. El snapshot pandas solo aporta contexto extra.</p>
                    <p class="muted">Genéralo desde el dashboard o con <code>php artisan plantaria:analytics:build</code>.</p>
                @endif
            </aside>
        </div>

        @if ($assistantError)
            <section class="card">
                <h2>Error de asistente</h2>
                <p class="error">{{ $assistantError }}</p>
            </section>
        @endif

        @if ($answer)
            <section class="card">
                <h2>Respuesta</h2>
                <div class="answer-box">{{ $answer }}</div>
            </section>
        @endif

        @if ($sqlError)
            <section class="card">
                <h2>Error SQL</h2>
                <p class="error">{{ $sqlError }}</p>
            </section>
        @endif

        @if ($sqlRowCount !== null)
            <section class="card">
                <div class="actions" style="justify-content: space-between;">
                    <h2 style="margin: 0;">Resultado SQL</h2>
                    <span class="muted">{{ $sqlRowCount }} fila(s)@if($sqlTruncated) · mostrando las primeras {{ count($sqlRows) }}@endif</span>
                </div>
                @if (empty($sqlRows))
                    <p class="muted" style="margin-top: 14px;">La consulta es válida, pero no devolvió filas.</p>
                @else
                    <div class="sql-table" style="margin-top: 14px;">
                        <table>
                            <thead>
                                <tr>
                                    @foreach ($sqlColumns as $column)
                                        <th>{{ $column }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($sqlRows as $row)
                                    <tr>
                                        @foreach ($sqlColumns as $column)
                                            <td><code>{{ $row[$column] ?? null }}</code></td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        @endif
    </div>
</x-admin.layout>
