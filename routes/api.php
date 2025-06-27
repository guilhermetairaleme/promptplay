<?php

use App\Http\Controllers\ChatHistoryController;
use App\Models\Chat;
use App\Models\ChatHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::get('/chats', [ChatHistoryController::class, 'index']);
Route::post('/chats', [ChatHistoryController::class, 'store']);
Route::delete('/chat-history/{id}', [ChatHistoryController::class, 'destroy']);
Route::put('/chats/{id}', [ChatHistoryController::class, 'update']);

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


