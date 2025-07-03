<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ItemController extends Controller
{
   public function import(Request $request) {
    $items = $request->input('items', []);

    foreach ($items as $data) {
        Item::updateOrCreate(
            ['external_id' => $data['id']],
            [
                'format' => $data['format'],
                'warranty_period' => $data['warranty_period'],
                'status' => $data['status'],
                'is_subscription' => $data['is_subscription'],
                'name' => $data['name'],
                'created_at_external' => \Carbon\Carbon::createFromTimestampMs($data['created_at']),
                'ucode' => $data['ucode'],
            ]
        );
    }

    return response()->json([
        'message' => 'Todos os itens foram importados com sucesso.',
        'total_imported' => count($items),
        'page_info' => $request->input('page_info')
    ]);
}
}
