<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\ChatHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ChatHistoryController
{
 public function index()
    {
        return ChatHistory::orderBy('created_at', 'desc')->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'joke' => 'nullable|string',
            'fields' => 'nullable|array',
            'extra' => 'nullable|string',
            'prompt' => 'nullable|string',
            'final_prompt' => 'nullable|string',
        ]);

        $chat = ChatHistory::create([
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
