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

set_time_limit(0);

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

    // Início do prompt enviado para o modelo
    $prompt = <<<EOT
    You are a prompt generator for realistic videos used in Google's Flow system. Every prompt you generate must follow the same structure and be written in English, with dialogue in Portuguese only.
    Use strong Brazilian tone when needed.
    You are given a fixed joke as the base. Your task is to generate a complete Flow-ready prompt using the joke, adapting the characters, setting, lighting, and style according to the user's inputs.
    NEVER change the joke's core punchline or dialogue, only change how it's presented.

    Always return the prompt in this exact format:
    # VIDEO STYLE
    # CHARACTERS
    # ENVIRONMENT
    # SCENE DESCRIPTION
    # DIALOGUE (PORTUGUESE ONLY)

    ⚠️ IMPORTANT:
    - DO NOT use JSON format for the CHARACTERS section.
    - Describe each character as a bullet point, using rich, natural, cinematic language.
    - Each bullet should include: gender, age, hairstyle, expression, clothing, accessories, and actions.
    - Use bullet points (•) and natural language like:
    • Garçom: Jovem sorridente com camisa xadrez e avental preto, servindo hambúrgueres com simpatia.

    Now, generate a Flow prompt for the following joke:
    "{$joke}"

    Apply the following modifications:
    EOT;
    foreach ($request->except('joke', 'extra') as $key => $value) {
        if (!empty($value)) {
            $label = ucfirst(str_replace('_', ' ', $key));

            if ($key === 'characters') {
                $prompt .= "\n- {$label}: Use EXACTLY this description for the characters, word by word, without rephrasing or changing anything: \"{$value}\"";
            } else {
                $prompt .= "\n- {$label}: {$value}";
            }
        }
    }

    if (!empty($extra)) {
        $prompt .= "\n\nAdditional instructions:\n{$extra}";
    }

    $prompt .= "\n\nDO NOT alter the character description under any circumstance.";

    // Envia para OpenAI
    $response = Http::withToken(env('OPENAI_API_KEY'))->post('https://api.openai.com/v1/chat/completions', [
        'model' => 'gpt-4o',
        'messages' => [
            ['role' => 'system', 'content' => 'You are a Flow prompt generator.'],
            ['role' => 'user', 'content' => $prompt],
        ],
    ]);

    $generatedPrompt = $response['choices'][0]['message']['content'] ?? 'Erro ao gerar prompt.';

    return response()->json([
        'prompt' => $generatedPrompt
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
    $baseContext = $request->input('prompt');           // texto extra opcional
    $imageBase64 = $request->input('image');            // data:image/jpeg;base64,...

    // ---------- 1. SYSTEM MESSAGE ----------
    $system = [
        'role'    => 'system',
        // 🔄 ALTERADO – reforço de micro-detalhes em roupas/estampas
        'content' => <<<SYS
Você é um analista de imagem cinematográfica. Descreva cada pixel que seja significativo:
• texturas, reflexos, direção da luz, cor de fundo, profundidade de campo, granulação, contraste, foco/desfoque;
• para cada pessoa: tom_de_pele, cor/tipo de cabelo, musculatura, braços, coxa, peito, abdomen, caracteristicas_extras;
• **ROUPA (obrigatório)**: tipo, cor predominante, estilo visual e, se houver, **estampa ou padrão** (diga exatamente qual – floral, geométrico, listrado, poá, etc. – com cores, escala e localização), **dizeres/letras** (texto completo, cor, posição) e **formas gráficas** (caveira, coração, logotipo, emoji, listras laterais, brasão – detalhe cor/posição).
Nunca invente informações: se não estiver visível, não inclua esse campo. Responda somente com JSON válido e sem comentários.
SYS
    ];

    $systemDialogue = [
        'role'    => 'system',
        'content' => 'Quando preencher o campo "dialogue", copie literalmente a piada encontrada na imagem (todas as letras, acentos e pontuação). NÃO altere, resuma, nem acrescente nada. Use letras maiúsculas se estiver assim na imagem.'
    ];

    // ---------- 2. PROMPTS ----------
    $promptWithImage = <<<EOT
Analise a imagem como se fosse um _frame_ RAW de cinema.

**RETORNE UM JSON com estes campos, na ordem exata**:
1. "video_type"
2. "clima"
3. "horario_dia"
4. "setting"
5. "narration"
6. "characters"
7. "secondary_characters"
8. "visual_style"
9. "subject"
10. "objective"
11. "dialogue"

### Como preencher "characters":
- Liste um bullet **•** por animal ou pessoa visível.
- Para **animais** inclua, se possível:
  • `especie` (ex: "cavalo")
  • `raca` (ex: "Gypsy Vanner") - se não for reconhecível, omita
  • `pelagem` / `cor`
  • `textura_do_pelo`
  • `caracteristicas_fisicas` (crina, músculos, cauda, etc.)
  • `pose`, `direcao_olhar`, `acao`
  • `acessorios` (ex: cabeçada, sela)
  • `caracteristicas_extras` (franjas, cicatrizes, manchas)

Para pessoas, inclua:
• sexo (se visível)
• tom_de_pele (ex: claro, médio, bronzeado, escuro)
• cor_cabelo e tipo_cabelo (ex: "liso castanho", "ondulado loiro platinado")
• roupa - descreva:
- tipo (ex: vestido, terno, camiseta)
- cor predominante
- estilo visual (ex: casual, elegante, formal, esportivo)
- estampa ou padrão, se houver: descreva com riqueza — ex: "floral com flores grandes em tons de rosa e azul espalhadas por toda a frente da blusa" ou "geométrico com triângulos pretos e cinzas formando listras diagonais"
- dizeres ou letras visíveis: exemplo "escrita 'NYC' em letras brancas, centralizada na altura do peito"
- formas gráficas (ex: caveiras, estrelas, corações, faixas, brasões, listras laterais, emojis, logotipos, etc.), incluindo cores e posição
- **estampa_ou_padrao** detalhada (cores, motivo, localização);
- **dizeres_ou_letras_visiveis** (texto completo, posição, cor) ou null;
- **formas_graficas** (caveira, coração, logotipo, faixas, emojis, etc.) ou null.
Exemplo aprimorado:

“camiseta branca com estampa frontal de caveira vermelha estilizada, centralizada no peito, com escrita ‘REBEL’ em preto logo abaixo; padrão respingado cinza nas mangas”

• pose (ex: sentado, de pé, em movimento)
• direcao_olhar (ex: para a frente, para a esquerda)
• expressao (ex: sorrindo, sério, surpreso)
• acao (ex: caminhando, segurando objeto, abraçando)

• acessorios (ex: brinco, colar, relógio, óculos, chapéu, taça de vinho)

• musculatura (ex: definida, atlética, forte, mediana, pouco definida)

• braços:
- volume (ex: musculoso, fino, médio)
- veias_visiveis (true/false)
- tatuagens (ex: [{ local: "antebraço direito", tipo: "tribal", cor: "preto" }])

• coxa:
- volume (ex: grossa, média, fina)
- definicao_muscular (ex: alta, moderada, baixa)

• peito (ex: largo e definido, estreito, atlético)
• abdomen (ex: tanquinho, liso, barriga saliente)
• caracteristicas_extras (ex: barba, maquiagem, unhas pintadas, piercings, cicatrizes, tatuagens adicionais)

  Nunca chute informações: se não for visível, **não inclua** o campo.
  • `pose`, `direcao_olhar`, `expressao`, `acao`
  • `acessorios` (ex: brinco, taça de vinho, relógio, colar)
  • `caracteristicas_extras` (ex: barba, unhas pintadas, maquiagem, tatuagens)

- Nunca chute informações: se não for visível, **não inclua** o campo.

- Para **objetos relevantes** (ex: taças, pratos, alimentos, móveis, adornos), inclua:
  • `tipo` (ex: "taça de vinho", "prato de salada", "abajur")
  • `material` (ex: vidro, cerâmica, madeira, tecido)
  • `cor` (ex: transparente, branco, dourado, verde escuro)
  • `textura` (ex: lisa, brilhante, rugosa, fosca)
  • `estado` (ex: limpo, usado, brilhando, molhado)
  • `interacao` (ex: "sendo segurado pela mulher", "em cima da mesa", "encostado na janela")
  • `detalhes_visuais` (ex: "bordas douradas", "decoração floral", "reflexos de luz", "com comida servida")

- dialogue deve conter apenas a piada final completa, sem comentários, e sempre em letras maiúsculas, se for uma imagem textual.

### Regras
- Descreva texturas, sombras, fundo, luz, reflexos e foco/desfoque.
- Responda somente com JSON puro (sem Markdown ou comentários).
EOT;

    $promptOnlyText = <<<EOT
Imagine uma cena para vídeo e descreva-a de forma extremamente visual. Retorne JSON contendo:
video_type, clima, horario_dia, setting, narration, characters, secondary_characters, visual_style, subject, objective, dialogue.
EOT;

    // ---------- 3. MONTA ARRAY $messages ----------
    $messages = [$system,$systemDialogue];

    if ($imageBase64) {
        $userText = $promptWithImage;
        if ($baseContext) {
            $userText .= " Além disso, considere este contexto: \"$baseContext\"";
        }

        $messages[] = [
            'role'    => 'user',
            'content' => [
                ['type' => 'text',      'text' => $userText],
                ['type' => 'image_url', 'image_url' => ['url' => $imageBase64]]
            ]
        ];
    } else {
        $userText = $promptOnlyText;
        if ($baseContext) {
            $userText .= "\n\nContexto adicional: \"$baseContext\"";
        }

        $messages[] = [
            'role'    => 'user',
            'content' => $userText
        ];
    }

    // ---------- 4. CHAMADA À API ----------
    $response = Http::withToken(env('OPENAI_API_KEY'))->post(
        'https://api.openai.com/v1/chat/completions',
        [
            'model'           => 'gpt-4o',
            'messages'        => $messages,
            'temperature'     => 0.5,     // equilíbrio entre fidelidade e riqueza narrativa
            'max_tokens'      => 1500,
            'response_format' => ['type' => 'json_object']
        ]
    );

    // ---------- 5. PARSE DA RESPOSTA ----------
    $jsonText = $response['choices'][0]['message']['content'] ?? '{}';
    Log::alert("Resposta bruta: $jsonText");

    $data = json_decode($jsonText, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return response()->json([
            'error'            => 'Erro ao interpretar a resposta como JSON.',
            'respostaOriginal' => $jsonText
        ], 500);
    }

    // ---------- 6. NORMALIZAÇÃO OPCIONAL ----------
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $isFlat = array_keys($value) === range(0, count($value) - 1) &&
                      array_reduce($value, fn($c, $i) => $c && (is_scalar($i) || is_null($i)), true);
            $data[$key] = $isFlat ? implode(', ', $value)
                                  : json_encode($value, JSON_UNESCAPED_UNICODE);
        }
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
