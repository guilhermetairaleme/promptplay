<?php

use App\Http\Controllers\Api\HotmartWebhookController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\ChatHistoryController;
use App\Models\Chat;
use App\Models\ChatHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::post('/hotmart/webhook', [HotmartWebhookController::class, 'handle']);
Route::delete('/chat-history/{id}', [ChatHistoryController::class, 'destroy']);
Route::post('/items/import', [ItemController::class, 'import']);
Route::patch('/chats/{id}/clear-final', function ($id) {
    $chat = ChatHistory::findOrFail($id);
    $chat->final_prompt = null;
    $chat->save();

    return response()->json(['success' => true]);
});

Route::post('/chatgpt', function (Request $request) {
    $message = $request->input('message');

    $response = Http::withToken(env('OPENAI_API_KEY'))
        ->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4o',
            'messages' => [
                ['role' => 'user', 'content' => $message],
            ],
        ]);

    return response()->json([
        'response' => $response->json()['choices'][0]['message']['content'] ?? 'Erro ao consultar a API.'
    ]);
});

Route::post('/criar-personagem', function (Request $request) {
    $nome = $request->input('nome');
    $acao = $request->input('acao');
    $caracteristicas = $request->input('caracteristicas');

    $mensagem = <<<EOT
Crie uma breve descrição criativa para um personagem fictício com base nas informações abaixo:

- Nome: {$nome}
- Ação: {$acao}
- Características: {$caracteristicas}

A descrição deve ser curta (até 3 frases), sem contar uma história completa. Foque apenas em destacar a essência do personagem, de forma divertida e objetiva. Evite introduções, ambientações ou cenas longas.
EOT;

    $response = Http::withToken(env('OPENAI_API_KEY'))->post('https://api.openai.com/v1/chat/completions', [
        'model' => 'gpt-4o',
        'messages' => [
            ['role' => 'user', 'content' => $mensagem],
        ],
    ]);

    return response()->json([
        'descricao' => $response['choices'][0]['message']['content'] ?? 'Erro ao gerar personagem.'
    ]);
});
