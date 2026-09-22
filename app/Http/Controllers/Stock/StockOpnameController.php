<?php

namespace App\Http\Controllers\Stock;

use App\Helpers\CoilNoHelper;
use App\Http\Controllers\Controller;
use App\Imports\ValidateImport;
use App\Models\BukuStock;
use App\Models\DocPrint;
use App\Models\MsAutoNumber;
use App\Models\MsPart;
use App\Models\MsPartUnit;
use App\Models\MsWarehouse;
use App\Models\TransStockOpnameChecker;
use App\Models\TransStockOpnameDT;
use App\Models\TransStockOpnameHD;
use App\Services\FormatService;
use App\Services\GeneralService;
use App\Services\WarehouseAccessCriteria;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Yajra\DataTables\Facades\DataTables;

class StockOpnameController extends Controller
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

        return view('stock.opname.index');
    }

    public function datatable(Request $request)
    {
        $this->guardAdvancedAccess();

        $data = TransStockOpnameHD::select('id', 'TransactionNo', 'TransactionDate', 'WarehouseID', 'Status', 'Notes', 'Editable')->with('warehouse');
        WarehouseAccessCriteria::apply($data);

        if ($request->get('date')) {
            $dates = explode(' to ', $request->get('date'));
            if (count($dates) > 1) {
                $data->whereDate('TransactionDate', '>=', Carbon::createFromFormat('d/m/Y', $dates[0])->format('Y-m-d'));
                $data->whereDate('TransactionDate', '<=', Carbon::createFromFormat('d/m/Y', $dates[1])->format('Y-m-d'));
            } else {
                $data->whereDate('TransactionDate', Carbon::createFromFormat('d/m/Y', $dates[0])->format('Y-m-d'));
            }
        }

        return DataTables::of($data)
            ->editColumn('TransactionDate', fn ($row) => Carbon::parse($row->TransactionDate)->format('Y-m-d'))
            ->editColumn('WarehouseID', fn ($row) => optional($row->warehouse)->WarehouseName ?? $row->WarehouseID)
            ->addColumn('action', function ($row) {
                $btn = '<div class="btn-group">';
                $btn .= '<a class="btn btn-sm btn-alt-secondary" title="Show" href="' . route('opname.show', ['id' => $row->id]) . '"><i class="fa fa-fw fa-eye"></i></a>';

                if ($row->Editable == 1) {
                    if (Auth::user()->hasAnyPermission(['admin', 'opname.edit'])) {
                        $btn .= '<a class="btn btn-sm btn-alt-secondary" title="Edit" href="' . route('opname.edit', ['id' => $row->id]) . '"><i class="fa fa-fw fa-edit"></i></a>';
                    }

                    if (Auth::user()->hasAnyPermission(['admin', 'opname.delete'])) {
                        $btn .= '<button class="btn btn-sm btn-alt-secondary delete-btn" title="Delete" data-url="' . route('opname.delete', ['id' => $row->id]) . '"><i class="fa fa-fw fa-trash"></i></button>';
                    }
                }

                return $btn . '</div>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function add()
    {
        $this->guardAdvancedAccess();

        return view('stock.opname.add', [
            'details' => [],
            'checkers' => [],
        ]);
    }

    public function edit($id)
    {
        $this->guardAdvancedAccess();

        $opname = TransStockOpnameHD::with('details.part', 'checkers')->where('id', $id)->firstOrFail();

        return view('stock.opname.edit', [
            'opname' => $opname,
            'details' => $this->buildDetailPayload($opname),
            'checkers' => $this->buildCheckerPayload($opname),
        ]);
    }

    public function show($id)
    {
        $this->guardAdvancedAccess();

        $opname = TransStockOpnameHD::with('details.part', 'details.unit', 'checkers.employee', 'warehouse')->where('id', $id)->firstOrFail();
        foreach ($opname->details as $detail) {
            $detail->QtyStockFormatted = $this->formatService->formatPrice($detail->QtyStock);
            $detail->QtyOpnameFormatted = $this->formatService->formatPrice($detail->QtyOpname);
            $detail->DifferenceFormatted = $this->formatService->formatPrice($detail->QtyOpname - $detail->QtyStock);
            $detail->ExpDateFormatted = $detail->ExpDate ? Carbon::parse($detail->ExpDate)->format('Y-m-d') : '';
            $detail->CoilNo = $detail->BatchNo ? CoilNoHelper::get($detail->PartID, $detail->BatchNo) : null;
        }

        $options = DocPrint::where('ModuleCode', 'STOCKOPNAME')
            ->where('TypeStr', 'print')
            ->get();

        return view('stock.opname.show', compact('opname', 'options'));
    }

    public function getStock(Request $request)
    {
        $this->guardAdvancedAccess();
        $warehouseId = $request->get('id');
        if (!$warehouseId) {
            return response(['stock' => []]);
        }

        $rows = BukuStock::select(
                'PartID',
                'WarehouseID',
                'BatchNo',
                DB::raw('SUM(Qty) as total_qty')
            )
            ->where('WarehouseID', $warehouseId)
            ->groupBy('PartID', 'WarehouseID', 'BatchNo')
            ->orderBy('PartID')
            ->get();

        $parts = $this->partsByIds($rows->pluck('PartID')->unique()->values()->all());
        $unitCache = [];

        $data = [];
        foreach ($rows as $row) {
            $part = $parts->get($row->PartID);
            $unitCache[$row->PartID] = $unitCache[$row->PartID] ?? $this->lowestUnit($row->PartID);

            $data[] = [
                'id' => generateRandomString(10),
                'part' => $row->PartID,
                'part_name' => $row->PartID . ($part && $part->PartName ? ' - ' . $part->PartName : ''),
                'with_serial_no' => 0,
                'unit' => $unitCache[$row->PartID],
                'stock' => (float) $row->total_qty,
                'opname' => (float) $row->total_qty,
                'batch_no' => $row->BatchNo,
                'coil_no' => $row->BatchNo ? CoilNoHelper::get($row->PartID, $row->BatchNo) : null,
                'serial_no' => null,
                'exp_date' => null,
                'bin' => null,
                'loc' => null,
                'closed' => false,
            ];
        }

        return response(['stock' => $data]);
    }

    public function checkExcel(Request $request)
    {
        $this->guardAdvancedAccess();

        if (!$request->hasFile('excel')) {
            return response([
                'success' => false,
                'errors' => ['excel.required' => ['Pick a spreadsheet first!']],
            ]);
        }

        try {
            $sheets = Excel::toArray(new ValidateImport(), $request->file('excel'));
            $rows = array_values($sheets[0] ?? []);
            $rows = array_values(array_filter($rows, function ($row) {
                return collect($row)->filter(function ($value) {
                    return $value !== null && trim((string) $value) !== '';
                })->isNotEmpty();
            }));
        } catch (Exception $exception) {
            Log::error($exception);

            return response([
                'success' => false,
                'errors' => ['excel.invalid' => ['Unable to read the uploaded spreadsheet.']],
            ]);
        }

        if (count($rows) === 0) {
            return response([
                'success' => false,
                'errors' => ['excel.empty' => ['The uploaded spreadsheet does not contain any data rows.']],
            ]);
        }

        $normalizedRows = [];
        $messages = [];

        foreach ($rows as $i => $row) {
            $r = $i + 2;
            $normalizedRows[$i] = [
                'PartID' => $this->nullableImportValue($row['PartID'] ?? null),
                'UnitID' => $this->nullableImportValue($row['UnitID'] ?? null),
                'BatchNo' => $this->nullableImportValue($row['BatchNo'] ?? null),
                'SerialNo' => null,
                'ExpDate' => null,
                'BIN' => null,
                'LOC' => null,
                'QtyOpname' => $row['QtyOpname'] ?? null,
                'Closed' => strtoupper((string) ($row['Closed'] ?? '')),
                '_row' => $r,
            ];

            $messages["{$i}.PartID.required"] = "Row {$r} : Part ID is required!";
            $messages["{$i}.PartID.exists"] = "Row {$r} : Part ID doesn't exist!";
            $messages["{$i}.UnitID.required"] = "Row {$r} : Unit ID is required!";
            $messages["{$i}.QtyOpname.required"] = "Row {$r} : Qty Opname is required!";
            $messages["{$i}.QtyOpname.numeric"] = "Row {$r} : Qty Opname must be a number!";
            $messages["{$i}.QtyOpname.min"] = "Row {$r} : Qty Opname cannot be below 0!";
            $messages["{$i}.Closed.required"] = "Row {$r} : Closed is required!";
            $messages["{$i}.Closed.in"] = "Row {$r} : Closed must be either YES or NO!";
        }

        $validator = Validator::make($normalizedRows, [
            '*.PartID' => 'required|string|exists:Ms_Part,PartID',
            '*.UnitID' => 'required|string',
            '*.BatchNo' => 'nullable|string',
            '*.QtyOpname' => 'required|numeric|min:0',
            '*.Closed' => 'required|in:YES,NO',
        ], $messages);

        if ($validator->fails()) {
            return response([
                'success' => false,
                'errors' => $validator->messages()->toArray(),
            ]);
        }

        $parts = $this->partsByIds(collect($normalizedRows)->pluck('PartID')->unique()->values()->all());
        $errors = [];
        $seen = [];

        foreach ($normalizedRows as $i => $row) {
            $normalizedRows[$i]['SerialNo'] = null;
            $normalizedRows[$i]['ExpDate'] = null;
            $normalizedRows[$i]['BIN'] = null;
            $normalizedRows[$i]['LOC'] = null;

            $key = $this->identityKey(
                $row['PartID'],
                $row['UnitID'],
                $row['BatchNo'],
                null,
                null,
                null,
                null
            );

            if (isset($seen[$key])) {
                $errors["{$i}.Identity.duplicate"] = ["Row {$row['_row']} : Duplicate stock identity with row {$seen[$key]}!"];
                continue;
            }

            $seen[$key] = $row['_row'];
        }

        if (count($errors) > 0) {
            return response([
                'success' => false,
                'errors' => $errors,
            ]);
        }

        $details = TransStockOpnameDT::with('part')
            ->where('TransactionNo', $request->input('id'))
            ->get();

        $importByKey = [];
        foreach ($normalizedRows as $i => $row) {
            $importByKey[$this->identityKey(
                $row['PartID'],
                $row['UnitID'],
                $row['BatchNo'],
                null,
                null,
                null,
                null
            )] = $i;
        }

        $newDetails = [];
        foreach ($details as $detail) {
            $key = $this->identityKey(
                $detail->PartID,
                $detail->UnitID,
                $detail->BatchNo,
                null,
                null,
                null,
                null
            );

            $rowIndex = $importByKey[$key] ?? null;
            $row = $rowIndex !== null ? $normalizedRows[$rowIndex] : null;
            if ($rowIndex !== null) {
                $normalizedRows[$rowIndex]['_exist'] = true;
            }

            $newDetails[] = $this->buildImportDetailPayload(
                $detail->PartID,
                $detail->part,
                $detail->UnitID,
                (float) $detail->QtyStock,
                $row !== null ? (float) $row['QtyOpname'] : (float) $detail->QtyOpname,
                $detail->BatchNo,
                null,
                null,
                null,
                null,
                $row !== null ? $row['Closed'] === 'YES' : (bool) $detail->Closed
            );
        }

        foreach ($normalizedRows as $row) {
            if (!empty($row['_exist'])) {
                continue;
            }

            $errors["{$row['_row']}.Identity.not_found"] = ["Row {$row['_row']} : Stock identity does not exist in this opname. Download the latest advanced template and fill Qty Opname there."];
        }

        if (count($errors) > 0) {
            return response([
                'success' => false,
                'errors' => $errors,
            ]);
        }

        return response([
            'success' => true,
            'details' => $newDetails,
        ]);
    }

    public function downloadTemplate($id)
    {
        $this->guardAdvancedAccess();

        $opname = TransStockOpnameHD::with('details.part')
            ->where('id', $id)
            ->firstOrFail();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Opname');

        $headers = ['PartID', 'UnitID', 'BatchNo', 'QtyOpname', 'Closed'];
        foreach ($headers as $index => $header) {
            $column = chr(ord('A') + $index);
            $sheet->setCellValue("{$column}1", $header);
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $rowNumber = 2;
        foreach ($opname->details as $detail) {
            $sheet->setCellValueExplicit("A{$rowNumber}", $detail->PartID, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("B{$rowNumber}", $detail->UnitID, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("C{$rowNumber}", $detail->BatchNo ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue("D{$rowNumber}", null);
            $sheet->setCellValueExplicit("E{$rowNumber}", $detail->Closed ? 'YES' : 'NO', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $rowNumber++;
        }

        $filename = 'advanced_opname_template_' . str_replace(['/', '\\'], '_', $opname->TransactionNo) . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function store(Request $request)
    {
        $this->guardAdvancedAccess();
        $this->validateRequest($request, true);

        DB::beginTransaction();
        try {
            $warehouse = MsWarehouse::where('WarehouseID', $request->input('WarehouseID'))->first();
            if (!$warehouse) {
                throw new Exception('Warehouse does not exist.');
            }

            $transactionNo = $this->resolveTransactionNo($request);
            $user = Auth::user();

            TransStockOpnameHD::create([
                'TransactionNo' => $transactionNo,
                'TransactionDate' => $this->generalService->formatDate($request->input('TransactionDate')),
                'WarehouseID' => $request->input('WarehouseID'),
                'Notes' => $request->input('Notes') ? trim($request->input('Notes')) : null,
                'Status' => 'PENDING',
                'CreatedBy' => $user->UserID,
                'EntryTime' => date('Y-m-d H:i:s'),
                'LastUpdateBy' => $user->UserID,
                'LastUpdate' => date('Y-m-d H:i:s'),
                'IsAuto' => $request->input('automatic') ?? 0,
                'LastDigit' => $request->input('_last_digit'),
                'Editable' => true,
            ]);

            $this->insertDetails($request, $transactionNo);
            $this->insertCheckers($request, $transactionNo);

            DB::commit();
            clear_form_preservation('stock_opname_advanced_add');

            return redirect()->route('opname')->with([
                'type' => 'success',
                'icon' => 'fa fa-fw fa-circle-check',
                'message' => 'Advanced Stock Opname successfully added!',
            ]);
        } catch (Exception $exception) {
            DB::rollBack();
            Log::error($exception);

            return redirect()->back()->withInput()->withErrors([$exception->getMessage() ?: 'Something went wrong']);
        }
    }

    public function update(Request $request)
    {
        $this->guardAdvancedAccess();
        $this->validateRequest($request, false);

        DB::beginTransaction();
        try {
            $opname = TransStockOpnameHD::where('TransactionNo', $request->input('id'))->first();
            if (!$opname) {
                throw new Exception('Stock Opname does not exist.');
            }

            $opname->update([
                'ReasonsForDelays' => $request->input('ReasonsForDelays') ? trim($request->input('ReasonsForDelays')) : null,
                'Notes' => $request->input('Notes') ? trim($request->input('Notes')) : null,
                'Status' => $request->input('Status') ?: $opname->Status,
                'LastUpdateBy' => Auth::user()->UserID,
                'LastUpdate' => date('Y-m-d H:i:s'),
            ]);

            TransStockOpnameDT::where('TransactionNo', $opname->TransactionNo)->delete();
            TransStockOpnameChecker::where('TransactionNo', $opname->TransactionNo)->delete();

            $this->insertDetails($request, $opname->TransactionNo);
            $this->insertCheckers($request, $opname->TransactionNo);

            DB::commit();

            return redirect()->route('opname')->with([
                'type' => 'success',
                'icon' => 'fa fa-fw fa-circle-check',
                'message' => 'Advanced Stock Opname successfully updated!',
            ]);
        } catch (Exception $exception) {
            DB::rollBack();
            Log::error($exception);

            return redirect()->back()->withInput()->withErrors([$exception->getMessage() ?: 'Something went wrong']);
        }
    }

    public function destroy($id)
    {
        $this->guardAdvancedAccess();

        try {
            DB::transaction(function () use ($id) {
                $opname = TransStockOpnameHD::where('id', $id)->first();
                if (!$opname) {
                    throw new Exception('Stock Opname does not exist.');
                }

                TransStockOpnameChecker::where('TransactionNo', $opname->TransactionNo)->delete();
                TransStockOpnameDT::where('TransactionNo', $opname->TransactionNo)->delete();
                $opname->delete();
            });

            return response(['status' => 'success']);
        } catch (Exception $e) {
            Log::error($e);

            return response([
                'status' => 'failed',
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function validateRequest(Request $request, bool $create): void
    {
        $rules = [
            'TransactionDate' => 'required|date_format:d/m/Y',
            'WarehouseID' => 'required|string',
            'DetailsJson' => 'required|string',
            'employee' => 'required|array|min:1',
            'status' => 'required|array|min:1',
            'Notes' => 'nullable|string',
        ];

        if ($create) {
            $rules['TransactionNo'] = 'required_without:automatic|string|max:50|unique:Trans_StockOpnameHD,TransactionNo';
        } else {
            $rules['id'] = 'required|string';
            $rules['Status'] = 'required|string|in:PENDING,ADJUSTED,DONE';
            $rules['ReasonsForDelays'] = 'nullable|string';
        }

        $request->validate($rules, [
            'TransactionNo.unique' => 'Transaction No has already been taken!',
            'TransactionNo.max' => 'Transaction No maximum characters is 50!',
        ]);

        $details = $this->decodeDetails($request);
        if (count($details) === 0) {
            throw new Exception('You need to use at least one item!');
        }

        if (!in_array('SUPERVISOR', $request->input('status', []), true)) {
            throw new Exception('Need at least 1 checker as supervisor!');
        }
    }

    private function insertDetails(Request $request, string $transactionNo): void
    {
        $rows = [];
        foreach ($this->decodeDetails($request) as $detail) {
            $partId = $this->generalService->nullableDetailValue($detail['part'] ?? null);
            $unitId = $this->generalService->nullableDetailValue($detail['unit'] ?? null);
            $qtyStock = (float) ($detail['stock'] ?? 0);
            $qtyOpname = (float) ($detail['opname'] ?? 0);

            if (!$partId || !$unitId) {
                throw new Exception('Part and unit are required for every detail.');
            }

            if ($qtyOpname < 0) {
                throw new Exception("Qty Opname is invalid for Part {$partId}.");
            }

            $part = MsPart::where('PartID', $partId)->first();
            if (!$part) {
                throw new Exception("Part {$partId} does not exist.");
            }

            $rows[] = [
                'TransactionNo' => $transactionNo,
                'PartID' => $partId,
                'UnitID' => $unitId,
                'QtyStock' => $qtyStock,
                'QtyOpname' => $qtyOpname,
                'BatchNo' => $this->nullableDetailValue($detail['batch_no'] ?? null),
                'SerialNo' => null,
                'ExpDate' => null,
                'BIN' => null,
                'LOC' => null,
                'Notes' => $this->nullableDetailValue($detail['notes'] ?? null) ?? '',
                'Closed' => !empty($detail['closed']) ? 1 : 0,
            ];
        }

        $this->insertInChunks(TransStockOpnameDT::class, $rows);
    }

    private function insertCheckers(Request $request, string $transactionNo): void
    {
        $employees = $request->input('employee', []);
        $statuses = $request->input('status', []);
        $rows = [];

        foreach ($employees as $i => $employee) {
            $employee = $this->generalService->nullableDetailValue($employee);
            if (!$employee) {
                continue;
            }

            $rows[] = [
                'TransactionNo' => $transactionNo,
                'EmployeeID' => $employee,
                'Status' => $statuses[$i] ?? 'STAFF',
            ];
        }

        if (count($rows) === 0) {
            throw new Exception('You need to use at least one checker!');
        }

        $this->insertInChunks(TransStockOpnameChecker::class, $rows);
    }

    private function decodeDetails(Request $request): array
    {
        $details = json_decode($request->input('DetailsJson', '[]'), true);

        return is_array($details) ? array_values($details) : [];
    }

    private function buildDetailPayload(TransStockOpnameHD $opname): array
    {
        return $opname->details->map(function ($detail) {
            return [
                'id' => generateRandomString(10),
                'part' => $detail->PartID,
                'part_name' => $detail->PartID . ($detail->part && $detail->part->PartName ? ' - ' . $detail->part->PartName : ''),
                'with_serial_no' => 0,
                'unit' => $detail->UnitID,
                'stock' => (float) $detail->QtyStock,
                'opname' => (float) $detail->QtyOpname,
                'batch_no' => $detail->BatchNo,
                'coil_no' => $detail->BatchNo ? CoilNoHelper::get($detail->PartID, $detail->BatchNo) : null,
                'serial_no' => null,
                'exp_date' => null,
                'bin' => null,
                'loc' => null,
                'closed' => (bool) $detail->Closed,
            ];
        })->values()->all();
    }

    private function buildCheckerPayload(TransStockOpnameHD $opname): array
    {
        return $opname->checkers->map(function ($checker) {
            return [
                'id' => generateRandomString(10),
                'employee' => $checker->EmployeeID,
                'employee_text' => $checker->EmployeeID . ($checker->employee && $checker->employee->EmployeeName ? ' - ' . $checker->employee->EmployeeName : ''),
                'status' => $checker->Status,
            ];
        })->values()->all();
    }

    private function buildImportDetailPayload(
        string $partId,
        ?MsPart $part,
        ?string $unit,
        float $stock,
        float $opname,
        ?string $batchNo,
        ?string $serialNo,
        ?string $expDate,
        ?string $bin,
        ?string $loc,
        bool $closed
    ): array {
        return [
            'id' => generateRandomString(10),
            'part' => $partId,
            'part_name' => $partId . ($part && $part->PartName ? ' - ' . $part->PartName : ''),
            'with_serial_no' => 0,
            'unit' => $unit,
            'stock' => $stock,
            'opname' => $opname,
            'batch_no' => $batchNo,
            'coil_no' => $batchNo ? CoilNoHelper::get($partId, $batchNo) : null,
            'serial_no' => null,
            'exp_date' => null,
            'bin' => null,
            'loc' => null,
            'closed' => $closed,
        ];
    }

    private function resolveTransactionNo(Request $request): string
    {
        if (!$request->input('automatic')) {
            $request->merge(['_last_digit' => null]);
            return trim($request->input('TransactionNo'));
        }

        $masterAuto = MsAutoNumber::find('1');
        $digit = 1;
        $checkLast = TransStockOpnameHD::where('IsAuto', 1)
            ->whereMonth('TransactionDate', Carbon::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('m'))
            ->whereYear('TransactionDate', Carbon::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('Y'))
            ->orderBy('LastDigit', 'desc')
            ->first();

        if ($checkLast) {
            $digit = $checkLast->LastDigit + 1;
        }

        do {
            $id = $masterAuto->Inventory06 . '/' . Carbon::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('Y')
                . '/' . Carbon::createFromFormat('d/m/Y', $request->input('TransactionDate'))->format('m')
                . '/' . str_pad($digit, 4, '0', STR_PAD_LEFT);

            $exists = TransStockOpnameHD::where('TransactionNo', $id)->exists();
            if ($exists) {
                $digit++;
            }
        } while ($exists);

        $request->merge(['_last_digit' => $digit]);

        return $id;
    }

    private function lowestUnit(string $partId): ?string
    {
        $unit = MsPartUnit::where('PartID', $partId)
            ->where('Conversion', 1)
            ->orderBy('Sequence')
            ->first();

        if ($unit) {
            return $unit->UnitID2;
        }

        $fallback = MsPartUnit::where('PartID', $partId)
            ->orderBy('Sequence')
            ->first();

        return $fallback ? ($fallback->UnitID1 ?? $fallback->UnitID2) : null;
    }

    private function partsByIds(array $partIds)
    {
        $parts = collect();
        foreach (array_chunk($partIds, 1000) as $chunk) {
            MsPart::whereIn('PartID', $chunk)->get()->each(function ($part) use ($parts) {
                $parts->put($part->PartID, $part);
            });
        }

        return $parts;
    }

    private function nullableDetailValue($value): ?string
    {
        if ($value === '__NULL__') {
            return null;
        }

        return $this->generalService->nullableDetailValue($value);
    }

    private function nullableImportValue($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '' || strtoupper($value) === '(EMPTY)') {
            return null;
        }

        return $value;
    }

    private function nullableDateValue($value): ?string
    {
        $value = $this->nullableDetailValue($value);
        if ($value === null) {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        return $this->generalService->nullableDateValue($value);
    }

    private function normalizeImportDate($value): ?string
    {
        $value = $this->nullableImportValue($value);
        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->format('Y-m-d');
            } catch (Exception $exception) {
                return $value;
            }
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->format('Y-m-d');
            } catch (Exception $exception) {
                // Try the next supported format.
            }
        }

        return $value;
    }

    private function identityKey(
        ?string $partId,
        ?string $unitId,
        ?string $batchNo,
        ?string $serialNo,
        ?string $expDate,
        ?string $bin,
        ?string $loc
    ): string {
        return implode('|', [
            $partId ?? '',
            $unitId ?? '',
            $batchNo ?? '',
            $serialNo ?? '',
            $expDate ?? '',
            $bin ?? '',
            $loc ?? '',
        ]);
    }

    private function insertInChunks(string $modelClass, array $rows): void
    {
        foreach (array_chunk($rows, 10) as $chunk) {
            $modelClass::insert($chunk);
        }
    }

    private function guardAdvancedAccess(): void
    {
    }
}
