<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use Illuminate\Http\Request;

class ChatController
{
    public function index(){
    return Chat::orderBy('created_at', 'desc')->get();
   }

    public function store(Request $request)
    {
        $chat = Chat::create([
            'title' => $request->input('title'),
            'messages' => $request->input('messages'),
        ]);

        return response()->json(['id' => $chat->id]);
    }
}
