<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\HotmartOrder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\NewUserWelcomeMail;
use App\Models\Item;

class HotmartWebhookController extends Controller
{
    public function handle(Request $request) {
    Log::info('Webhook Hotmart recebido', $request->all());

    $payload = $request->all();

    // Evita duplicatas
    $transactionId = $payload['data']['purchase']['transaction'] ?? null;

    if (!$transactionId || HotmartOrder::where('transaction_id', $transactionId)->exists()) {
        return response()->json(['message' => 'Duplicado ou inválido.'], 200);
    }

    $buyer = $payload['data']['buyer'] ?? [];
    $product = $payload['data']['product'] ?? [];
    $purchase = $payload['data']['purchase'] ?? [];
    $commissions = $payload['data']['commissions'] ?? [];

    $totalCommission = collect($commissions)->sum('value');

    // Criar usuário se não existir
    $buyerEmail = $buyer['email'] ?? null;
    $buyerName = $buyer['name'] ?? 'Cliente';


    if ($buyerEmail) {
        $user = User::where('email', $buyerEmail)->first();

        if (!$user) {
            $randomPassword = Str::random(10);

            $user = User::create([
                'name' => $buyerName,
                'email' => $buyerEmail,
                'password' => Hash::make($randomPassword),
            ]);

          try {
                $item = Item::where('external_id',$product['id']);
                if($item){
                    $picture = $item->path;
                }
                Log::alert("enviando... $picture");
                Mail::to($buyerEmail)->send(new NewUserWelcomeMail($user, $randomPassword,$picture));
                Log::info("E-mail enviado para {$buyerEmail}");
            } catch (\Exception $e) {
                Log::error("Falha ao enviar e-mail para {$buyerEmail}: " . $e->getMessage());
            }
        }
    }

    // Salvar pedido
    HotmartOrder::create([
        'event' => $payload['event'],
        'transaction_id' => $transactionId,
        'buyer_email' => $buyerEmail,
        'buyer_name' => $buyerName,
        'product_name' => $product['name'] ?? null,
        'status' => $purchase['status'] ?? null,
        'amount' => $purchase['price']['value'] ?? null,
        'currency' => $purchase['price']['currency_value'] ?? null,
        'payment_type' => $purchase['payment']['type'] ?? null,
        'commission_total' => $totalCommission,
        'data_json' => $payload['data'],
    ]);

    return response()->json(['message' => 'Pedido registrado com sucesso.']);
}
}
