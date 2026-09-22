<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\MsQR;
use Illuminate\Http\JsonResponse;

class QRController extends Controller
{
    public function getQR(string $code): JsonResponse
    {
        try {
            $qr = MsQR::where('code', strtoupper(trim($code)))->first();

            if (!$qr) {
                return ResponseFormatter::error('QR code not found', 404)->toResponse();
            }

            $result = [
                'code' => $qr->code,
                'show_content' => $qr->show_content,
                'value' => $qr->json_value['data'] ?? [],
                'display' => $qr->json_display['data'] ?? [],
            ];

            return ResponseFormatter::success($result, 'QR fetched successfully')->toResponse();
        } catch (\Exception $e) {
            return ResponseFormatter::error($e->getMessage())->toResponse();
        }
    }
}
