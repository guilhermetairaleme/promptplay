<?php

use App\Http\Controllers\ProfileController;
use App\Models\Joke;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('chat', function () {
    return Inertia::render('Chat');
})->name('chat');

Route::post('/chatgpt', function (Request $request) {
    Log::info('Mensagem recebida: ' . $request->input('message'));

    $response = Http::withToken(env('OPENAI_API_KEY'))
        ->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4o',
            'messages' => [
                ['role' => 'user', 'content' => $request->input('message')],
            ],
        ]);

    Log::info('Resposta do OpenAI: ' . json_encode($response->json()));

    return response()->json([
        'response' => $response->json()['choices'][0]['message']['content'] ?? 'Erro na resposta',
    ]);
});

Route::post('/api/finalize-prompt', function (Request $request) {
    $prompt = $request->input('prompt');

    $response = Http::withToken(env('OPENAI_API_KEY'))->post('https://api.openai.com/v1/chat/completions', [
        'model' => 'gpt-4o',
        'messages' => [
            [
                'role' => 'system',
                'content' => 'Translate all the narration, descriptions, and character names to English using informal, natural language – the way people talk in real life, not formal textbook style. Keep all character dialogues (everything inside quotes) in Portuguese, but translate only the character names inside those dialogues to English.'

            ],
            ['role' => 'user', 'content' => $prompt],
        ],
    ]);

    $translated = $response['choices'][0]['message']['content'] ?? 'Erro ao traduzir.';

    return response()->json(['prompt_en' => $translated]);
});

Route::get('/api/jokes', fn() => Joke::all());

Route::post('/api/generate-prompt', function (Request $request) {
    $joke = $request->input('joke');
    $extra = $request->input('extra');

    $prompt = <<<EOT
You are a prompt generator for realistic videos used in Google's Flow system. Every prompt you generate must follow the same structure and be written in English, with dialogue in Portuguese only.
Use strong Northeast Brazilian tone when needed.
You are given a fixed joke as the base. Your task is to generate a complete Flow-ready prompt using the joke, adapting the characters, setting, lighting, and style according to the user's inputs.
NEVER change the joke's core punchline or dialogue, only change how it's presented.
Always return the prompt in this format:
# VIDEO STYLE
# CHARACTERS
# ENVIRONMENT
# SCENE DESCRIPTION
# DIALOGUE (PORTUGUESE ONLY)

Now, generate a Flow prompt for the following joke:
"{$joke}"

Apply the following modifications:
EOT;

    foreach ($request->except('joke', 'extra') as $key => $value) {
        if (!empty($value)) {
            $prompt .= "\n- " . ucfirst(str_replace('_', ' ', $key)) . ": {$value}";
        }
    }

    if (!empty($extra)) {
        $prompt .= "\n\nAdditional details:\n{$extra}";
    }

    $response = Http::withToken(env('OPENAI_API_KEY'))->post('https://api.openai.com/v1/chat/completions', [
        'model' => 'gpt-4o',
        'messages' => [
            ['role' => 'system', 'content' => 'You are a Flow prompt generator.'],
            ['role' => 'user', 'content' => $prompt],
        ],
    ]);

    return response()->json([
        'prompt' => $response['choices'][0]['message']['content'] ?? 'Erro ao gerar prompt.'
    ]);
});

Route::post('/api/generate-joke', function (Request $request) {
    $baseJoke = $request->input('prompt');

    // Prompt com exigência de apelidos separados por dois-pontos e aspas
    $promptFinal = $baseJoke
        ? "Com base nesta descrição de comédia estilo Flow: \"$baseJoke\", gere os campos a seguir em formato JSON: joke, video_type, clima, horario_dia, setting, narration, characters, secondary_characters, visual_style, subject, objective, dialogue. No campo 'characters', use o seguinte formato: NomeReal: conhecido como \"Apelido\" (ex: Tarzan: conhecido como \"Tata\"). Use os apelidos nas descrições (scene description), mas nas falas (dialogue), use o nome real dos personagens. Linguagem informal. Responda apenas com o JSON, sem explicações."
        : "Gere uma piada estilo Flow com personagens com apelidos no seguinte formato: NomeReal: conhecido como \"Apelido\" (ex: Carla: conhecido como \"Cacá\", Gorila: conhecido como \"Guri\"). No campo 'characters' use esse formato. Use o apelido na ambientação e na narração, mas nas falas, use o nome real do personagem. Preencha os campos em JSON: joke, video_type, clima, horario_dia, setting, narration, characters, secondary_characters, visual_style, subject, objective e dialogue. Linguagem informal. Responda apenas com o JSON.";

    $response = Http::withToken(env('OPENAI_API_KEY'))->post('https://api.openai.com/v1/chat/completions', [
        'model' => 'gpt-4o',
        'messages' => [
            ['role' => 'user', 'content' => $promptFinal],
        ],
    ]);

    $fullText = $response['choices'][0]['message']['content'] ?? '';
    Log::alert($fullText);

    // Extrai apenas o JSON do texto retornado
    preg_match('/\{.*\}/s', $fullText, $matches);
    $jsonText = $matches[0] ?? '{}';

    $data = json_decode($jsonText, true);

    // Verifica se a resposta é um JSON válido
    if (json_last_error() !== JSON_ERROR_NONE) {
        return response()->json([
            'error' => 'Erro ao interpretar a resposta como JSON.',
            'respostaOriginal' => $fullText
        ], 500);
    }

    // Converte arrays para strings, se necessário
    foreach ($data as $key => $value) {
        if (is_array($value)) {
          if (array_keys($value) === range(0, count($value) - 1)) {
                // Garante que todos os valores são strings simples
                $isFlat = array_reduce($value, function ($carry, $item) {
                    return $carry && (is_scalar($item) || is_null($item));
                }, true);

                $data[$key] = $isFlat
                    ? implode(', ', $value)
                    : json_encode($value, JSON_UNESCAPED_UNICODE);
            } else {
                $data[$key] = json_encode($value, JSON_UNESCAPED_UNICODE);
            }
        }
    }

    // Mantém a piada original se enviada
    if ($baseJoke) {
        $data['joke'] = $baseJoke;
    }

    return response()->json($data);
});


Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/prompt-generator', function () {
        return Inertia::render('PromptGenerator');
    })->name('prompt.generator');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
