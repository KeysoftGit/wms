<?php

namespace App\Http\Controllers\Stock;

use App\Helpers\BukuStockHelper;
use App\Helpers\CoilNoHelper;
use App\Helpers\DualQuantityHelper;
use App\Services\WarehouseAccessCriteria;
use App\Models\DocPrint;
use App\Models\BukuStock;
use App\Models\MsWarehouse;
use App\Models\MsAutoNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use App\Models\TransDirectItemTransferDT;
use App\Models\TransDirectItemTransferHD;

class ItemTransferController extends Controller
{
private function insertInChunks(string $modelClass, array $rows): void
    {
        foreach (array_chunk($rows, 10) as $chunk) {
            $modelClass::insert($chunk);
        }
    }

    public function index()
    {
        return view('stock.transfer.index');
    }

    public function datatable(Request $request)
    {
        $data = TransDirectItemTransferHD::select('id', 'TransactionNo', 'TransactionDate', 'WarehouseIDFrom',  'WarehouseIDTo', 'Notes', 'Editable');
        WarehouseAccessCriteria::apply($data, ['WarehouseIDFrom', 'WarehouseIDTo']);

        if ($request->get('date')) {
            $dates = explode(' to ', $request->get('date'));
            if (count($dates) > 1) {
                $data->whereDate('TransactionDate', '>=', \DateTime::createFromFormat('d/m/Y', $dates[0])->format('Y-m-d'));
                $data->whereDate('TransactionDate', '<=', \DateTime::createFromFormat('d/m/Y', $dates[1])->format('Y-m-d'));
            } else {
                $data->whereDate('TransactionDate', \DateTime::createFromFormat('d/m/Y', $dates[0])->format('Y-m-d'));
            }
        }

        return DataTables::of($data)
            ->addColumn('action', function ($row) {
                $btn = '<div class="btn-group">';

                $btn .= '<a class="btn btn-sm btn-alt-secondary js-bs-tooltip-enabled" data-bs-toggle="tooltip" title="Show" href="' . route('transfer.show', $row->id) . '"><i class="fa fa-fw fa-eye"></i></a>';
                if ($row->Editable == 1) {
                    if (Auth::user()->hasAnyPermission(['admin', 'transfer.edit'])) {
                        $btn .= '<a class="btn btn-sm btn-alt-secondary js-bs-tooltip-enabled" data-bs-toggle="tooltip" title="Edit" href="' . route('transfer.edit', $row->id) . '"><i class="fa fa-fw fa-edit"></i></a>';
                    }
                    if (Auth::user()->hasAnyPermission(['admin', 'transfer.delete'])) {
                        $btn .= '<button class="btn btn-sm btn-alt-secondary js-bs-tooltip-enabled delete-btn" data-bs-toggle="tooltip" title="Delete" data-url="' . route('transfer.delete', $row->id) . '"><i class="fa fa-fw fa-trash"></i></button>';
                    }
                }

                return $btn;
            })
            ->make(true);
    }

    public function add()
    {
        return view('stock.transfer.add');
    }

    public function getStock(Request $request)
    {
        $partId = $request->query('part');
        $warehouseId = $request->query('warehouse');

        if (!$partId || !$warehouseId) {
            return response([
                'stock' => 0,
                'stock2' => null,
                'unit_id2' => null,
            ]);
        }

        $qtyOri = (float) $request->query('qty_ori', 0);
        $qty2Ori = (float) $request->query('qty2_ori', 0);
        $conversion = (float) $request->query('conversion', 1);
        $stock = BukuStockHelper::calculateCurrentStock($partId, $warehouseId) + ($qtyOri * $conversion);
        $secondary = DualQuantityHelper::currentStockSecondaryQuantity($partId, $warehouseId, null, null, null, null, null, false);
        $stock2 = $secondary['qty2'] !== null ? (float) $secondary['qty2'] + $qty2Ori : null;

        return response([
            'stock' => $stock,
            'stock2' => $stock2,
            'unit_id2' => $secondary['unit_id2'],
        ]);
    }

