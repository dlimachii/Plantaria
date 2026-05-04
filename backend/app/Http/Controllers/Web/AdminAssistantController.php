<?php

namespace App\Http\Controllers\Web;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AdminAssistantDirectQuery;
use App\Services\PandasAnalyticsReport;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class AdminAssistantController extends Controller
{
    public function index(Request $request, PandasAnalyticsReport $report): View
    {
        $this->ensureAdmin($request);

        return view('admin.assistant.index', [
            'report' => $report->read(),
            'question' => '',
            'answer' => null,
            'assistantError' => null,
            'ollamaModel' => config('services.ollama.model'),
            'ollamaBaseUrl' => config('services.ollama.base_url'),
        ]);
    }

    public function ask(Request $request, PandasAnalyticsReport $report, AdminAssistantDirectQuery $directQuery): View
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'question' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        $context = $report->compactContext();
        $question = trim($validated['question']);
        $answer = null;
        $assistantError = null;
        $directAnswer = $directQuery->answer($question);

        if ($directAnswer !== null) {
            $answer = $directAnswer;
        } elseif (! ($context['available'] ?? false)) {
            $assistantError = 'No he reconocido una consulta directa segura. Para preguntas abiertas, genera primero la analitica con pandas desde el dashboard.';
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
                    $answer = $response->json('response') ?: 'Ollama no devolvio texto.';
                } else {
                    $assistantError = 'Ollama respondio con HTTP '.$response->status().'. Revisa que el modelo este descargado.';
                }
            } catch (ConnectionException) {
                $assistantError = 'No se pudo conectar con Ollama. Arranca ollama serve y descarga el modelo configurado.';
            }
        }

        return view('admin.assistant.index', [
            'report' => $report->read(),
            'question' => $question,
            'answer' => $answer,
            'assistantError' => $assistantError,
            'ollamaModel' => config('services.ollama.model'),
            'ollamaBaseUrl' => config('services.ollama.base_url'),
        ]);
    }

    private function prompt(string $question, array $context): string
    {
        return implode("\n\n", [
            'Eres un asistente interno del panel admin de Plantaria.',
            'Responde en espanol, de forma breve y operativa.',
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
}
