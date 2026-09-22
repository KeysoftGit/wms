<?php

namespace App\Services;

use App\Models\TransPurchaseInvoiceDT3;
use App\Models\TransPurchaseOrderHD;
use App\Models\TransQualityControlReceivingHD;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PurchaseOrderStateService
{
    public static function recalculate(string $poNumber, ?string $userId = null): void
    {
        $po = TransPurchaseOrderHD::with('details')
            ->where('TransactionNo', $poNumber)
            ->first();

        if (!$po) {
            return;
        }

        if ($po->ClosingReason !== null && trim((string) $po->ClosingReason) !== '') {
            $po->update(self::audit([
                'Closed' => 1,
                'Editable' => 0,
            ], $userId));
            return;
        }

        $outstanding = false;
        foreach ($po->details as $detail) {
            $orderedQty = (float) $detail->Qty * (float) $detail->Conversion;
            $lackTolerance = max((float) ($detail->LackTolerancePercentage ?? 0), 0);
            $minimumReceiveQty = max($orderedQty * (1 - ($lackTolerance / 100)), 0);

            if ((float) $detail->QtyReceived < ($minimumReceiveQty - 0.000001)) {
                $outstanding = true;
                break;
            }
        }

        $hasAsn = Schema::hasTable('Trans_AdvanceShippingNoticeHD')
            && DB::table('Trans_AdvanceShippingNoticeHD')
                ->where('PONumber', $poNumber)
                ->exists();
        $hasGr = TransQualityControlReceivingHD::where('PONumber', $poNumber)->exists();
        $hasPi = TransPurchaseInvoiceDT3::where('ReffNumber', $poNumber)->exists();
        $approvalLocked = Schema::hasColumn('Trans_PurchaseOrderHD', 'Status')
            && in_array(strtoupper(trim((string) ($po->Status ?? 'PENDING'))), ['APPROVED', 'REJECTED'], true);

        $po->update(self::audit([
            'Outstanding' => $outstanding ? 1 : 0,
            'Closed' => $outstanding ? 0 : 1,
            'Editable' => (!$approvalLocked && !$hasAsn && !$hasGr && !$hasPi) ? 1 : 0,
        ], $userId));
    }

    private static function audit(array $data, ?string $userId): array
    {
        if ($userId !== null) {
            $data['LastUpdateBy'] = $userId;
        }

        $data['LastUpdate'] = date('Y-m-d H:i:s');

        return $data;
    }
}