    public function getStockOptions(Request $request)
    {
        $unitId = $request->get('unit');
        $options = BukuStock::query()
            ->where('WarehouseID', $request->get('warehouse'))
            ->where('PartID', $request->get('part'))
            ->when($request->get('transaction'), fn ($query, $transactionNo) => $query->where('TransactionNo', '<>', $transactionNo))
            ->select('BatchNo')
            ->selectRaw('SUM(Qty) as Qty')
            ->selectRaw('SUM(CASE WHEN UnitID = ? THEN Qty ELSE 0 END) as BaseUnitQty', [$unitId])
            ->selectRaw('SUM(CASE WHEN UnitID = ? AND UnitID2 IS NOT NULL AND Qty2 IS NOT NULL AND Qty2 <> 0 THEN Qty2 ELSE 0 END) as BaseRatioQty2', [$unitId])
            ->selectRaw('MIN(CASE WHEN UnitID = ? AND UnitID2 IS NOT NULL AND Qty2 IS NOT NULL AND Qty2 <> 0 THEN UnitID2 ELSE NULL END) as BaseUnitID2', [$unitId])
            ->selectRaw('SUM(CASE WHEN UnitID2 = ? AND Qty2 IS NOT NULL AND Qty2 <> 0 THEN Qty ELSE 0 END) as RatioQty', [$unitId])
            ->selectRaw('SUM(CASE WHEN UnitID2 = ? AND Qty2 IS NOT NULL AND Qty2 <> 0 THEN Qty2 ELSE 0 END) as RatioQty2', [$unitId])
            ->selectRaw('MIN(CASE WHEN UnitID2 = ? AND Qty2 IS NOT NULL AND Qty2 <> 0 THEN UnitID ELSE NULL END) as RatioUnitID', [$unitId])
            ->groupBy('BatchNo')
            ->havingRaw('SUM(Qty) > 0')
            ->get();

        $options->each(function ($option) {
            $ratioQty2 = (float) ($option->RatioQty2 ?? 0);
            $option->Conversion = $ratioQty2 > 0.000001 ? (float) $option->RatioQty / $ratioQty2 : null;
        });

        return response()->json(['options' => $options]);
    }
    private function normalizedTransferStockQty(
        string $partId,
        ?string $warehouseId,
        ?string $unitId,
        float $qty,
        float $conversion
    ): float {
        $secondary = DualQuantityHelper::currentStockSecondaryQuantity($partId, $warehouseId, null, null, null, null, null, false);
        $currentQty1 = abs((float) ($secondary['qty1'] ?? 0));
        $currentQty2 = abs((float) ($secondary['qty2'] ?? 0));
        $secondaryUnit = $secondary['unit_id2'] ?? null;

        if ($secondaryUnit && $unitId === $secondaryUnit && $currentQty1 > 0.000001 && $currentQty2 > 0.000001) {
            return $qty / $currentQty2 * $currentQty1;
        }

        return $qty * $conversion;
    }

