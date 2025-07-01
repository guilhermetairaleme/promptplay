<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\ChatHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatHistoryController
{
    public function index() {
        return ChatHistory::where('user_id', Auth::user()->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

 public function corrigir(Request $request) {

    $request->validate([
        'prompt_base' => 'required|string',
        'instrucao' => 'required|string',
    ]);

    $promptBase = $request->input('prompt_base');
    $instrucao = $request->input('instrucao');

    $promptFinal = "Aqui está um prompt base gerado anteriormente:\n\n" .
                   "$promptBase\n\n" .
                   "Agora, com base na instrução abaixo, melhore ou ajuste o prompt:\n" .
                   "$instrucao\n\n" .
                   "Gere uma nova versão do prompt com as alterações aplicadas:";

    try {
        $response = Http::withToken(env('OPENAI_API_KEY'))->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4o',
            'messages' => [
                ['role' => 'user', 'content' => $promptFinal],
            ],
        ]);

        $data = $response->json();

        return response()->json([
            'prompt_corrigido' => $data['choices'][0]['message']['content'] ?? 'Não foi possível gerar o prompt corrigido.'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Erro ao consultar a OpenAI',
            'message' => $e->getMessage()
        ], 500);
    }
}


   public function store(Request $request) {
    $request->validate([
        'title' => 'required|string|max:255',
        'joke' => 'nullable|string',
        'fields' => 'nullable|array',
        'extra' => 'nullable|string',
        'prompt' => 'nullable|string',
        'final_prompt' => 'nullable|string',
    ]);

    $chat = ChatHistory::create([
        'user_id' => Auth::user()->id, // 🔗 vincula ao usuário logado
        'title' => $request->input('title'),
        'joke' => $request->input('joke'),
        'fields' => $request->input('fields'),
        'extra' => $request->input('extra'),
        'prompt' => $request->input('prompt'),
        'final_prompt' => $request->input('final_prompt'),
    ]);

    return response()->json($chat, 201);
}

    public function destroy($id)
{
    $chat = ChatHistory::find($id);

    if (!$chat) {
        return response()->json(['error' => 'Histórico não encontrado'], 404);
    }

    $chat->delete();

    return response()->json(['message' => 'Histórico deletado com sucesso']);
}


public function update(Request $request, $id)
{
    try {
        $chat = ChatHistory::findOrFail($id);

        $chat->update([
            'title' => $request->input('title'),
            'joke' => $request->input('joke'),
            'fields' => $request->input('fields'),
            'extra' => $request->input('extra'),
            'prompt' => $request->input('prompt'),
            'final_prompt' => $request->input('final_prompt'),
        ]);

        return response()->json([
            'success' => true,
            'chat' => $chat,
        ]);
    } catch (\Exception $e) {
        Log::error('Erro ao atualizar chat:', ['exception' => $e]);
        return response()->json([
            'success' => false,
            'message' => 'Erro interno ao atualizar chat.'
        ], 500);
    }
}

}
