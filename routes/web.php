<?php

use App\Http\Controllers\ChatHistoryController;
use App\Http\Controllers\ProfileController;
use App\Http\Middleware\RedirectIfAdmin;
use App\Models\Joke;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
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
                'content' => <<<EOT
                Translate the entire text to English, but follow these strict rules:

                1. All narration, descriptions, character names, and scene details must be translated to **natural, informal English**.
                2. All **dialogues (inside quotes)** must remain in **Portuguese**, without translating the sentences.
                3. However, if there is a **character name inside the dialogue**, translate only that name to English, but keep the rest of the sentence in Portuguese.
                4. Do not add or remove any content. Only translate based on the rules above.

                Example:
                Original: "MARIA: Olá! Como vai você?"
                Translated: "MARY: Olá! Como vai você?"

                Now apply this to the full text.
                EOT

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

// Route::post('/api/generate-joke', function (Request $request) {
//     $baseJoke = $request->input('prompt');

//     // Prompt com exigência de apelidos separados por dois-pontos e aspas
//     $promptFinal = $baseJoke
//         ? "Com base nesta descrição de comédia estilo Flow: \"$baseJoke\", gere os campos a seguir em formato JSON: joke, video_type, clima, horario_dia, setting, narration, characters, secondary_characters, visual_style, subject, objective, dialogue. No campo 'characters', use o seguinte formato: NomeReal: conhecido como \"Apelido\" (ex: Tarzan: conhecido como \"Tata\"). Use os apelidos nas descrições (scene description), mas nas falas (dialogue), use o nome real dos personagens. Linguagem informal. Responda apenas com o JSON, sem explicações."
//         : "Gere uma piada estilo Flow com personagens com apelidos no seguinte formato: NomeReal: conhecido como \"Apelido\" (ex: Carla: conhecido como \"Cacá\", Gorila: conhecido como \"Guri\"). No campo 'characters' use esse formato. Use o apelido na ambientação e na narração, mas nas falas, use o nome real do personagem. Preencha os campos em JSON: joke, video_type, clima, horario_dia, setting, narration, characters, secondary_characters, visual_style, subject, objective e dialogue. Linguagem informal. Responda apenas com o JSON.";

//     $response = Http::withToken(env('OPENAI_API_KEY'))->post('https://api.openai.com/v1/chat/completions', [
//         'model' => 'gpt-4o',
//         'messages' => [
//             ['role' => 'user', 'content' => $promptFinal],
//         ],
//     ]);

//     $fullText = $response['choices'][0]['message']['content'] ?? '';
//     Log::alert($fullText);

//     // Extrai apenas o JSON do texto retornado
//     preg_match('/\{.*\}/s', $fullText, $matches);
//     $jsonText = $matches[0] ?? '{}';

//     $data = json_decode($jsonText, true);

//     // Verifica se a resposta é um JSON válido
//     if (json_last_error() !== JSON_ERROR_NONE) {
//         return response()->json([
//             'error' => 'Erro ao interpretar a resposta como JSON.',
//             'respostaOriginal' => $fullText
//         ], 500);
//     }

//     // Converte arrays para strings, se necessário
//     foreach ($data as $key => $value) {
//         if (is_array($value)) {
//           if (array_keys($value) === range(0, count($value) - 1)) {
//                 // Garante que todos os valores são strings simples
//                 $isFlat = array_reduce($value, function ($carry, $item) {
//                     return $carry && (is_scalar($item) || is_null($item));
//                 }, true);

//                 $data[$key] = $isFlat
//                     ? implode(', ', $value)
//                     : json_encode($value, JSON_UNESCAPED_UNICODE);
//             } else {
//                 $data[$key] = json_encode($value, JSON_UNESCAPED_UNICODE);
//             }
//         }
//     }

//     // Mantém a piada original se enviada
//     if ($baseJoke) {
//         $data['joke'] = $baseJoke;
//     }

//     return response()->json($data);
// });

