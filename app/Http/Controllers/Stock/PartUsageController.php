<?php

namespace App\Http\Controllers\Stock;

use App\Helpers\BukuStockHelper;
use App\Helpers\CoilNoHelper;
use App\Helpers\DualQuantityHelper;
use App\Http\Controllers\Controller;
use App\Models\BukuStock;
use App\Models\DocPrint;
use App\Models\MsAutoNumber;
use App\Models\MsPart;
use App\Models\MsPartUnit;
use App\Models\TransJournalDT;
use App\Models\TransJournalHD;
use App\Models\TransPartUsageDT;
use App\Models\TransPartUsageHD;
use App\Services\FormatService;
use App\Services\GeneralService;
use App\Services\PartUsageJournalService;
use App\Services\WarehouseAccessCriteria;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class PartUsageController extends Controller
{
    private FormatService $formatService;
    private GeneralService $generalService;

    public function __construct(FormatService $formatService, GeneralService $generalService)
    {
        $this->formatService = $formatService;
        $this->generalService = $generalService;
    }

    public function index()
    {
        $this->guardAdvancedAccess();

        return view('stock.usage.index');
    }

    public function datatable(Request $request)
    {
        $this->guardAdvancedAccess();

        $data = TransPartUsageHD::with('division');
        WarehouseAccessCriteria::applyDetails($data);

        return DataTables::of($data)
            ->editColumn('TransactionDate', fn ($row) => Carbon::parse($row->TransactionDate)->format('Y-m-d'))
            ->editColumn('DivisionID', fn ($row) => optional($row->division)->DivisionName ?? $row->DivisionID)
            ->addColumn('action', function ($row) {
                $btn = '<div class="btn-group">';
                $btn .= '<a class="btn btn-sm btn-alt-secondary" title="Show" href="' . route('usage.show', ['id' => $row->id]) . '"><i class="fa fa-fw fa-eye"></i></a>';

                if ($row->Editable == 1) {
                    if (Auth::user()->hasAnyPermission(['admin', 'part_usage.edit'])) {
                        $btn .= '<a class="btn btn-sm btn-alt-secondary" title="Edit" href="' . route('usage.edit', ['id' => $row->id]) . '"><i class="fa fa-fw fa-edit"></i></a>';
                    }

                    if (Auth::user()->hasAnyPermission(['admin', 'part_usage.delete'])) {
                        $btn .= '<button class="btn btn-sm btn-alt-secondary delete-btn" title="Delete" data-url="' . route('usage.delete', ['id' => $row->id]) . '"><i class="fa fa-fw fa-trash"></i></button>';
                    }
                }

                return $btn . '</div>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function show($id)
    {
        $this->guardAdvancedAccess();

        $data = TransPartUsageHD::with('details.part', 'details.unit', 'details.warehouse', 'division')->where('id', $id)->firstOrFail();
        $data->TransactionDateFormatted = Carbon::parse($data->TransactionDate)->format('Y-m-d');
        $data->ExpiredDateFormatted = $data->ExpiredDate ? Carbon::parse($data->ExpiredDate)->format('Y-m-d') : '';

        foreach ($data->details as $detail) {
            $stockRow = $this->bukuStockRowForDetail($data->TransactionNo, $detail);
            $detail->QtyFormatted = $this->formatService->formatPrice($detail->Qty);
            $detail->Qty2Formatted = $stockRow && $stockRow->Qty2 !== null ? $this->formatService->formatPrice(abs((float) $stockRow->Qty2)) : null;
            $detail->UnitID2 = $stockRow->UnitID2 ?? null;
            $detail->ExpDateFormatted = $detail->ExpDate ? Carbon::parse($detail->ExpDate)->format('Y-m-d') : '';
            $detail->CoilNo = $detail->BatchNo ? CoilNoHelper::get($detail->PartID, $detail->BatchNo) : null;
        }

        $options = DocPrint::where('ModuleCode', 'PU')
            ->where('TypeStr', 'print')
            ->get();

        return view('stock.usage.show', compact('data', 'options'));
    }

    public function add()
    {
        $this->guardAdvancedAccess();

        return view('stock.usage.add', [
            'details' => [],
        ]);
    }

    public function edit($id)
    {
        $this->guardAdvancedAccess();

        $usage = TransPartUsageHD::with('details.part', 'details.warehouse')->where('id', $id)->firstOrFail();

        return view('stock.usage.edit', [
            'usage' => $usage,
            'details' => $this->buildDetailPayload($usage),
        ]);
    }

    public function store(Request $request)
    {
        $this->guardAdvancedAccess();
        $this->validateRequest($request, true);

        DB::beginTransaction();
        try {
            $id = $this->resolveTransactionNo($request);
            $user = Auth::user();

            TransPartUsageHD::create([
                'TransactionNo' => $id,
                'TransactionDate' => $this->generalService->formatDate($request->input('TransactionDate')),
                'ExpiredDate' => $this->generalService->nullableDateValue($request->input('ExpiredDate')),
                'WONumber' => $request->input('WONumber'),
                'DivisionID' => $request->input('DivisionID'),
                'Notes' => $request->input('Notes') ?? '-',
                'CreatedBy' => $user->UserID,
                'EntryTime' => now(),
                'LastUpdateBy' => $user->UserID,
                'LastUpdate' => now(),
                'Editable' => 1,
                'IsAuto' => $request->input('automatic') ?? 0,
                'LastDigit' => $request->input('_last_digit'),
            ]);

            $this->insertUsageRows($request, $id);
            PartUsageJournalService::rebuild($id);

            DB::commit();
            clear_form_preservation('stock_usage_advanced_add');

            return redirect()->route('usage')->with('success', 'Part usage successfully added!');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed Insert Advanced Part Usage: ' . $e->getMessage());

            return redirect()->back()->withInput()->withErrors(['msg' => $e->getMessage()]);
        }
    }

    public function update(Request $request)
    {
        $this->guardAdvancedAccess();
        $this->validateRequest($request, false);

        DB::beginTransaction();
        try {
            $header = TransPartUsageHD::with('details')->where('TransactionNo', $request->input('TransactionNo'))->firstOrFail();
            $user = Auth::user();
            $serialStockDeltas = $this->serialStockDeltasFromExistingDetails($header);

            $header->update([
                'TransactionDate' => $this->generalService->formatDate($request->input('TransactionDate')),
                'ExpiredDate' => $this->generalService->nullableDateValue($request->input('ExpiredDate')),
                'WONumber' => $request->input('WONumber'),
                'DivisionID' => $request->input('DivisionID'),
                'Notes' => $request->input('Notes') ?? '-',
                'LastUpdateBy' => $user->UserID,
                'LastUpdate' => now(),
            ]);

            BukuStock::where('TransactionNo', $header->TransactionNo)->delete();
            BukuStockHelper::validateSerialStockDoesNotExceedOne($serialStockDeltas, false);
            TransPartUsageDT::where('TransactionNo', $header->TransactionNo)->delete();
            $this->insertUsageRows($request, $header->TransactionNo);
            PartUsageJournalService::rebuild($header->TransactionNo);

            DB::commit();

            return redirect()->route('usage')->with('success', 'Part usage successfully updated!');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed Update Advanced Part Usage: ' . $e->getMessage());

            return redirect()->back()->withInput()->withErrors(['msg' => $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        $this->guardAdvancedAccess();

        try {
            DB::transaction(function () use ($id) {
                $usage = TransPartUsageHD::with('details')->where('id', $id)->first();
                if (!$usage) {
                    throw new Exception('Data not found');
                }

                BukuStock::where('TransactionNo', $usage->TransactionNo)->delete();
                BukuStockHelper::validateSerialStockDoesNotExceedOne($this->serialStockDeltasFromExistingDetails($usage), false);
                TransJournalDT::where('TransactionNo', $usage->TransactionNo)->delete();
                TransJournalHD::where('TransactionNo', $usage->TransactionNo)->delete();
                $usage->details()->delete();
                $usage->delete();
            });

            return response()->json(['status' => 'success']);
        } catch (Exception $e) {
            Log::error($e);

            return response()->json([
                'status' => 'error',
                'message' => 'Delete failed! Make sure it is not used in any other data!',
            ]);
        }
    }

    private function validateRequest(Request $request, bool $create): void
    {
        $rules = [
            'TransactionDate' => 'required|date_format:d/m/Y',
            'ExpiredDate' => 'nullable|date_format:d/m/Y',
            'DivisionID' => 'required|string',
            'Notes' => 'nullable|string',
            'DetailsJson' => 'required|string',
        ];

        if ($create) {
            $rules['TransactionNo'] = 'required_without:automatic|string|max:50|unique:Trans_PartUsageHD,TransactionNo';
        } else {
            $rules['TransactionNo'] = 'required|string|max:50';
        }

        $request->validate($rules);

        $details = $this->decodeDetails($request);
        if (count($details) === 0) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'DetailsJson' => 'You need to use at least one item!',
            ]);
        }
    }

    private function insertUsageRows(Request $request, string $transactionNo): void
    {
        foreach ($this->decodeDetails($request) as $detail) {
            $partId = $this->generalService->nullableDetailValue($detail['PartID'] ?? null);
            $warehouseId = $this->generalService->nullableDetailValue($detail['WarehouseID'] ?? null);
            $usedQty = (float) ($detail['Qty'] ?? 0);
            $inputQty = (float) ($detail['InputQty'] ?? $usedQty);
            $unit1 = $this->generalService->nullableDetailValue($detail['UnitID'] ?? null);
            $unit2 = $this->generalService->nullableDetailValue($detail['UnitID2'] ?? ($detail['InputUnit'] ?? $unit1));

            if (!$partId || !$warehouseId || $usedQty <= 0 || !$unit1) {
                throw new Exception('Part, warehouse, unit, and qty are required for every detail.');
            }

            $part = MsPart::where('PartID', $partId)->first();
            if (!$part) {
                throw new Exception("Part {$partId} does not exist.");
            }

            $withSerialNo = (int) ($part->WithSerialNo ?? 0) === 1;
            $stockFilters = $this->submittedStockFilters($detail, $withSerialNo);
            $stockAttributes = $this->stockFiltersForInsert($stockFilters);
            $stockQty = abs((float) ($detail['StockQty'] ?? 0));
            $stockQty2 = abs((float) ($detail['StockQty2'] ?? 0));
            $inputUnit = $this->generalService->nullableDetailValue($detail['InputUnit'] ?? null);

            if ($inputUnit && $unit2 && (string) $inputUnit === (string) $unit2) {
                $sourceQty = $inputQty;
            } elseif ($stockQty > 0.000001 && $stockQty2 > 0.000001) {
                $sourceQty = $usedQty / $stockQty * $stockQty2;
            } else {
                $sourceQty = $inputQty;
            }

            $availableStock = BukuStockHelper::calculateCurrentStock(
                $partId,
                $warehouseId,
                $stockFilters['BatchNo'],
                $stockFilters['SerialNo'],
                $stockFilters['ExpDate'],
                $stockFilters['BIN'],
                $stockFilters['LOC']
            );

            if ($usedQty > $availableStock) {
                throw new Exception($this->stockNotEnoughMessage($partId, $warehouseId, $stockAttributes));
            }

            TransPartUsageDT::insert([
                'TransactionNo' => $transactionNo,
                'PartID' => $partId,
                'UnitID' => $unit1,
                'WarehouseID' => $warehouseId,
                'Qty' => $usedQty,
                'BatchNo' => $stockAttributes['BatchNo'],
                'SerialNo' => $stockAttributes['SerialNo'],
                'ExpDate' => $stockAttributes['ExpDate'],
                'BIN' => $stockAttributes['BIN'],
                'LOC' => $stockAttributes['LOC'],
                'Notes' => $detail['Notes'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            BukuStock::insert([
                'TransactionNo' => $transactionNo,
                'TransactionDate' => $this->generalService->formatDate($request->input('TransactionDate')),
                'PartID' => $partId,
                'WarehouseID' => $warehouseId,
                'Sequence' => null,
                'UnitID' => $unit1,
                'Qty' => -$usedQty,
                'Qty2' => -$sourceQty,
                'UnitID2' => $unit2,
                'BatchNo' => $stockAttributes['BatchNo'],
                'SerialNo' => $stockAttributes['SerialNo'],
                'ExpDate' => $stockAttributes['ExpDate'],
                'BIN' => $stockAttributes['BIN'],
                'LOC' => $stockAttributes['LOC'],
                'TransactionType' => 'PART_USAGE',
                'CreatedBy' => Auth::user()->UserID,
                'EntryTime' => date('Y-m-d H:i:s'),
                'Notes' => $detail['Notes'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
    private function bukuStockRowForDetail(string $transactionNo, $detail): ?BukuStock
    {
        $query = BukuStock::where('TransactionNo', $transactionNo)
            ->where('TransactionType', 'PART_USAGE')
            ->where('PartID', $detail->PartID)
            ->where('WarehouseID', $detail->WarehouseID)
            ->where('Qty', '<', 0);

        $this->applyNullableBukuStockFilter($query, 'BatchNo', $detail->BatchNo);
        $this->applyNullableBukuStockFilter($query, 'SerialNo', $detail->SerialNo);
        $this->applyNullableBukuStockFilter($query, 'ExpDate', $detail->ExpDate);
        $this->applyNullableBukuStockFilter($query, 'BIN', $detail->BIN);
        $this->applyNullableBukuStockFilter($query, 'LOC', $detail->LOC);

        return $query->first();
    }

    private function applyNullableBukuStockFilter($query, string $column, $value): void
    {
        if ($value === null || trim((string) $value) === '') {
            $query->whereNull($column);
            return;
        }

        $query->where($column, $value);
    }

    private function decodeDetails(Request $request): array
    {
        $details = json_decode($request->input('DetailsJson', '[]'), true);

        return is_array($details) ? array_values($details) : [];
    }

    private function serialStockDeltasFromExistingDetails(TransPartUsageHD $usage): array
    {
        $serialStockDeltas = [];
        foreach ($usage->details as $detail) {
            $serialNo = $this->generalService->nullableDetailValue($detail->SerialNo);
            if ($serialNo === null) {
                continue;
            }

            $serialStockDeltas[] = [
                'warehouse_id' => $detail->WarehouseID,
                'part_id' => $detail->PartID,
                'serial_no' => $serialNo,
                'delta' => 0,
            ];
        }

        return $serialStockDeltas;
    }

    private function submittedStockFilters(array $detail, bool $withSerialNo): array
    {
        return [
            'BatchNo' => $this->submittedStockValue($detail['BatchNo'] ?? null),
            'SerialNo' => null,
            'ExpDate' => null,
            'BIN' => null,
            'LOC' => null,
        ];
    }

    private function submittedStockValue($value): ?string
    {
        $value = $this->generalService->nullableDetailValue($value);

        return $value === null ? '__NULL__' : $value;
    }

    private function submittedDateValue($value): ?string
    {
        $value = $this->generalService->nullableDetailValue($value);
        if ($value === null || $value === '__NULL__') {
            return '__NULL__';
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        return $this->generalService->nullableDateValue($value);
    }

    private function stockFiltersForInsert(array $stockFilters): array
    {
        $attributes = [];
        foreach ($stockFilters as $key => $value) {
            $attributes[$key] = $value === '__NULL__' ? null : $value;
        }

        return $attributes;
    }

    private function stockNotEnoughMessage(string $partId, string $warehouseId, array $stockAttributes): string
    {
        $details = [
            'Part' => $partId,
            'Warehouse' => $warehouseId,
            'Batch No' => $stockAttributes['BatchNo'] ?? null,
            'Serial No' => $stockAttributes['SerialNo'] ?? null,
            'Exp Date' => $stockAttributes['ExpDate'] ?? null,
            'BIN' => $stockAttributes['BIN'] ?? null,
            'LOC' => $stockAttributes['LOC'] ?? null,
        ];

        $detailText = collect($details)
            ->map(fn ($value, $label) => $label . ': ' . ($value === null || $value === '' ? '(Empty)' : $value))
            ->implode(', ');

        return 'Stock is not enough for selected stock details. ' . $detailText . '.';
    }

    private function buildDetailPayload(TransPartUsageHD $usage): array
    {
        return $usage->details->map(function ($detail) use ($usage) {
            $stockRow = $this->bukuStockRowForDetail($usage->TransactionNo, $detail);
            $actualQty = abs((float) ($stockRow->Qty ?? $detail->Qty ?? 0));
            $qty2 = abs((float) ($stockRow->Qty2 ?? 0));
            $unit1 = $stockRow->UnitID ?? $detail->UnitID;
            $unit2 = $stockRow->UnitID2 ?? $detail->UnitID;
            $selectedUnit = $detail->UnitID;
            $inputQty = $actualQty;
            $conversion = 1;

            if ($selectedUnit && $unit2 && (string) $selectedUnit === (string) $unit2 && $qty2 > 0.000001) {
                $inputQty = $qty2;
                $conversion = $actualQty / $qty2;
            }

            $secondaryStock = [
                'qty1' => $actualQty,
                'qty2' => $qty2 > 0.000001 ? $qty2 : null,
                'unit_id2' => $unit2,
            ];

            return [
                'PartID' => $detail->PartID,
                'PartName' => $detail->part->PartName ?? '',
                'WithSerialNo' => (int) ($detail->part->WithSerialNo ?? 0),
                'UnitID' => $unit1,
                'Conversion' => $conversion,
                'Qty' => $actualQty,
                'InputQty' => $inputQty,
                'InputUnit' => $selectedUnit,
                'WarehouseID' => $detail->WarehouseID,
                'WarehouseName' => $detail->warehouse->WarehouseName ?? $detail->WarehouseID,
                'StockQty' => $secondaryStock['qty1'] !== null ? (float) $secondaryStock['qty1'] : null,
                'StockQty2' => $secondaryStock['qty2'] !== null ? (float) $secondaryStock['qty2'] : null,
                'UnitID2' => $secondaryStock['unit_id2'],
                'BatchNo' => $detail->BatchNo,
                'CoilNo' => $detail->BatchNo ? CoilNoHelper::get($detail->PartID, $detail->BatchNo) : null,
                'SerialNo' => $detail->SerialNo,
                'ExpDate' => $detail->ExpDate ? Carbon::parse($detail->ExpDate)->format('Y-m-d') : null,
                'BIN' => $detail->BIN,
                'LOC' => $detail->LOC,
                'Notes' => $detail->Notes,
            ];
        })->values()->all();
    }
    private function resolveTransactionNo(Request $request): string
    {
        if (!$request->input('automatic')) {
            $request->merge(['_last_digit' => null]);
            return trim($request->input('TransactionNo'));
        }

        $masterAuto = MsAutoNumber::find('1');
        $digit = 1;
        $checkLast = TransPartUsageHD::where('IsAuto', 1)
            ->whereMonth('TransactionDate', \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('m'))
            ->whereYear('TransactionDate', \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('Y'))
            ->orderBy('LastDigit', 'desc')
            ->first();

        if ($checkLast) {
            $digit = $checkLast->LastDigit + 1;
        }

        do {
            $id = $masterAuto->PartUsage . '/' . \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('Y')
                . '/' . \DateTime::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('m')
                . '/' . str_pad($digit, 4, '0', STR_PAD_LEFT);

            $exists = TransPartUsageHD::where('TransactionNo', $id)->exists();
            if ($exists) {
                $digit++;
            }
        } while ($exists);

        $request->merge(['_last_digit' => $digit]);

        return $id;
    }

    private function guardAdvancedAccess(): void
    {
    }
}
