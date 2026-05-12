<?php

namespace App\Http\Controllers\Web;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AdminReadOnlySqlQuery;
use App\Services\AdminAssistantDirectQuery;
use App\Services\PandasAnalyticsReport;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;
use Illuminate\View\View;

class AdminAssistantController extends Controller
{
    public function index(Request $request, PandasAnalyticsReport $report): View
    {
        $this->ensureAdmin($request);

        $ollamaStatus = $this->ollamaStatus();

        return view('admin.assistant.index', [
            'report' => $report->read(),
            'question' => '',
            'answer' => null,
            'assistantError' => null,
            'ollamaModel' => config('services.ollama.model'),
            'ollamaBaseUrl' => config('services.ollama.base_url'),
            'ollamaStatus' => $ollamaStatus,
            'sqlEnabled' => (bool) config('services.admin_sql.enabled', true),
            'sqlQuery' => '',
            'sqlColumns' => [],
            'sqlRows' => [],
            'sqlRowCount' => null,
            'sqlTruncated' => false,
            'sqlError' => null,
        ]);
    }

    public function ask(Request $request, PandasAnalyticsReport $report, AdminAssistantDirectQuery $directQuery): View
    {
        $this->ensureAdmin($request);

        $ollamaStatus = $this->ollamaStatus();

        $validated = $request->validate([
            'question' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        $context = $report->compactContext();
        $question = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $validated['question']) ?? $validated['question']);
        $answer = null;
        $assistantError = null;
        $directAnswer = $directQuery->answer($question);

        if ($directAnswer !== null) {
            $answer = $directAnswer;
        } elseif (! ($context['available'] ?? false)) {
            $assistantError = 'No he reconocido una consulta directa segura. Para preguntas abiertas, genera primero la analítica con pandas desde el dashboard.';
        } elseif (! ($ollamaStatus['available'] ?? false)) {
            $assistantError = $ollamaStatus['message'] ?? 'El asistente IA no está disponible.';
        } else {
            try {
                $response = Http::timeout(20)->post(
                    rtrim((string) config('services.ollama.base_url'), '/').'/api/generate',
                    [
                        'model' => config('services.ollama.model'),
                        'stream' => false,
                        'prompt' => $this->prompt($question, $context),
                        'options' => [
                            'temperature' => 0.2,
                            'num_predict' => 300,
                        ],
                    ],
                );

                if ($response->successful()) {
                    $answer = $response->json('response') ?: 'Ollama no devolvió texto.';
                } else {
                    $assistantError = 'Ollama respondió con HTTP '.$response->status().'. Revisa que el modelo esté descargado.';
                }
            } catch (ConnectionException) {
                $assistantError = 'No se pudo conectar con Ollama. Arranca ollama serve y descarga el modelo configurado.';
            }
        }

        return $this->assistantView(
            report: $report->read(),
            question: $question,
            answer: $answer,
            assistantError: $assistantError,
            ollamaStatus: $ollamaStatus,
        );
    }

    public function runSql(
        Request $request,
        PandasAnalyticsReport $report,
        AdminReadOnlySqlQuery $sqlQueryService,
    ): View {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'sql_query' => ['required', 'string', 'min:6', 'max:4000'],
        ]);

        $sqlQuery = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $validated['sql_query']) ?? $validated['sql_query']);
        $sqlColumns = [];
        $sqlRows = [];
        $sqlRowCount = null;
        $sqlTruncated = false;
        $sqlError = null;

        try {
            $result = $sqlQueryService->execute($sqlQuery);
            $sqlColumns = $result['columns'];
            $sqlRows = $result['rows'];
            $sqlRowCount = $result['row_count'];
            $sqlTruncated = $result['truncated'];
        } catch (RuntimeException $exception) {
            $sqlError = $exception->getMessage();
        } catch (Throwable $exception) {
            report($exception);
            $sqlError = 'La consulta no se pudo ejecutar. Revisa la sintaxis o limita mejor el alcance.';
        }

        return $this->assistantView(
            report: $report->read(),
            ollamaStatus: $this->ollamaStatus(),
            sqlQuery: $sqlQuery,
            sqlColumns: $sqlColumns,
            sqlRows: $sqlRows,
            sqlRowCount: $sqlRowCount,
            sqlTruncated: $sqlTruncated,
            sqlError: $sqlError,
        );
    }

    private function ollamaStatus(): array
    {
        $enabled = (bool) config('services.ollama.enabled', true);
        $baseUrl = trim((string) config('services.ollama.base_url'));
        $model = trim((string) config('services.ollama.model'));

        if (! $enabled) {
            return [
                'available' => false,
                'message' => 'El asistente IA está deshabilitado para la demo (OLLAMA_ENABLED=false).',
            ];
        }

        if ($baseUrl === '' || $model === '') {
            return [
                'available' => false,
                'message' => 'El asistente IA no está configurado. Revisa OLLAMA_BASE_URL y OLLAMA_MODEL.',
            ];
        }

        try {
            $response = Http::timeout(2)->get(rtrim($baseUrl, '/').'/api/tags');
            if (! $response->successful()) {
                return [
                    'available' => false,
                    'message' => 'Ollama no responde correctamente (HTTP '.$response->status().').',
                ];
            }
        } catch (ConnectionException) {
            return [
                'available' => false,
                'message' => 'Ollama no está accesible. Para la demo puedes dejarlo visible y no usarlo.',
            ];
        }

        return [
            'available' => true,
            'message' => null,
        ];
    }

    private function prompt(string $question, array $context): string
    {
        return implode("\n\n", [
            'Eres un asistente interno del panel admin de Plantaria.',
            'Responde en español, de forma breve y operativa.',
            'Usa solo los datos JSON proporcionados. Si falta un dato, dilo claramente.',
            'Contexto calculado con pandas:',
            json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            'Pregunta del administrador:',
            $question,
        ]);
    }

    private function ensureAdmin(Request $request): User
    {
        /** @var User|null $user */
        $user = $request->user();

        abort_unless($user && $user->role === UserRole::ADMIN, 403);

        return $user;
    }

    private function assistantView(
        array $report,
        array $ollamaStatus,
        string $question = '',
        ?string $answer = null,
        ?string $assistantError = null,
        string $sqlQuery = '',
        array $sqlColumns = [],
        array $sqlRows = [],
        ?int $sqlRowCount = null,
        bool $sqlTruncated = false,
        ?string $sqlError = null,
    ): View {
        return view('admin.assistant.index', [
            'report' => $report,
            'question' => $question,
            'answer' => $answer,
            'assistantError' => $assistantError,
            'ollamaModel' => config('services.ollama.model'),
            'ollamaBaseUrl' => config('services.ollama.base_url'),
            'ollamaStatus' => $ollamaStatus,
            'sqlEnabled' => (bool) config('services.admin_sql.enabled', true),
            'sqlQuery' => $sqlQuery,
            'sqlColumns' => $sqlColumns,
            'sqlRows' => $sqlRows,
            'sqlRowCount' => $sqlRowCount,
            'sqlTruncated' => $sqlTruncated,
            'sqlError' => $sqlError,
        ]);
    }
}