    public function oldStore(Request $request)
    {
        $this->validate($request, [
            'TransactionNo' => 'required_without:automatic|string|max:50|unique:Trans_DirectItemTransferHD,TransactionNo',
            'TransactionDate' => 'required',
            'WarehouseIDFrom' => 'required',
            'StaffInChargeIDFrom' => 'required',
            "WarehouseIDTo" => "required",
            "StaffInChargeIDTo" => "required",
            'Notes' => 'nullable|string',
            "part" => "required|array|min:1"
        ], [
            'TransactionNo.unique' => 'Transaction No has already been taken!',
            'TransactionNo.max' => 'Transaction No maximum characters is 50!',
        ]);

        try {
            // VALIDATION

            if ($request->input('WarehouseIDFrom') == $request->input('WarehouseIDTo')) {
                return redirect()->back()->withInput()
                    ->withErrors([
                        'Target warehouse must not be the same as source warehouse!'
                    ]);
            }

            $part = $request->input('part');
            $unit = $request->input('unit');
            $conversion = $request->input('conversion');
            $qty = $request->input('qty');

            $checkData = [];
            foreach ($part as $i => $id) {
                $stockQty = $this->normalizedTransferStockQty(
                    $id,
                    $request->input('WarehouseIDFrom'),
                    $unit[$i] ?? null,
                    (float) ($qty[$i] ?? 0),
                    (float) ($conversion[$i] ?? 1)
                );
                if (array_key_exists($id, $checkData)) {
                    $checkData[$id]['qty'] += $stockQty;
                } else {
                    $checkData[$id] = [
                        'qty' => $stockQty
                    ];
                }
            }

            foreach ($checkData as $key => $check) {
                if ($check['qty'] > BukuStockHelper::calculateCurrentStock($key, $request->input('WarehouseIDFrom'))) {
                    return redirect()->back()->withInput()
                        ->withErrors([
                            'Transfer amount must not exceed current stock on the source warehouse!'
                        ]);
                }
            }


            // INSERT

            $masterAuto = MsAutoNumber::find("1");

            $digit = null;

            if ($request->input('automatic')) {
                $checkLast = TransDirectItemTransferHD::where('IsAuto', 1)
                    ->whereMonth('TransactionDate', \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('m'))
                    ->whereYear('TransactionDate', \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('Y'))
                    ->orderBy('LastDigit', 'desc')
                    ->first();

                if (!$checkLast) {
                    $digit = 1;
                } else {
                    $digit = $checkLast->LastDigit + 1;
                }

                $id = $masterAuto->Inventory10 . '/' . \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('Y')
                    . '/' . \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('m')
                    . '/' . str_pad($digit, 4, "0", STR_PAD_LEFT);

                $checkExist = TransDirectItemTransferHD::where('TransactionNo', $id)->first();
                if ($checkExist) {
                    return redirect()->back()->withInput()->withErrors([
                        "Transaction No has already been taken!"
                    ]);
                }
            } else {
                $id = trim($request->input('TransactionNo'));
            }

            TransDirectItemTransferHD::create([
                'TransactionNo' => $id,
                'TransactionDate' => \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('Y-m-d'),
                'WarehouseIDFrom' => $request->input('WarehouseIDFrom'),
                'StaffInChargeIDFrom' => $request->input('StaffInChargeIDFrom'),
                'WarehouseIDTo' => $request->input('WarehouseIDTo'),
                'StaffInChargeIDTo' => $request->input('StaffInChargeIDTo'),
                'Notes' => $request->input('Notes') ? trim($request->input('Notes')) : null,
                'CreatedBy' => Auth::user()->UserID,
                'EntryTime' => date('Y-m-d H:i:s'),
                'LastUpdateBy' => Auth::user()->UserID,
                'LastUpdate' => date('Y-m-d H:i:s'),
                'IsAuto' => $request->input('automatic') ?? 0,
                'LastDigit' => $digit,
                'Editable' => true
            ]);

            $cartoon = $request->input('cartoon');
            $dimension = $request->input('dimension');
            $notes = $request->input('notes');

            $details = [];
            $bsDetails = [];
            foreach ($part as $i => $partId) {
                $stockQty = $this->normalizedTransferStockQty(
                    $partId,
                    $request->input('WarehouseIDFrom'),
                    $unit[$i] ?? null,
                    (float) ($qty[$i] ?? 0),
                    (float) ($conversion[$i] ?? 1)
                );
                $sourceSecondaryColumns = DualQuantityHelper::bukuStockSecondaryColumns(
                    $stockQty * -1,
                    $partId,
                    $request->input('WarehouseIDFrom'),
                    null,
                    null,
                    null,
                    null,
                    null,
                    false
                );

                $details[] = [
                    'TransactionNo' => $id,
                    'PartID' => $partId,
                    'UnitID' => $unit[$i],
                    'Qty' => $qty[$i],
                    'Dimension' => $dimension[$i] ?? '',
                    'CartoonNo' => $cartoon[$i] ?? '',
                    'Notes' => $notes[$i] ?? '',
                    'Sequence' => $i,
                    'Conversion' => $conversion[$i]
                ];

                $bsDetails[] = array_merge([
                    'TransactionNo' => $id,
                    'TransactionDate' => \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('Y-m-d'),
                    'PartID' => $partId,
                    'WarehouseID' => $request->input('WarehouseIDFrom'),
                    'Sequence' => $i,
                    'UnitID' => $unit[$i],
                    'Qty' => $stockQty * -1,
                    'TransactionType' => 'DIRECT_ITEMTRANSFER',
                    'CreatedBy' => Auth::user()->UserID,
                    'EntryTime' => date('Y-m-d H:i:s'),
                ], $sourceSecondaryColumns);

                $targetSecondaryColumns = $sourceSecondaryColumns;
                if (array_key_exists('Qty2', $targetSecondaryColumns) && $targetSecondaryColumns['Qty2'] !== null) {
                    $targetSecondaryColumns['Qty2'] = abs((float) $targetSecondaryColumns['Qty2']);
                }

                $bsDetails[] = array_merge([
                    'TransactionNo' => $id,
                    'TransactionDate' => \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('Y-m-d'),
                    'PartID' => $partId,
                    'WarehouseID' => $request->input('WarehouseIDTo'),
                    'Sequence' => $i,
                    'UnitID' => $unit[$i],
                    'Qty' => $stockQty,
                    'TransactionType' => 'DIRECT_ITEMTRANSFER',
                    'CreatedBy' => Auth::user()->UserID,
                    'EntryTime' => date('Y-m-d H:i:s'),
                ], $targetSecondaryColumns);
            }

            $this->insertInChunks(TransDirectItemTransferDT::class, $details);
            $this->insertInChunks(BukuStock::class, $bsDetails);

            return redirect()->route('transfer')
                ->with([
                    'type' => 'success',
                    'icon' => 'fa fa-fw fa-circle-check',
                    'message' => 'Item Transfer successfully added!'
                ]);
        } catch (\Exception $exception) {
            Log::error($exception);

            return redirect()->back()->withInput()->withErrors([
                'Something went wrong!'
            ]);
        }
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'TransactionNo' => 'required_without:automatic|string|max:50|unique:Trans_DirectItemTransferHD,TransactionNo',
            'TransactionDate' => 'required',
            'WarehouseIDFrom' => 'required',
            'StaffInChargeIDFrom' => 'required',
            "WarehouseIDTo" => "required",
            "StaffInChargeIDTo" => "required",
            'Notes' => 'nullable|string',
            "part" => "required|array|min:1",
            'qty_actual.*' => 'nullable|numeric',
            'qty2_actual.*' => 'nullable|numeric',
            'unit1.*' => 'nullable|string',
            'unit2.*' => 'nullable|string',
        ], [
            'TransactionNo.unique' => 'Transaction No has already been taken!',
            'TransactionNo.max' => 'Transaction No maximum characters is 50!',
        ]);

        DB::beginTransaction();
        try {
            if ($request->input('WarehouseIDFrom') == $request->input('WarehouseIDTo')) {
                throw new \Exception('Target warehouse must not be the same as source warehouse!');
            }

            $part = $request->input('part');
            $unit = $request->input('unit');
            $conversion = $request->input('conversion');
            $qty = $request->input('qty');
            $cartoon = $request->input('cartoon', []);

            $checkData = [];
            foreach ($part as $i => $id) {
                $batchNo = $cartoon[$i] ?? null;
                $usedQty = (float)($request->qty_actual[$i] ?? ((float)$qty[$i] * (float)$conversion[$i]));
                $stockKey = $id . '|' . ($batchNo ?? '');
                if (array_key_exists($stockKey, $checkData)) {
                    $checkData[$stockKey]['qty'] += $usedQty;
                } else {
                    $checkData[$stockKey] = ['part' => $id, 'batch' => $batchNo, 'qty' => $usedQty];
                }
            }

            foreach ($checkData as $check) {
                $stockQty = BukuStockHelper::calculateCurrentStock($check['part'], $request->input('WarehouseIDFrom'), $check['batch']);
                if ($check['qty'] > $stockQty) {
                    throw new \Exception('Transfer amount must not exceed current stock on the source warehouse!');
                }
            }

            $masterAuto = MsAutoNumber::find("1");
            $digit = null;
            if ($request->input('automatic')) {
                $checkLast = TransDirectItemTransferHD::where('IsAuto', 1)
                    ->whereMonth('TransactionDate', \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('m'))
                    ->whereYear('TransactionDate', \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('Y'))
                    ->orderBy('LastDigit', 'desc')
                    ->first();
                $digit = $checkLast ? $checkLast->LastDigit + 1 : 1;
                do {
                    $id = $masterAuto->Inventory10 . '/' . \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('Y') . '/' . \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('m') . '/' . str_pad($digit, 4, "0", STR_PAD_LEFT);
                    $checkExist = TransDirectItemTransferHD::where('TransactionNo', $id)->first();
                    if ($checkExist) $digit++;
                } while ($checkExist);
            } else {
                $id = trim($request->input('TransactionNo'));
            }

            TransDirectItemTransferHD::create([
                'TransactionNo' => $id,
                'TransactionDate' => \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('Y-m-d'),
                'WarehouseIDFrom' => $request->input('WarehouseIDFrom'),
                'StaffInChargeIDFrom' => $request->input('StaffInChargeIDFrom'),
                'WarehouseIDTo' => $request->input('WarehouseIDTo'),
                'StaffInChargeIDTo' => $request->input('StaffInChargeIDTo'),
                'Notes' => $request->input('Notes') ? trim($request->input('Notes')) : null,
                'CreatedBy' => Auth::user()->UserID,
                'EntryTime' => date('Y-m-d H:i:s'),
                'LastUpdateBy' => Auth::user()->UserID,
                'LastUpdate' => date('Y-m-d H:i:s'),
                'IsAuto' => $request->input('automatic') ?? 0,
                'LastDigit' => $digit,
                'Editable' => true
            ]);

            $dimension = $request->input('dimension', []);
            $notes = $request->input('notes', []);
            $details = [];
            $bsDetails = [];
            foreach ($part as $i => $partId) {
                $usedQty = (float)($request->qty_actual[$i] ?? ((float)$qty[$i] * (float)$conversion[$i]));
                $sourceQty = (float)($request->qty2_actual[$i] ?? ($qty[$i] ?? 0));
                $unit1 = $request->unit1[$i] ?? $unit[$i];
                $unit2 = $request->unit2[$i] ?? $unit[$i];
                $batchNo = $cartoon[$i] ?? null;

                $details[] = ['TransactionNo' => $id, 'PartID' => $partId, 'UnitID' => $unit[$i], 'Qty' => $qty[$i], 'Dimension' => $dimension[$i] ?? '', 'CartoonNo' => $batchNo ?? '', 'Notes' => $notes[$i] ?? '', 'Sequence' => $i, 'Conversion' => $conversion[$i]];
                $baseStockRow = ['TransactionNo' => $id, 'TransactionDate' => \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('Y-m-d'), 'PartID' => $partId, 'Sequence' => $i, 'UnitID' => $unit1, 'UnitID2' => $unit2, 'BatchNo' => $batchNo, 'TransactionType' => 'DIRECT_ITEMTRANSFER', 'CreatedBy' => Auth::user()->UserID, 'EntryTime' => date('Y-m-d H:i:s')];
                $bsDetails[] = array_merge($baseStockRow, ['WarehouseID' => $request->input('WarehouseIDFrom'), 'Qty' => $usedQty * -1, 'Qty2' => $sourceQty * -1]);
                $bsDetails[] = array_merge($baseStockRow, ['WarehouseID' => $request->input('WarehouseIDTo'), 'Qty' => $usedQty, 'Qty2' => $sourceQty]);
            }

            $this->insertInChunks(TransDirectItemTransferDT::class, $details);
            $this->insertInChunks(BukuStock::class, $bsDetails);
            DB::commit();
            clear_form_preservation('stock_transfer_add');
            return redirect()->route('transfer')->with(['type' => 'success', 'icon' => 'fa fa-fw fa-circle-check', 'message' => 'Item Transfer successfully added!']);
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error($exception);
            return redirect()->back()->withInput()->withErrors([$exception->getMessage()]);
        }
    }
    public function showDetail($detail, ?BukuStock $sourceStock = null)
    {
        if (!$sourceStock) {
            $sourceStock = BukuStock::where('TransactionNo', $detail->TransactionNo)
                ->whereIn('TransactionType', ['DIRECT_ITEMTRANSFER', 'LOCATION_ADJUSTMENT'])
                ->where('Sequence', $detail->Sequence)
                ->where('PartID', $detail->PartID)
                ->where('Qty', '<', 0)
                ->first();
        }

        $actualQty = abs((float) ($sourceStock->Qty ?? ((float) $detail->Qty * (float) $detail->Conversion)));
        $qty2 = abs((float) ($sourceStock->Qty2 ?? 0));
        $unit1 = $sourceStock->UnitID ?? $detail->UnitID;
        $unit2 = $sourceStock->UnitID2 ?? $detail->UnitID;
        $selectedQty = (float) $detail->Qty;
        $conversion = (float) $detail->Conversion;

        if ($detail->UnitID && $unit2 && (string) $detail->UnitID === (string) $unit2 && $qty2 > 0.000001) {
            $selectedQty = $qty2;
            $conversion = $actualQty / $qty2;
        } elseif ($detail->UnitID && $unit1 && (string) $detail->UnitID === (string) $unit1) {
            $selectedQty = $actualQty;
            $conversion = 1;
        }

        return [
            'id' => generateRandomString(10),
            'part' => $detail->PartID,
            'part_name' => optional($detail->part)->PartName,
            'partName' => $detail->PartID . ($detail->part->PartName ? ' - ' . $detail->part->PartName : ''),
            'qty' => $selectedQty,
            'qtyOri' => $selectedQty,
            'qty_actual' => $actualQty,
            'qty2_actual' => $qty2 > 0.000001 ? $qty2 : $selectedQty,
            'unit1' => $unit1,
            'unit2' => $unit2,
            'unitUrl' => route('misc.partunit2', ['id' => $detail->PartID]),
            'unit' => $detail->UnitID,
            'conversion' => $conversion,
            'stock' => 0,
            'cartoon' => $sourceStock->BatchNo ?? $detail->CartoonNo,
            'dimension' => $detail->Dimension,
            'Notes' => $detail->Notes,
            'notes' => $detail->Notes,
            'warehouseIdFrom' => $detail->WarehouseIDFrom,
            'warehouseNameFrom' => $detail->WarehouseIDFrom ? (MsWarehouse::where('WarehouseID', $detail->WarehouseIDFrom)->value('WarehouseName') ?: $detail->WarehouseIDFrom) : null,
            'batchNo' => $sourceStock->BatchNo ?? $detail->CartoonNo,
            'coilNo' => null,
            'qty2' => $qty2 > 0.000001 ? $qty2 : null,
            'unitId2' => $unit2,
            'stock_options' => [],
            'stock_options_loaded' => false,
            'stock_request_id' => 0,
            'stock_key' => '',
            'initiated' => false,
        ];
    }
    public function show($id)
    {
        $transfer = TransDirectItemTransferHD::where('id', $id)->first();

        $details = [];
        foreach ($transfer->details as $detail) {
            $sourceStock = BukuStock::where('TransactionNo', $transfer->TransactionNo)
                ->whereIn('TransactionType', ['DIRECT_ITEMTRANSFER', 'LOCATION_ADJUSTMENT'])
                ->where('Sequence', $detail->Sequence)
                ->where('PartID', $detail->PartID)
                ->where('Qty', '<', 0)
                ->first();

            $details[] = $this->showDetail($detail, $sourceStock);
        }

        $options = DocPrint::where('ModuleCode', 'DIT')
            ->where('TypeStr', 'print')
            ->get();

        return view('stock.transfer.show', compact('transfer', 'details', 'options'));
    }

    public function edit($id)
    {
        $transfer = TransDirectItemTransferHD::where('id', $id)->first();

        $details = [];
        foreach ($transfer->details as $detail) {
            $sourceStock = BukuStock::where('TransactionNo', $transfer->TransactionNo)
                ->whereIn('TransactionType', ['DIRECT_ITEMTRANSFER', 'LOCATION_ADJUSTMENT'])
                ->where('Sequence', $detail->Sequence)
                ->where('PartID', $detail->PartID)
                ->where('Qty', '<', 0)
                ->first();

            $details[] = $this->showDetail($detail, $sourceStock);
        }

        return view('stock.transfer.edit', compact('transfer', 'details'));
    }


    public function update(Request $request)
    {
        $this->validate($request, [
            'WarehouseIDFrom' => 'required',
            'StaffInChargeIDFrom' => 'required',
            "WarehouseIDTo" => "required",
            "StaffInChargeIDTo" => "required",
            'Notes' => 'nullable|string',
            "part" => "required|array|min:1",
            'qty_actual.*' => 'nullable|numeric',
            'qty2_actual.*' => 'nullable|numeric',
            'unit1.*' => 'nullable|string',
            'unit2.*' => 'nullable|string',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $transfer = TransDirectItemTransferHD::where('TransactionNo', $request->input('id'))->first();
                if ($request->input('WarehouseIDFrom') == $request->input('WarehouseIDTo')) {
                    throw new \Exception('Target warehouse must not be the same as source warehouse!');
                }

                $part = $request->input('part');
                $unit = $request->input('unit');
                $conversion = $request->input('conversion');
                $qty = $request->input('qty');
                $cartoon = $request->input('cartoon', []);
                BukuStock::where('TransactionNo', $request->input('id'))->delete();

                $checkData = [];
                foreach ($part as $i => $id) {
                    $batchNo = $cartoon[$i] ?? null;
                    $usedQty = (float)($request->qty_actual[$i] ?? ((float)$qty[$i] * (float)$conversion[$i]));
                    $stockKey = $id . '|' . ($batchNo ?? '');
                    if (array_key_exists($stockKey, $checkData)) {
                        $checkData[$stockKey]['qty'] += $usedQty;
                    } else {
                        $checkData[$stockKey] = ['part' => $id, 'batch' => $batchNo, 'qty' => $usedQty];
                    }
                }

                foreach ($checkData as $check) {
                    $stockQty = BukuStockHelper::calculateCurrentStock($check['part'], $request->input('WarehouseIDFrom'), $check['batch']);
                    if ($check['qty'] > $stockQty) {
                        throw new \Exception('Transfer amount must not exceed current stock on the source warehouse!');
                    }
                }

                $transfer->update([
                    'TransactionDate' => \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('Y-m-d'),
                    'WarehouseIDFrom' => $request->input('WarehouseIDFrom'),
                    'StaffInChargeIDFrom' => $request->input('StaffInChargeIDFrom'),
                    'WarehouseIDTo' => $request->input('WarehouseIDTo'),
                    'StaffInChargeIDTo' => $request->input('StaffInChargeIDTo'),
                    'Notes' => $request->input('Notes') ? trim($request->input('Notes')) : null,
                    'LastUpdateBy' => Auth::user()->UserID,
                    'LastUpdate' => date('Y-m-d H:i:s'),
                ]);

                TransDirectItemTransferDT::where('TransactionNo', $request->input('id'))->delete();
                $dimension = $request->input('dimension', []);
                $notes = $request->input('notes', []);
                $details = [];
                $bsDetails = [];
                foreach ($part as $i => $partId) {
                    $usedQty = (float)($request->qty_actual[$i] ?? ((float)$qty[$i] * (float)$conversion[$i]));
                    $sourceQty = (float)($request->qty2_actual[$i] ?? ($qty[$i] ?? 0));
                    $unit1 = $request->unit1[$i] ?? $unit[$i];
                    $unit2 = $request->unit2[$i] ?? $unit[$i];
                    $batchNo = $cartoon[$i] ?? null;
                    $details[] = ['TransactionNo' => $request->input('id'), 'PartID' => $partId, 'UnitID' => $unit[$i], 'Qty' => $qty[$i], 'Dimension' => $dimension[$i] ?? '', 'CartoonNo' => $batchNo ?? '', 'Notes' => $notes[$i] ?? '', 'Sequence' => $i, 'Conversion' => $conversion[$i]];
                    $baseStockRow = ['TransactionNo' => $request->input('id'), 'TransactionDate' => \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('Y-m-d'), 'PartID' => $partId, 'Sequence' => $i, 'UnitID' => $unit1, 'UnitID2' => $unit2, 'BatchNo' => $batchNo, 'TransactionType' => 'DIRECT_ITEMTRANSFER', 'CreatedBy' => Auth::user()->UserID, 'EntryTime' => date('Y-m-d H:i:s')];
                    $bsDetails[] = array_merge($baseStockRow, ['WarehouseID' => $request->input('WarehouseIDFrom'), 'Qty' => $usedQty * -1, 'Qty2' => $sourceQty * -1]);
                    $bsDetails[] = array_merge($baseStockRow, ['WarehouseID' => $request->input('WarehouseIDTo'), 'Qty' => $usedQty, 'Qty2' => $sourceQty]);
                }

                $this->insertInChunks(TransDirectItemTransferDT::class, $details);
                $this->insertInChunks(BukuStock::class, $bsDetails);
            });

            return redirect()->route('transfer')->with(['type' => 'success', 'icon' => 'fa fa-fw fa-circle-check', 'message' => 'Item Transfer successfully updated!']);
        } catch (\Exception $exception) {
            Log::error($exception);
            return redirect()->back()->withInput()->withErrors([$exception->getMessage() ?? 'Something went wrong']);
        }
    }
    public function destroy($id)
    {
        try {
            DB::transaction(function () use ($id) {
                $transfer = TransDirectItemTransferHD::where('id', $id)->first();

                // DELETE BUKU STOCK
                BukuStock::where('TransactionNo', $transfer->TransactionNo)->delete();

                TransDirectItemTransferDT::where('TransactionNo', $transfer->TransactionNo)->delete();
                $transfer->delete();
            });


            return response([
                'status' => 'success'
            ]);
        } catch (\Exception $e) {
            Log::error($e);

            return response([
                'status' => 'failed',
            ]);
        }
    }
}
