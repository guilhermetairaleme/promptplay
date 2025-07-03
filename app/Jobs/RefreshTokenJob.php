<?php

namespace App\Jobs;

use App\Models\Token;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RefreshTokenJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        $token = Token::latest()->first();
        $now = now();

        $clientId = env('HOTMART_CLIENT_ID');
        $clientSecret = env('HOTMART_CLIENT_SECRET');
        $basicAuth = base64_encode("{$clientId}:{$clientSecret}");

        // Se não existe token, gerar novo
        if (!$token) {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . $basicAuth,
            ])->post('https://api-sec-vlc.hotmart.com/security/oauth/token?grant_type=client_credentials');

            if ($response->successful()) {
                $data = $response->json();

                Token::create([
                    'access_token' => $data['access_token'],
                    'token_type' => $data['token_type'],
                    'expires_in' => $data['expires_in'],
                    'scope' => $data['scope'] ?? null,
                    'jti' => $data['jti'] ?? null,
                ]);

                Log::info('Novo token gerado com sucesso.');
            } else {
                Log::error('Erro ao gerar token: ' . $response->body());
            }

            return;
        }

        // Verificar se está expirando
        $expiration = $token->created_at->addSeconds($token->expires_in);

        if ($now->greaterThanOrEqualTo($expiration->subMinutes(5))) {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . $basicAuth,
            ])->post('https://api-sec-vlc.hotmart.com/security/oauth/token?grant_type=client_credentials');

            if ($response->successful()) {
                $data = $response->json();

                Token::create([
                    'access_token' => $data['access_token'],
                    'token_type' => $data['token_type'],
                    'expires_in' => $data['expires_in'],
                    'scope' => $data['scope'] ?? null,
                    'jti' => $data['jti'] ?? null,
                ]);

                Log::info('Token renovado com sucesso.');
            } else {
                Log::error('Erro ao renovar token: ' . $response->body());
            }
        }
    }


}
