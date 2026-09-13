<?php

namespace App\Http\Responders;

use Illuminate\Http\JsonResponse;

class GenerateMenuPdfResponder
{
    public function notYetAvailable(): JsonResponse
    {
        return response()->json(['message' => 'PDF機能は準備中です。']);
    }
}