// COM IMAGEM
Route::post('/api/generate-joke', function (Request $request) {
    $baseJoke = $request->input('prompt');
    $imageBase64 = $request->input('image'); // Base64 com prefixo data:image/jpeg;base64,...

    $messages = [];

    // Prompt para quando há imagem
    $promptWithImage = <<<EOT
Analise cuidadosamente a imagem fornecida. Descreva os personagens com todos os detalhes visuais perceptíveis: tom de pele, cor e tipo de cabelo, expressão facial, vestimenta, idade aproximada, linguagem corporal e postura. Descreva também o ambiente com riqueza de detalhes: móveis, iluminação, estilo visual, cores predominantes e atmosfera. Responda obrigatoriamente com um JSON contendo os seguintes campos: joke, video_type, clima, horario_dia, setting, narration, characters, secondary_characters, visual_style, subject, objective, dialogue. Use linguagem informal e divertida, mas respeitosa. No campo 'characters', inclua todos os detalhes físicos (ex: 'Homem pardo, cabelo raspado, camiseta azul, expressão relaxada'). Responda somente com o JSON, sem explicações extras.
EOT;

    // Prompt alternativo para quando não há imagem
    $promptOnlyText = <<<EOT
Gere uma piada estilo Flow com personagens com apelidos no formato NomeReal: conhecido como "Apelido" (ex: Carla: conhecida como "Cacá"). Descreva os personagens com todos os detalhes possíveis, incluindo cor de pele, cabelo, roupas, expressão facial e postura. Descreva também o cenário e a interação. Responda apenas com um JSON contendo: joke, video_type, clima, horario_dia, setting, narration, characters, secondary_characters, visual_style, subject, objective, dialogue. Linguagem informal. Apenas o JSON.
EOT;

    // Montagem da mensagem com base nos dados disponíveis
    if ($imageBase64) {
        $text = $promptWithImage;

        if ($baseJoke) {
            $text .= " Além disso, considere essa fala ou contexto adicional: \"$baseJoke\"";
        }

        $messages[] = [
            'role' => 'user',
            'content' => [
                ['type' => 'text', 'text' => $text],
                ['type' => 'image_url', 'image_url' => ['url' => $imageBase64]]
            ]
        ];
    } else {
        $text = $promptOnlyText;

        if ($baseJoke) {
            $text .= "\n\nBase de inspiração: \"$baseJoke\"";
        }

        $messages[] = [
            'role' => 'user',
            'content' => $text
        ];
    }

    // Envio para a API do GPT-4o
    $response = Http::withToken(env('OPENAI_API_KEY'))->post('https://api.openai.com/v1/chat/completions', [
        'model' => 'gpt-4o',
        'messages' => $messages,
    ]);

    $fullText = $response['choices'][0]['message']['content'] ?? '';
    Log::alert("Resposta bruta: $fullText");

    // Extrai JSON da resposta
    preg_match('/\{.*\}/s', $fullText, $matches);
    $jsonText = $matches[0] ?? '{}';
    $data = json_decode($jsonText, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return response()->json([
            'error' => 'Erro ao interpretar a resposta como JSON.',
            'respostaOriginal' => $fullText
        ], 500);
    }

    // Normaliza arrays
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            if (array_keys($value) === range(0, count($value) - 1)) {
                $isFlat = array_reduce($value, fn($carry, $item) => $carry && (is_scalar($item) || is_null($item)), true);
                $data[$key] = $isFlat ? implode(', ', $value) : json_encode($value, JSON_UNESCAPED_UNICODE);
            } else {
                $data[$key] = json_encode($value, JSON_UNESCAPED_UNICODE);
            }
        }
    }

    if ($baseJoke) {
        $data['joke'] = $baseJoke;
    }

    return response()->json($data);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', RedirectIfAdmin::class])->group(function () {
    Route::get('/prompt-generator/{name?}', function ($name = null) {
        return Inertia::render('PromptGenerator', ['adminName' => $name]);
    })->name('prompt.generator');
});

Route::middleware('auth')->group(function () {
    Route::get('/chats', [ChatHistoryController::class, 'index']);
    Route::post('/chats', [ChatHistoryController::class, 'store']);
    Route::put('/chats/{id}', [ChatHistoryController::class, 'update']);
    Route::post('/corrigir-prompt', [ChatHistoryController::class, 'corrigir']);
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
