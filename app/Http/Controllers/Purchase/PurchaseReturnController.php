<?php

namespace App\Http\Controllers\Purchase;

use App\Helpers\BukuStockHelper;
use App\Services\WarehouseAccessCriteria;
use App\Http\Controllers\Controller;
use App\Models\BukuHutang;
use App\Models\BukuStock;
use App\Models\DocPrint;
use App\Models\MsAutoNumber;
use App\Models\MsPartUnit;
use App\Models\MsRevAlias;
use App\Models\TransDirectPurchaseHD;
use App\Models\TransDirectVendorPaymentDT;
use App\Models\TransGoodsReceivingHD;
use App\Models\TransJournalDT;
use App\Models\TransJournalHD;
use App\Models\TransPurchaseInvoiceDT;
use App\Models\TransPurchaseOrderDT;
use App\Models\TransPurchaseOrderHD;
use App\Models\TransPurchaseReturnDT;
use App\Models\TransPurchaseReturnHD;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class PurchaseReturnController extends Controller
{
    public function getRev(){
        $revs = MsRevAlias::where('TransactionType', 'PURCHASING')->first();

        $revData = [
            [
                'name' => $revs->ItemRevDT01,
                'type' => $revs->ItemRevDT01Type
            ],
            [
                'name' => $revs->ItemRevDT02,
                'type' => $revs->ItemRevDT02Type
            ],
            [
                'name' => $revs->ItemRevDT03,
                'type' => $revs->ItemRevDT03Type
            ],
            [
                'name' => $revs->ItemRevDT04,
                'type' => $revs->ItemRevDT04Type
            ],
            [
                'name' => $revs->ItemRevDT05,
                'type' => $revs->ItemRevDT05Type
            ],
            [
                'name' => $revs->ItemRevDT06,
                'type' => $revs->ItemRevDT06Type
            ],[
                'name' => $revs->ItemRevDT07,
                'type' => $revs->ItemRevDT07Type
            ],
            [
                'name' => $revs->ItemRevDT08,
                'type' => $revs->ItemRevDT08Type
            ],
            [
                'name' => $revs->ItemRevDT09,
                'type' => $revs->ItemRevDT09Type
            ],
            [
                'name' => $revs->ItemRevDT10,
                'type' => $revs->ItemRevDT10Type
            ],
            [
                'name' => $revs->ItemRevDT11,
                'type' => $revs->ItemRevDT11Type
            ],
            [
                'name' => $revs->ItemRevDT12,
                'type' => $revs->ItemRevDT12Type
            ],
            [
                'name' => $revs->ItemRevDT13,
                'type' => $revs->ItemRevDT13Type
            ],
            [
                'name' => $revs->ItemRevDT14,
                'type' => $revs->ItemRevDT14Type
            ],
            [
                'name' => $revs->ItemRevDT15,
                'type' => $revs->ItemRevDT15Type
            ],
        ];

        return $revData;
    }
    public function index(){
        return view('purchase.pr.index');
    }

    public function datatable(Request $request){
        $data = TransPurchaseReturnHD::select('id', 'TransactionNo', 'TransactionDate', 'BasedOnGoodsReceiving', 'ReceivingNumber',
            'BasedOnDirectPurchase', 'DivisionID', 'SupplierID', 'GrandTotal', 'Editable')
            ->with(['division', 'supplier']);
        WarehouseAccessCriteria::applyDetails($data);

        if($request->get('date')){
            $dates = explode(' to ', $request->get('date'));
            if(count($dates) > 1){
                $data->whereDate('TransactionDate', '>=', \DateTime::createFromFormat('d/m/Y', $dates[0])->format('Y-m-d'));
                $data->whereDate('TransactionDate', '<=', \DateTime::createFromFormat('d/m/Y', $dates[1])->format('Y-m-d'));
            }
            else {
                $data->whereDate('TransactionDate', \DateTime::createFromFormat('d/m/Y', $dates[0])->format('Y-m-d'));
            }
        }

        return DataTables::of($data)
            ->editColumn('DivisionID', function ($row){
                return optional($row->division)->DivisionName ?? $row->DivisionID;
            })
            ->editColumn('SupplierID', function ($row){
                return optional($row->supplier)->SupplierName ?? $row->SupplierID;
            })
            ->addColumn('action', function ($row){
                $btn = '<div class="btn-group">';

                $btn .= '<a class="btn btn-sm btn-alt-secondary js-bs-tooltip-enabled" data-bs-toggle="tooltip" title="Show" href="'. route('pr.show', $row->id) .'"><i class="fa fa-fw fa-eye"></i></a>';
                if($row->Editable == 1){
                    if(Auth::user()->hasAnyPermission(['admin', 'pr.edit'])){
                        $btn .= '<a class="btn btn-sm btn-alt-secondary js-bs-tooltip-enabled" data-bs-toggle="tooltip" title="Edit" href="'. route('pr.edit', $row->id) .'"><i class="fa fa-fw fa-edit"></i></a>';
                    }
                    if(Auth::user()->hasAnyPermission(['admin', 'pr.delete'])){
                        $btn .= '<button class="btn btn-sm btn-alt-secondary js-bs-tooltip-enabled delete-btn" data-bs-toggle="tooltip" title="Delete" data-url="'. route('pr.delete', $row->id) .'"><i class="fa fa-fw fa-trash"></i></button>';
                    }
                }

                return $btn;
            })
            ->make(true);
    }

    public function add(){
        $revData = $this->getRev();

        return view('purchase.pr.add', compact('revData'));
    }

    public function getGR(Request $request){
        try {
            $transactions = TransGoodsReceivingHD::where('Outstanding', 1);

            if($request->get('search')){
                $transactions->where('TransactionNo', 'like', '%' . $request->query('search') . '%');
            }

            $data = [];
            foreach ($transactions->get() as $transaction){
                $data[] = [
                    'id' => $transaction->TransactionNo,
                    'text' => $transaction->TransactionNo,
                ];
            }

            return response($data);
        } catch (\Exception $e) {
            Log::error($e);
            return response([
                'status' => 'error',
            ]);
        }
    }

    public function getGRDetail(Request $request){
        $gr = TransGoodsReceivingHD::where('TransactionNo', $request->get('inv'))->first();
        $poNumber = $gr->qc->PONumber;

        $data = [];
        $revCount = $gr->RevCount;
        $po = TransPurchaseOrderHD::where('TransactionNo', $poNumber)->first();
        foreach ($gr->details as $detail) {
            $poDetail = TransPurchaseOrderDT::where('TransactionNo', $poNumber)
                ->where('PartID', $detail->PartID)
                ->where('Sequence', $detail->Sequence)
                ->first();

            $returned = TransPurchaseReturnDT::where('PartID', $detail->PartID)
                ->where('Sequence', $detail->Sequence)
                ->whereHas('parent', function ($query) use ($request) {
                    return $query->where('ReceivingNumber', $request->get('inv'));
                })
                ->sum('Qty');
            $qtyOri = $detail->Qty - $returned;
            $qty = 0;

            $rev = [];
            for ($i = 0; $i < $gr->RevCount; $i++){
                switch ($i) {
                    case 0:
                        $rev[] = $detail->ItemRevDT01 ?? '';
                        break;
                    case 1:
                        $rev[] = $detail->ItemRevDT02 ?? '';
                        break;
                    case 2:
                        $rev[] = $detail->ItemRevDT03 ?? '';
                        break;
                    case 3:
                        $rev[] = $detail->ItemRevDT04 ?? '';
                        break;
                    case 4:
                        $rev[] = $detail->ItemRevDT05 ?? '';
                        break;
                    case 5:
                        $rev[] = $detail->ItemRevDT06 ?? '';
                        break;
                    case 6:
                        $rev[] = $detail->ItemRevDT07 ?? '';
                        break;
                    case 7:
                        $rev[] = $detail->ItemRevDT08 ?? '';
                        break;
                    case 8:
                        $rev[] = $detail->ItemRevDT09 ?? '';
                        break;
                    case 9:
                        $rev[] = $detail->ItemRevDT10 ?? '';
                        break;
                    case 10:
                        $rev[] = $detail->ItemRevDT11 ?? '';
                        break;
                    case 11:
                        $rev[] = $detail->ItemRevDT12 ?? '';
                        break;
                    case 12:
                        $rev[] = $detail->ItemRevDT13 ?? '';
                        break;
                    case 13:
                        $rev[] = $detail->ItemRevDT14 ?? '';
                        break;
                    case 14:
                        $rev[] = $detail->ItemRevDT15 ?? '';
                        break;
                }
            }

            if($request->get('edit')){
                $pr = TransPurchaseReturnHD::where('TransactionNo', $request->get('edit'))->first();
                $revCount = $pr->RevCount;
                $prDetail = TransPurchaseReturnDT::where('TransactionNo', $request->get('edit'))
                    ->where('PartID', $detail->PartID)
                    ->where('Sequence', $detail->Sequence)
                    ->first();

                if($prDetail){
                    $qty = $prDetail->Qty;
                    $qtyOri += $qty;

                    $revCount = $pr->RevCount;
                    $rev = [];
                    for ($i = 0; $i < $pr->RevCount; $i++){
                        switch ($i) {
                            case 0:
                                $rev[] = $prDetail->ItemRevDT01 ?? '';
                                break;
                            case 1:
                                $rev[] = $prDetail->ItemRevDT02 ?? '';
                                break;
                            case 2:
                                $rev[] = $prDetail->ItemRevDT03 ?? '';
                                break;
                            case 3:
                                $rev[] = $prDetail->ItemRevDT04 ?? '';
                                break;
                            case 4:
                                $rev[] = $prDetail->ItemRevDT05 ?? '';
                                break;
                            case 5:
                                $rev[] = $prDetail->ItemRevDT06 ?? '';
                                break;
                            case 6:
                                $rev[] = $prDetail->ItemRevDT07 ?? '';
                                break;
                            case 7:
                                $rev[] = $prDetail->ItemRevDT08 ?? '';
                                break;
                            case 8:
                                $rev[] = $prDetail->ItemRevDT09 ?? '';
                                break;
                            case 9:
                                $rev[] = $prDetail->ItemRevDT10 ?? '';
                                break;
                            case 10:
                                $rev[] = $prDetail->ItemRevDT11 ?? '';
                                break;
                            case 11:
                                $rev[] = $prDetail->ItemRevDT12 ?? '';
                                break;
                            case 12:
                                $rev[] = $prDetail->ItemRevDT13 ?? '';
                                break;
                            case 13:
                                $rev[] = $prDetail->ItemRevDT14 ?? '';
                                break;
                            case 14:
                                $rev[] = $prDetail->ItemRevDT15 ?? '';
                                break;
                        }
                    }
                }
            }

            $price = ($poDetail->UnitPrice - $poDetail->Discount) / $poDetail->Conversion;
            if($poDetail->parent->VAT == 'I'){
                $vat = $poDetail->part->VAT2;
                $temp = $price / (1 + ($vat/100));
                $tax = $temp * $vat/100;
                $price = $price - $tax;
            }

//            $discount = 0;
//
//            if ($poDetail->PercentageDisc1 != null && $poDetail->PercentageDisc1 != 0) {
//                $temp = $price * $poDetail->PercentageDisc1 / 100;
//                $discount += $temp;
//                $price = $price - $temp;
//            }
//
//            if ($poDetail->Discount1 != null && $poDetail->Discount1 != 0) {
//                $discount += $poDetail->Discount1;
//                $price = $price - $poDetail->Discount1;
//            }
//
//            if ($poDetail->PercentageDisc2 != null && $poDetail->PercentageDisc2 != 0) {
//                $temp = $price * $poDetail->PercentageDisc2 / 100;
//                $discount += $temp;
//                $price = $price - $temp;
//            }
//
//            if ($poDetail->Discount2 != null && $poDetail->Discount2 != 0) {
//                $discount += $poDetail->Discount2;
//                $price = $price - $poDetail->Discount2;
//            }
//
//            $price = $price / $poDetail->Conversion;
//
//            if ($poDetail->parent->VAT == 'I') {
//                $vat = $poDetail->part->VAT2;
//                $temp = $price / (1 + ($vat / 100));
//                $tax = $temp * $vat / 100;
//                $price = $price - $tax;
//            }

            $subtotal = $price * $detail->Qty;

            if(!$request->get('show') || $qty > 0){
                $data[] = [
                    'part_id' => $detail->PartID,
                    'part' => $detail->PartID . ($detail->part->PartName ? ' - ' . $detail->part->PartName : ''),
                    'vat' => $detail->part->VAT2,
                    'sequence' => $detail->Sequence,
                    'conversion' => $poDetail->Conversion,
                    'qty_ori' => $qtyOri,
                    'qty' => $qty,
                    'unit_id' => $detail->UnitID,
                    'unit' => $detail->UnitID . ($detail->unit->UnitName ? ' - ' . $detail->unit->UnitName : ''),
                    'price' => $price,
                    'discount1' => $poDetail->Discount1 ?? '',
                    'discount1p' => $poDetail->PercentageDisc1 ?? '',
                    'discount2' => $poDetail->Discount2 ?? '',
                    'discount2p' => $poDetail->PercentageDisc2 ?? '',
                    'totalDiscount' => $poDetail->Discount,
                    'totalPrice' => $subtotal,
                    'currency' => $gr->CurrencyID,
                    'division_id' => $poDetail->DivisionID,
                    'division' => $poDetail->DivisionID . ($poDetail->division->DivisionName ? ' - ' . $poDetail->division->DivisionName : ''),
                    'warehouse_id' => $gr->WarehouseID,
                    'warehouse' => $gr->WarehouseID . ($gr->warehouse->WarehouseName ? ' - ' . $gr->warehouse->WarehouseName : ''),
                    'rev' => $rev
                ];
            }

        }

        return response([
            'data' => $data,
            'revCount' => $revCount,
            'division' => $po->DivisionID,
            'division_name' => $po->DivisionID . ($po->division->DivisionName ? ' - ' . $po->division->DivisionName : ''),
            'supplier' => $po->SupplierID,
            'supplier_name' => $po->SupplierID . ($po->supplier->SupplierName != null ? ' - ' . $po->supplier->SupplierName : ''),
            'currency' => $po->CurrencyID,
            'currency_name' => $po->CurrencyID . ($po->currency->CurrencyName != null ? ' - ' . $po->currency->CurrencyName : ''),
            'rate' => $po->Rate,
            'vat' => $po->VAT,
            'success' => true,
        ]);
    }

    public function getDP(Request $request){
        try {
            $transactions = TransDirectPurchaseHD::query();

            if($request->get('search')){
                $transactions->where('TransactionNo', 'like', '%' . $request->query('search') . '%');
            }

            $data = [];
            foreach ($transactions->get() as $transaction){
                $data[] = [
                    'id' => $transaction->TransactionNo,
                    'text' => $transaction->TransactionNo,
                ];
            }

            return response($data);
        } catch (\Exception $e) {
            Log::error($e);
            return response([
                'status' => 'error',
            ]);
        }
    }

    public function getDPDetail(Request $request){
        $dp = TransDirectPurchaseHD::where('TransactionNo', $request->get('inv'))->first();

        $data = [];
        $revCount = $dp->RevCount;
        foreach ($dp->details as $detail) {
            $returned = TransPurchaseReturnDT::where('PartID', $detail->PartID)
                ->where('Sequence', $detail->Sequence)
                ->whereHas('parent', function ($query) use ($request) {
                    return $query->where('ReceivingNumber', $request->get('inv'));
                })
                ->sum('Qty');
            $qtyOri = $detail->Qty - $returned;

            $qty = 0;
            $rev = [];
            for ($i = 0; $i < $dp->RevCount; $i++){
                switch ($i) {
                    case 0:
                        $rev[] = $detail->ItemRevDT01 ?? '';
                        break;
                    case 1:
                        $rev[] = $detail->ItemRevDT02 ?? '';
                        break;
                    case 2:
                        $rev[] = $detail->ItemRevDT03 ?? '';
                        break;
                    case 3:
                        $rev[] = $detail->ItemRevDT04 ?? '';
                        break;
                    case 4:
                        $rev[] = $detail->ItemRevDT05 ?? '';
                        break;
                    case 5:
                        $rev[] = $detail->ItemRevDT06 ?? '';
                        break;
                    case 6:
                        $rev[] = $detail->ItemRevDT07 ?? '';
                        break;
                    case 7:
                        $rev[] = $detail->ItemRevDT08 ?? '';
                        break;
                    case 8:
                        $rev[] = $detail->ItemRevDT09 ?? '';
                        break;
                    case 9:
                        $rev[] = $detail->ItemRevDT10 ?? '';
                        break;
                    case 10:
                        $rev[] = $detail->ItemRevDT11 ?? '';
                        break;
                    case 11:
                        $rev[] = $detail->ItemRevDT12 ?? '';
                        break;
                    case 12:
                        $rev[] = $detail->ItemRevDT13 ?? '';
                        break;
                    case 13:
                        $rev[] = $detail->ItemRevDT14 ?? '';
                        break;
                    case 14:
                        $rev[] = $detail->ItemRevDT15 ?? '';
                        break;
                }
            }

            if($request->get('edit')){
                $pr = TransPurchaseReturnHD::where('TransactionNo', $request->get('edit'))->first();
                $revCount = $pr->RevCount;

                $prDetail = TransPurchaseReturnDT::where('TransactionNo', $request->get('edit'))
                    ->where('PartID', $detail->PartID)
                    ->where('Sequence', $detail->Sequence)
                    ->first();

                if($prDetail){
                    $qty = $prDetail->Qty;
                    $qtyOri += $qty;

                    $rev = [];
                    for ($i = 0; $i < $pr->RevCount; $i++){
                        switch ($i) {
                            case 0:
                                $rev[] = $prDetail->ItemRevDT01 ?? '';
                                break;
                            case 1:
                                $rev[] = $prDetail->ItemRevDT02 ?? '';
                                break;
                            case 2:
                                $rev[] = $prDetail->ItemRevDT03 ?? '';
                                break;
                            case 3:
                                $rev[] = $prDetail->ItemRevDT04 ?? '';
                                break;
                            case 4:
                                $rev[] = $prDetail->ItemRevDT05 ?? '';
                                break;
                            case 5:
                                $rev[] = $prDetail->ItemRevDT06 ?? '';
                                break;
                            case 6:
                                $rev[] = $prDetail->ItemRevDT07 ?? '';
                                break;
                            case 7:
                                $rev[] = $prDetail->ItemRevDT08 ?? '';
                                break;
                            case 8:
                                $rev[] = $prDetail->ItemRevDT09 ?? '';
                                break;
                            case 9:
                                $rev[] = $prDetail->ItemRevDT10 ?? '';
                                break;
                            case 10:
                                $rev[] = $prDetail->ItemRevDT11 ?? '';
                                break;
                            case 11:
                                $rev[] = $prDetail->ItemRevDT12 ?? '';
                                break;
                            case 12:
                                $rev[] = $prDetail->ItemRevDT13 ?? '';
                                break;
                            case 13:
                                $rev[] = $prDetail->ItemRevDT14 ?? '';
                                break;
                            case 14:
                                $rev[] = $prDetail->ItemRevDT15 ?? '';
                                break;
                        }
                    }
                }
            }

            $price = $detail->UnitPrice;
            $discount = 0;

            if ($detail->PercentageDisc1 != null && $detail->PercentageDisc1 != 0) {
                $temp = $price * $detail->PercentageDisc1 / 100;
                $discount += $temp;
                $price = $price - $temp;
            }

            if ($detail->Discount1 != null && $detail->Discount1 != 0) {
                $discount += $detail->Discount1;
                $price = $price - $detail->Discount1;
            }

            if ($detail->PercentageDisc2 != null && $detail->PercentageDisc2 != 0) {
                $temp = $price * $detail->PercentageDisc2 / 100;
                $discount += $temp;
                $price = $price - $temp;
            }

            if ($detail->Discount2 != null && $detail->Discount2 != 0) {
                $discount += $detail->Discount2;
                $price = $price - $detail->Discount2;
            }

            if ($detail->parent->VAT == 'I') {
                $vat = $detail->part->VAT2;
                $temp = $price / (1 + ($vat / 100));
                $tax = $temp * $vat / 100;
                $price = $price - $tax;
            }

            $subtotal = $price * $detail->Qty;

            if(!$request->get('show') || $qty > 0){
                $data[] = [
                    'part_id' => $detail->PartID,
                    'part' => $detail->PartID . ($detail->part->PartName ? ' - ' . $detail->part->PartName : ''),
                    'vat' => $detail->part->VAT2,
                    'sequence' => $detail->Sequence,
                    'conversion' => $detail->Conversion,
                    'qty_ori' => $qtyOri,
                    'qty' => $qty,
                    'unit_id' => $detail->UnitID,
                    'unit' => $detail->UnitID . ($detail->unit->UnitName ? ' - ' . $detail->unit->UnitName : ''),
                    'price' => $price,
                    'discount1' => $detail->Discount1 ?? '',
                    'discount1p' => $detail->PercentageDisc1 ?? '',
                    'discount2' => $detail->Discount2 ?? '',
                    'discount2p' => $detail->PercentageDisc2 ?? '',
                    'totalDiscount' => $discount,
                    'totalPrice' => $subtotal,
                    'currency' => $dp->CurrencyID,
                    'division_id' => $detail->DivisionID,
                    'division' => $detail->DivisionID . ($detail->division->DivisionName ? ' - ' . $detail->division->DivisionName : ''),
                    'warehouse_id' => $detail->WarehouseID,
                    'warehouse' => $detail->WarehouseID . ($detail->warehouse->WarehouseName ? ' - ' . $detail->warehouse->WarehouseName : ''),
                    'rev' => $rev
                ];
            }
        }

        return response([
            'data' => $data,
            'revCount' => $revCount,
            'division' => $dp->DivisionID,
            'division_name' => $dp->DivisionID . ($dp->division->DivisionName ? ' - ' . $dp->division->DivisionName : ''),
            'supplier' => $dp->SupplierID,
            'supplier_name' => $dp->SupplierID . ($dp->supplier->SupplierName != null ? ' - ' . $dp->supplier->SupplierName : ''),
            'currency' => $dp->CurrencyID,
            'currency_name' => $dp->CurrencyID . ($dp->currency->CurrencyName != null ? ' - ' . $dp->currency->CurrencyName : ''),
            'rate' => $dp->Rate,
            'vat' => $dp->VAT,
            'success' => true,
        ]);
    }




    public function store(Request $request){
        $this->validate($request, [
            'TransactionNo' => 'required_without:automatic|string|max:50|unique:Trans_PurchaseReturnHD,TransactionNo',
            'TransactionDate' => 'required',
            'SupplierID' => 'required',
            'DivisionID' => 'required',
            'CurrencyID' => 'required',
            'Rate' => 'required',
            'Notes' => 'nullable|string',
            "part" => "required|array|min:1"
        ], [
            'TransactionNo.unique' => 'Transaction No has already been taken!',
            'TransactionNo.max' => 'Transaction No maximum characters is 50!',
            'part.min' => 'Need at least one detail to make an return!'
        ]);

        try {
            DB::beginTransaction();
            if($request->input('Type') != 'N'){
                if($request->input('Type') == 'GR'){
                    $invoice = TransGoodsReceivingHD::where('TransactionNo', $request->input('ReceivingNumber'))->first();
                }
                else if($request->input('Type') == 'DP'){
                    $invoice = TransDirectPurchaseHD::where('TransactionNo', $request->input('ReceivingNumber'))->first();
                }
                $date = Carbon::createFromFormat('d/m/Y', $request->input('TransactionDate'));
                $invDate = Carbon::parse($invoice->TransactionDate);
                if($date->lessThan($invDate)){
                    return redirect()->back()->withInput()
                        ->withErrors([
                            "Transaction Date must not be earlier than Reference's date!"
                        ]);
                }
            }

            $qty = $request->input('qty');
            $qtyOri = $request->input('qty_ori');
            $sequence = $request->input('sequence');
            $warehouse = $request->input('warehouse');
            $conversion = $request->input('conversion');
            $conversion = $request->input('conversion');

            // VALIDATE INPUT
            $totalQty = 0;
            foreach ($qty as $q){
                $totalQty += $q;
            }

            if($totalQty == 0){
                return redirect()->back()->withInput()
                    ->withErrors([
                        'You need to return at least one item!'
                    ]);
            }

            $errors = [];
            foreach ($request->input('part') as $i => $part){
                $qtyBase = $request->input('Type') != 'GR' ? ($qty[$i] * $conversion[$i]) : $qty[$i];
                $availableStock = BukuStockHelper::calculateCurrentStock($part, $warehouse[$i]);

                if($request->input('Type') == 'GR'){
                    if($qty[$i] > $qtyOri[$i]){
                        $errors[] = "Can't return " . $part . " because the quantity exceeds the original Goods Receiving amount!";
                    }
                }
                else if($request->input('Type') == 'DP'){
                    if($qty[$i] > $qtyOri[$i]){
                        $errors[] = "Can't return " . $part . " because the quantity exceeds the original Direct Purchase amount!";
                    }
                }
//                if($request->input('Type') == 'GR'){
//                    $grDetail = TransGoodsReceivingDT::where('TransactionNo', $request->input('ReceivingNumber'))
//                        ->where('PartID', $part)
//                        ->where('Sequence', $sequence[$i])
//                        ->first();
//
//                    if($qty[$i] > $grDetail->Qty){
//                        $errors[] = "Can't return " . $part . " because the quantity exceeds the original Goods Receiving amount!";
//                    }
//                }
//                else if($request->input('Type') == 'DP'){
//                    $dpDetail = TransDirectPurchaseDT::where('TransactionNo', $request->input('ReceivingNumber'))
//                        ->where('PartID', $part)
//                        ->where('Sequence', $sequence[$i])
//                        ->first();
//
//                    if($qty[$i] > $dpDetail->Qty){
//                        $errors[] = "Can't return " . $part . " because the quantity exceeds the original Direct Purchase amount!";
//                    }
//                }

                if($qtyBase > $availableStock){
                    $errors[] = "Can't return " . $part . " because the quantity exceeds the current buku stock amount!";
                }
            }

            if(count($errors) > 0){
                return redirect()->back()->withInput()
                    ->withErrors($errors);
            }



            $masterAuto = MsAutoNumber::find("1");

            $digit = null;

            if($request->input('automatic')){
                $checkLast = TransPurchaseReturnHD::where('IsAuto', 1)
                    ->whereMonth('TransactionDate', \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('m'))
                    ->whereYear('TransactionDate', \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('Y'))
                    ->orderBy('LastDigit', 'desc')
                    ->first();

                if(!$checkLast){
                    $digit = 1;
                }
                else {
                    $digit = $checkLast->LastDigit + 1;
                }

                $id = $masterAuto->Purchase12 . '/' . \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('Y')
                    . '/' . \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('m')
                    . '/' . str_pad( $digit, 4, "0", STR_PAD_LEFT );

                $checkExist = TransPurchaseReturnHD::where('TransactionNo', $id)->first();
                if($checkExist){
                    return redirect()->back()->withInput()->withErrors([
                        "Transaction No has already been taken!"
                    ]);
                }
            }
            else {
                $id = trim($request->input('TransactionNo'));
            }

            TransPurchaseReturnHD::create([
                'TransactionNo' => $id,
                'TransactionDate' => \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('Y-m-d'),
                'SupplierID' => $request->input('SupplierID'),
                'DivisionID' => $request->input('DivisionID'),
                'CurrencyID' => $request->input('CurrencyID'),
                'BasedOnGoodsReceiving' => $request->input('Type') == 'GR' ? 1 : 0,
                'BasedOnDirectPurchase' => $request->input('Type') == 'DP' ? 1 : 0,
                'BasedOnQCReceiving' => 0,
                'ReceivingNumber' => $request->input('ReceivingNumber') ?? '',
                'Rate' => $request->input('Rate') ?? 1,
                'VAT' => $request->input('VAT'),
                'SubTotal' => $request->input('GrandTotal') ?? 0,
                'GrandTotal' => $request->input('GrandTotal') ?? 0,
                'Notes' => $request->input('Notes') ? trim($request->input('Notes')) : null,
                'Editable' => 1,
                'RevCount' => $request->input('rev') ?? 0,
                'CreatedBy' => Auth::user()->UserID,
                'EntryTime' => date('Y-m-d H:i:s'),
                'LastUpdateBy' => Auth::user()->UserID,
                'LastUpdate' => date('Y-m-d H:i:s'),
                'IsAuto' => $request->input('automatic') ?? 0,
                'LastDigit' => $digit,
            ]);

            $details = [];
            $BSDetails = [];


            $unit = $request->input('unit');
            $price = $request->input('price');
            $disc = $request->input('discount');
            $disc1 = $request->input('discount1');
            $disc1p = $request->input('discount1p');
            $disc2 = $request->input('discount2');
            $disc2p = $request->input('discount2p');
            $division = $request->input('division');

            foreach ($request->input('part') as $i => $part){
                if($qty[$i] > 0){
                    $newDetail = [
                        'TransactionNo' => $id,
                        'PartID' => $part,
                        'Qty' => $qty[$i],
                        'Sequence' => $sequence[$i],
                        'UnitID' => $unit[$i],
                        'Conversion' => $conversion[$i],
                        'UnitPrice' => $price[$i],
                        'PercentageDisc1' => $disc1p[$i],
                        'Discount1' => $disc1[$i],
                        'PercentageDisc2' => $disc2p[$i],
                        'Discount2' => $disc2[$i],
                        'Discount' => $disc[$i],
                        'DivisionID' => $division[$i],
                        'WarehouseID' => $warehouse[$i],
                    ];

                    for ($j = 1; $j <= $request->input('rev'); $j++){
                        if($j < 10){
                            $newDetail['ItemRevDT0' . $j] = $request->input('rev'.$j)[$i];
                        }
                        else {
                            $newDetail['ItemRevDT' . $j] = $request->input('rev'.$j)[$i];
                        }
                    }

                    $details[] = $newDetail;

                    // INSERT BUKU STOK
                    $qtyBase = $request->input('Type') != 'GR' ? ($qty[$i] * $conversion[$i]) : $qty[$i];
                    $unit2 = MsPartUnit::where('PartID', $part)
                        ->where('UnitID2', $unit[$i])
                        ->first();
                    $BSDetails[] = [
                        'TransactionNo' => $id,
                        'TransactionDate' => \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('Y-m-d'),
                        'PartID' => $part,
                        'WarehouseID' => $warehouse[$i],
                        'Sequence' => $i,
                        'UnitID' => $request->input('Type') != 'GR' ? $unit2->UnitID1 : $unit[$i],
                        'Qty' => floatval($qtyBase * -1),
                        'TransactionType' => 'PURCHASE_RETURN',
                        'CreatedBy' => Auth::user()->UserID,
                        'EntryTime' => date('Y-m-d H:i:s'),
                    ];
                }
            }

            TransPurchaseReturnDT::insert($details);
            BukuStock::insert($BSDetails);


            // INSERT BUKU HUTANG
            if($request->input('Type') != 'GR'){
                BukuHutang::create([
                    'TransactionNo' => $id,
                    'BalanceNo' => $request->input('ReceivingNumber') ?? $id,
                    'TransactionDate' => \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('Y-m-d'),
                    'DueDate' => \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('Y-m-d'),
                    'SupplierID' => $request->input('SupplierID'),
                    'CurrencyID' => $request->input('CurrencyID'),
                    'Rate' => $request->input('Rate') ?? 1,
                    'Amount' => $request->input('GrandTotal') ? (double)($request->input('GrandTotal') * -1) : 0,
                    'isVoucher' => 0,
                    'TransactionType' => 'PURCHASE_RETURN',
                    'CreatedBy' => Auth::user()->UserID,
                    'EntryTime' => date('Y-m-d H:i:s'),
                ]);
            }



            // INSERT JOURNAL
            \App\Services\PurchaseReturnJournalService::rebuild($id);


            // CHECK OUTSTANDING
            if($request->input('Type') == 'GR'){
                $this->checkOutstandingGR($request->input('ReceivingNumber'));
            }
            else if($request->input('Type') == 'DP'){
                $this->checkOutstandingDP($request->input('ReceivingNumber'));
            }
            DB::commit();
            return redirect()->route('pr')
                ->with([
                    'type' => 'success',
                    'icon' => 'fa fa-fw fa-circle-check',
                    'message' => 'Purchase Return successfully added!'
                ]);
        } catch (\Exception $e){
            Log::error($e);
            DB::rollBack();
            return redirect()->back()->withInput()->withErrors([
                $e->getMessage() ?: 'Something went wrong!'
            ]);
        }
    }

    public function show($id){
        $pr = TransPurchaseReturnHD::where('id', $id)->first();
        $details = [];

        $type = 'N';
        if($pr->BasedOnGoodsReceiving == 1){
            $type = 'GR';
        }
        else if($pr->BasedOnDirectPurchase == 1){
            $type = 'DP';
        }

        if($pr->BasedOnGoodsReceiving == 0 && $pr->BasedOnDirectPurchase == 0){
            foreach ($pr->details as $detail){
                $rev = [];
                for ($i = 0; $i < $pr->RevCount; $i++){
                    switch ($i) {
                        case 0:
                            $rev[] = $detail->ItemRevDT01 ?? '';
                            break;
                        case 1:
                            $rev[] = $detail->ItemRevDT02 ?? '';
                            break;
                        case 2:
                            $rev[] = $detail->ItemRevDT03 ?? '';
                            break;
                        case 3:
                            $rev[] = $detail->ItemRevDT04 ?? '';
                            break;
                        case 4:
                            $rev[] = $detail->ItemRevDT05 ?? '';
                            break;
                        case 5:
                            $rev[] = $detail->ItemRevDT06 ?? '';
                            break;
                        case 6:
                            $rev[] = $detail->ItemRevDT07 ?? '';
                            break;
                        case 7:
                            $rev[] = $detail->ItemRevDT08 ?? '';
                            break;
                        case 8:
                            $rev[] = $detail->ItemRevDT09 ?? '';
                            break;
                        case 9:
                            $rev[] = $detail->ItemRevDT10 ?? '';
                            break;
                        case 10:
                            $rev[] = $detail->ItemRevDT11 ?? '';
                            break;
                        case 11:
                            $rev[] = $detail->ItemRevDT12 ?? '';
                            break;
                        case 12:
                            $rev[] = $detail->ItemRevDT13 ?? '';
                            break;
                        case 13:
                            $rev[] = $detail->ItemRevDT14 ?? '';
                            break;
                        case 14:
                            $rev[] = $detail->ItemRevDT15 ?? '';
                            break;
                    }
                }

                $details[] = [
                    'part' =>  $detail->PartID . ($detail->part->PartName ? ' - ' . $detail->part->PartName : ''),
                    'vat' =>  $detail->part->VAT2,
                    'qty' =>  $detail->Qty,
                    'unitUrl' =>  '',
                    'unit' =>  $detail->UnitID . ($detail->unit->UnitName ? ' - ' . $detail->unit->UnitName : ''),
                    'conversion' =>  $detail->Conversion,
                    'price' =>  $detail->UnitPrice,
                    'discount1' =>  $detail->Discount1 ?? '',
                    'discount1p' =>  $detail->PercentageDisc1 ?? '',
                    'discount2' =>  $detail->Discount2 ?? '',
                    'discount2p' =>  $detail->PercentageDisc2 ?? '',
                    'totalPrice' =>  0,
                    'totalDiscount' =>  $detail->Discount,
                    'division' =>  $detail->DivisionID . ($detail->division->DivisionName ? ' - ' . $detail->division->DivisionName : ''),
                    'warehouse' =>  $detail->WarehouseID . ($detail->warehouse->WarehouseName ? ' - ' . $detail->warehouse->WarehouseName : ''), 'rev' =>  $rev,
                    'initiated' => false,
                ];
            }
        }

        $revData = $this->getRev();

        $options = DocPrint::where('ModuleCode', 'PRETURN')
            ->where('TypeStr', 'print')
            ->get();

        return view('purchase.pr.show', compact('pr', 'type', 'details', 'options', 'revData'));
    }

    public function edit($id){
        $pr = TransPurchaseReturnHD::where('id', $id)->first();
        $details = [];

        $type = 'N';
        if($pr->BasedOnGoodsReceiving == 1){
            $type = 'GR';
        }
        else if($pr->BasedOnDirectPurchase == 1){
            $type = 'DP';
        }

        if($pr->BasedOnGoodsReceiving == 0 && $pr->BasedOnDirectPurchase == 0){
            foreach ($pr->details as $detail){
                $rev = [];
                for ($i = 0; $i < $pr->RevCount; $i++){
                    switch ($i) {
                        case 0:
                            $rev[] = $detail->ItemRevDT01 ?? '';
                            break;
                        case 1:
                            $rev[] = $detail->ItemRevDT02 ?? '';
                            break;
                        case 2:
                            $rev[] = $detail->ItemRevDT03 ?? '';
                            break;
                        case 3:
                            $rev[] = $detail->ItemRevDT04 ?? '';
                            break;
                        case 4:
                            $rev[] = $detail->ItemRevDT05 ?? '';
                            break;
                        case 5:
                            $rev[] = $detail->ItemRevDT06 ?? '';
                            break;
                        case 6:
                            $rev[] = $detail->ItemRevDT07 ?? '';
                            break;
                        case 7:
                            $rev[] = $detail->ItemRevDT08 ?? '';
                            break;
                        case 8:
                            $rev[] = $detail->ItemRevDT09 ?? '';
                            break;
                        case 9:
                            $rev[] = $detail->ItemRevDT10 ?? '';
                            break;
                        case 10:
                            $rev[] = $detail->ItemRevDT11 ?? '';
                            break;
                        case 11:
                            $rev[] = $detail->ItemRevDT12 ?? '';
                            break;
                        case 12:
                            $rev[] = $detail->ItemRevDT13 ?? '';
                            break;
                        case 13:
                            $rev[] = $detail->ItemRevDT14 ?? '';
                            break;
                        case 14:
                            $rev[] = $detail->ItemRevDT15 ?? '';
                            break;
                    }
                }

                $details[] = [
                    'part' =>  $detail->PartID,
                    'sequence' => $detail->Sequence,
                    'vat' =>  $detail->part->VAT2,
                    'qty' =>  $detail->Qty,
                    'unitUrl' => route('misc.partunit2', ['id' => $detail->PartID]),
                    'unit' =>  $detail->UnitID,
                    'conversion' =>  $detail->Conversion,
                    'price' =>  $detail->UnitPrice,
                    'discount1' =>  $detail->Discount1 ?? '',
                    'discount1p' =>  $detail->PercentageDisc1 ?? '',
                    'discount2' =>  $detail->Discount2 ?? '',
                    'discount2p' =>  $detail->PercentageDisc2 ?? '',
                    'totalPrice' =>  0,
                    'totalDiscount' =>  $detail->Discount,
                    'division' =>  $detail->DivisionID,
                    'warehouse' =>  $detail->WarehouseID,
                    'rev' =>  $rev,
                    'initiated' => false,
                ];
            }
        }

        $revData = $this->getRev();

        return view('purchase.pr.edit', compact('pr', 'type', 'details', 'revData'));
    }

    public function update(Request $request){
        $this->validate($request, [
            'id' => 'required',
            'TransactionDate' => 'required',
            'SupplierID' => 'required',
            'DivisionID' => 'required',
            'CurrencyID' => 'required',
            'Rate' => 'required',
            'Notes' => 'nullable|string',
            "part" => "required|array|min:1"
        ], [
            'part.min' => 'Need at least one detail to make an return!'
        ]);

        try {
            DB::beginTransaction();
            if($request->input('Type') != 'N'){
                if($request->input('Type') == 'GR'){
                    $invoice = TransGoodsReceivingHD::where('TransactionNo', $request->input('ReceivingNumber'))->first();
                }
                else if($request->input('Type') == 'DP'){
                    $invoice = TransDirectPurchaseHD::where('TransactionNo', $request->input('ReceivingNumber'))->first();
                }
                $date = Carbon::createFromFormat('d/m/Y', $request->input('TransactionDate'));
                $invDate = Carbon::parse($invoice->TransactionDate);
                if($date->lessThan($invDate)){
                    return redirect()->back()->withInput()
                        ->withErrors([
                            "Transaction Date must not be earlier than Reference's date!"
                        ]);
                }
            }

            $pr = TransPurchaseReturnHD::where('TransactionNo', $request->input('id'))->first();

            $qty = $request->input('qty');
            $qtyOri = $request->input('qty_ori');
            $sequence = $request->input('sequence');
            $warehouse = $request->input('warehouse');

            // VALIDATE INPUT
            $totalQty = 0;
            foreach ($qty as $q){
                $totalQty += $q;
            }

            if($totalQty == 0){
                return redirect()->back()->withInput()
                    ->withErrors([
                        'You need to return at least one item!'
                    ]);
            }

            $errors = [];
            foreach ($request->input('part') as $i => $part){
                $prDetail = TransPurchaseReturnDT::where('TransactionNo', $request->input('id'))
                    ->where('PartID', $part)
                    ->where('Sequence', $sequence[$i])
                    ->first();
                $qtyBase = $request->input('Type') != 'GR' ? ($qty[$i] * $conversion[$i]) : $qty[$i];
                $oldQtyBase = $prDetail
                    ? ((int) $pr->BasedOnGoodsReceiving === 1 ? $prDetail->Qty : $prDetail->Qty * $prDetail->Conversion)
                    : 0;
                $availableStock = BukuStockHelper::calculateCurrentStock($part, $warehouse[$i]) + $oldQtyBase;

                if($request->input('Type') == 'GR'){
                    if($qty[$i] > $qtyOri[$i]){
                        $errors[] = "Can't return " . $part . " because the quantity exceeds the original Goods Receiving amount!";
                    }
                }
                else if($request->input('Type') == 'DP'){
                    if($qty[$i] > $qtyOri[$i]){
                        $errors[] = "Can't return " . $part . " because the quantity exceeds the original Direct Purchase amount!";
                    }
                }

//                if($request->input('Type') == 'GR'){
//                    $grDetail = TransGoodsReceivingDT::where('TransactionNo', $request->input('ReceivingNumber'))
//                        ->where('PartID', $part)
//                        ->where('Sequence', $sequence[$i])
//                        ->first();
//
//                    if($qty[$i] > $grDetail->Qty){
//                        $errors[] = "Can't return " . $part . " because the quantity exceeds the original Goods Receiving amount!";
//                    }
//                }
//                else if($request->input('Type') == 'DP'){
//                    $dpDetail = TransDirectPurchaseDT::where('TransactionNo', $request->input('ReceivingNumber'))
//                        ->where('PartID', $part)
//                        ->where('Sequence', $sequence[$i])
//                        ->first();
//
//                    if($qty[$i] > $dpDetail->Qty){
//                        $errors[] = "Can't return " . $part . " because the quantity exceeds the original Direct Purchase amount!";
//                    }
//                }

                if($qtyBase > $availableStock){
                    $errors[] = "Can't return " . $part . " because the quantity exceeds the current buku stock amount!";
                }
            }

            if(count($errors) > 0){
                return redirect()->back()->withInput()
                    ->withErrors($errors);
            }

            // UPDATE

            $pr->update([
                'TransactionDate' => \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('Y-m-d'),
                'SupplierID' => $request->input('SupplierID'),
                'DivisionID' => $request->input('DivisionID'),
                'CurrencyID' => $request->input('CurrencyID'),
                'BasedOnGoodsReceiving' => $request->input('Type') == 'GR' ? 1 : 0,
                'BasedOnDirectPurchase' => $request->input('Type') == 'DP' ? 1 : 0,
                'BasedOnQCReceiving' => 0,
                'ReceivingNumber' => $request->input('ReceivingNumber') ?? '',
                'Rate' => $request->input('Rate') ?? 1,
                'SubTotal' => $request->input('GrandTotal') ?? 0,
                'GrandTotal' => $request->input('GrandTotal') ?? 0,
                'Notes' => $request->input('Notes') ? trim($request->input('Notes')) : null,
                'Editable' => 1,
                'RevCount' => $request->input('rev') ?? 0,
                'LastUpdateBy' => Auth::user()->UserID,
                'LastUpdate' => date('Y-m-d H:i:s'),
            ]);

            $details = [];
            $BSDetails = [];


            $unit = $request->input('unit');
            $price = $request->input('price');
            $disc = $request->input('discount');
            $disc1 = $request->input('discount1');
            $disc1p = $request->input('discount1p');
            $disc2 = $request->input('discount2');
            $disc2p = $request->input('discount2p');
            $division = $request->input('division');

            BukuStock::where('TransactionNo', $request->input('id'))->delete();
            TransPurchaseReturnDT::where('TransactionNo', $request->input('id'))->delete();

            foreach ($request->input('part') as $i => $part){
                if($qty[$i] > 0){
                    $newDetail = [
                        'TransactionNo' => $request->input('id'),
                        'PartID' => $part,
                        'Qty' => $qty[$i],
                        'Sequence' => $sequence[$i],
                        'UnitID' => $unit[$i],
                        'Conversion' => $conversion[$i],
                        'UnitPrice' => $price[$i],
                        'PercentageDisc1' => $disc1p[$i],
                        'Discount1' => $disc1[$i],
                        'PercentageDisc2' => $disc2p[$i],
                        'Discount2' => $disc2[$i],
                        'Discount' => $disc[$i],
                        'DivisionID' => $division[$i],
                        'WarehouseID' => $warehouse[$i],
                    ];

                    for ($j = 1; $j <= $request->input('rev'); $j++){
                        if($j < 10){
                            $newDetail['ItemRevDT0' . $j] = $request->input('rev'.$j)[$i];
                        }
                        else {
                            $newDetail['ItemRevDT' . $j] = $request->input('rev'.$j)[$i];
                        }
                    }

                    $details[] = $newDetail;

                    // INSERT BUKU STOK
                    $qtyBase = $request->input('Type') != 'GR' ? ($qty[$i] * $conversion[$i]) : $qty[$i];
                    $unit2 = MsPartUnit::where('PartID', $part)
                        ->where('UnitID2', $unit[$i])
                        ->first();
                    $BSDetails[] = [
                        'TransactionNo' => $request->input('id'),
                        'TransactionDate' => \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('Y-m-d'),
                        'PartID' => $part,
                        'WarehouseID' => $warehouse[$i],
                        'Sequence' => $i,
                        'UnitID' => $request->input('Type') != 'GR' ? $unit2->UnitID1 : $unit[$i],
                        'Qty' => (double)($qtyBase * -1),
                        'TransactionType' => 'PURCHASE_RETURN',
                        'CreatedBy' => Auth::user()->UserID,
                        'EntryTime' => date('Y-m-d H:i:s'),
                    ];
                }
            }

            TransPurchaseReturnDT::insert($details);
            BukuStock::insert($BSDetails);


            // INSERT BUKU HUTANG
            BukuHutang::where('TransactionNo', $request->input('id'))->delete();
            if($request->input('Type') != 'GR'){
                BukuHutang::create([
                    'TransactionNo' => $request->input('id'),
                    'BalanceNo' => $request->input('ReceivingNumber') ?? $request->input('id'),
                    'TransactionDate' => \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('Y-m-d'),
                    'DueDate' => \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('Y-m-d'),
                    'SupplierID' => $request->input('SupplierID'),
                    'CurrencyID' => $request->input('CurrencyID'),
                    'Rate' => $request->input('Rate') ?? 1,
                    'Amount' => $request->input('GrandTotal') ? ($request->input('GrandTotal') * -1) : 0,
                    'isVoucher' => 0,
                    'TransactionType' => 'PURCHASE_RETURN',
                    'CreatedBy' => Auth::user()->UserID,
                    'EntryTime' => date('Y-m-d H:i:s'),
                ]);
            }


            // INSERT JOURNAL
            \App\Services\PurchaseReturnJournalService::rebuild($request->input('id'));



            // CHECK OUTSTANDING
            if($request->input('Type') == 'GR'){
                $this->checkOutstandingGR($request->input('ReceivingNumber'));
            }
            else if($request->input('Type') == 'DP'){
                $this->checkOutstandingDP($request->input('ReceivingNumber'));
            }

            DB::commit();
            return redirect()->route('pr')
                ->with([
                    'type' => 'success',
                    'icon' => 'fa fa-fw fa-circle-check',
                    'message' => 'Purchase Return successfully updated!'
                ]);
        } catch (\Exception $e){
            Log::error($e);
            DB::rollBack();
            return redirect()->back()->withInput()->withErrors([
                $e->getMessage() ?: 'Something went wrong!'
            ]);
        }
    }

    public function checkOutstandingGR($GRNumber){
        $gr = TransGoodsReceivingHD::where('TransactionNo', $GRNumber)->first();

        $checkOutstanding = true;
        $checkEditable = true;

        $checkReturn = TransPurchaseReturnHD::where('BasedOnGoodsReceiving', 1)
            ->where('ReceivingNumber', $GRNumber)
            ->first();
        if($checkReturn){
            $checkEditable = false;
        }

        $checkGR = TransPurchaseInvoiceDT::where('ReffNumber', $GRNumber)->first();
        if($checkGR){
            $checkOutstanding = false;
            $checkEditable = false;
        }

        $gr->update([
            'Editable' => $checkEditable,
            'Outstanding' => $checkOutstanding,
        ]);
    }

    public function checkOutstandingDP($DPNumber){
        $dp = TransDirectPurchaseHD::where('TransactionNo', $DPNumber)->first();

        $checkOutstanding = true;
        $checkEditable = true;

        $checkReturn = TransPurchaseReturnHD::where('BasedOnDirectPurchase', 1)
            ->where('ReceivingNumber', $DPNumber)
            ->first();
        if($checkReturn){
            $checkEditable = false;
        }

        $checkPayment = TransDirectVendorPaymentDT::where('InvoiceNumber', $DPNumber)
            ->first();
        if($checkPayment){
            $checkEditable = false;
        }

        $checkSum = BukuHutang::where('BalanceNo', $DPNumber)->sum('Amount');

        if($checkSum == 0){
            $checkOutstanding = false;
            $checkEditable = false;
        }

        $dp->update([
            'Editable' => $checkEditable,
            'Outstanding' => $checkOutstanding,
        ]);
    }

    public function destroy($id){
        try {
            DB::beginTransaction();
            $pr = TransPurchaseReturnHD::where('id', $id)->first();
            $type = $pr->BasedOnGoodsReceiving == 1 ? 'GR' : ($pr->BasedOnDirectPurchase == 1 ? 'DP' : '');
            $invNumber = $pr->ReceivingNumber;

            BukuHutang::where('TransactionNo', $pr->TransactionNo)->delete();
            BukuStock::where('TransactionNo', $pr->TransactionNo)->delete();

            TransJournalDT::where('TransactionNo', $pr->TransactionNo)->delete();
            TransJournalHD::where('TransactionNo', $pr->TransactionNo)->delete();

            TransPurchaseReturnDT::where('TransactionNo', $pr->TransactionNo)->delete();
            $pr->delete();

            if($type == 'GR'){
                $this->checkOutstandingGR($invNumber);
            }
            else if($type == 'DP'){
                $this->checkOutstandingDP($invNumber);
            }
            DB::commit();
            return response([
                'status' => 'success'
            ]);
        } catch (\Exception $e){
            Log::error($e);
            DB::rollBack();
            return response([
                'status' => 'failed',
            ]);
        }
    }
}
