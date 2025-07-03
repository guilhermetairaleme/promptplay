<?php

namespace App\Events;

use App\Models\Token;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class CheckAndRefreshToken
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct()
    {
        //
    }

     public function handle()
    {
        $token = Token::latest()->first();

        if (!$token) return;

        // Verifica o tempo de expiração baseado no created_at + expires_in
        $expiration = $token->created_at->addSeconds($token->expires_in);
        $now = now();

        if ($now->greaterThanOrEqualTo($expiration->subMinutes(5))) {
            // Token perto de expirar, renova aqui
            $response = Http::asForm()->post('URL_DO_REFRESH_TOKEN', [
                'grant_type' => 'refresh_token',
                'refresh_token' => 'SEU_REFRESH_TOKEN_AQUI',
                'client_id' => 'SEU_CLIENT_ID',
                'client_secret' => 'SEU_CLIENT_SECRET',
            ]);

            if ($response->successful()) {
                $data = $response->json();

                Token::create([
                    'access_token' => $data['access_token'],
                    'token_type' => $data['token_type'],
                    'expires_in' => $data['expires_in'],
                    'scope' => $data['scope'] ?? null,
                    'jti' => $data['jti'] ?? null,
                ]);
            }
        }
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
