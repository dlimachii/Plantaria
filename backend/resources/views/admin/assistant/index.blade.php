<x-admin.layout title="Asistente · Plantaria Admin">
    <style>
        .assistant-stack { display: grid; gap: 18px; }
        .assistant-grid { display: grid; grid-template-columns: minmax(0, 1.2fr) minmax(280px, .8fr); gap: 18px; align-items: start; }
        .assistant-meta { display: grid; gap: 10px; }
        .meta-row { padding: 12px; border-radius: 12px; border: 1px solid #dbe3d5; background: #fafcf8; }
        .answer-box { white-space: pre-wrap; line-height: 1.55; }
        .prompt-help { margin-top: 8px; font-size: .9rem; color: var(--muted); }
        @media (max-width: 860px) { .assistant-grid { grid-template-columns: 1fr; } }
    </style>

    <div class="assistant-stack">
        <section class="card">
            <div class="actions" style="justify-content: space-between;">
                <div>
                    <p class="muted" style="margin: 0 0 6px;">BBDD segura + Ollama local</p>
                    <h1 style="margin-bottom: 6px;">Asistente de analitica</h1>
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
                    <label>
                        Consulta administrativa
                        <textarea name="question" rows="5" required placeholder="Ejemplo: que deberia revisar primero antes de la demo?">{{ old('question', $question) }}</textarea>
                    </label>
                    @error('question')
                        <p class="error">{{ $message }}</p>
                    @enderror
                    <button type="submit">Consultar</button>
                    <p class="prompt-help">Las consultas directas usan tablas acotadas de Plantaria. Si hace falta Ollama, modelo: <strong>{{ $ollamaModel }}</strong>. Endpoint: <strong>{{ $ollamaBaseUrl }}</strong>.</p>
                </form>
            </section>

            <aside class="card">
                <h2>Contexto disponible</h2>
                @if ($report['available'] ?? false)
                    @php $kpis = $report['kpis'] ?? []; @endphp
                    <div class="assistant-meta">
                        <div class="meta-row">
                            <strong>{{ $kpis['events_7d'] ?? 0 }}</strong>
                            <div class="muted">eventos en 7 dias</div>
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
                    <p class="muted">{{ $report['message'] ?? 'No hay analitica generada.' }}</p>
                    <p class="muted">La consulta directa a BBDD funciona igualmente. El snapshot pandas solo aporta contexto extra.</p>
                    <p class="muted">Generala desde el dashboard o con <code>php artisan plantaria:analytics:build</code>.</p>
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
    </div>
</x-admin.layout>
