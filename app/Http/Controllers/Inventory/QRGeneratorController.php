<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\MsPartUnit;
use App\Models\MsQR;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class QRGeneratorController extends Controller
{
    public function index()
    {
        return view('inventory.qr_generator.index');
    }

    public function datatable()
    {
        $data = MsQR::query()
            ->select('id', 'code', 'json_display', 'show_content', 'created_at')
            ->whereRaw("JSON_VALUE(json_value, '$.data.PartID') IS NOT NULL");

        return DataTables::of($data)
            ->addColumn('summary', function ($row) {
                $data = $row->json_display['data'] ?? [];

                return collect($data)
                    ->filter(fn ($value) => $value !== null && $value !== '')
                    ->map(fn ($value, $key) => e($key . ': ' . $value))
                    ->implode('<br>');
            })
            ->addColumn('action', function ($row) {
                $btn = '<div class="btn-group">';
                $btn .= '<a class="btn btn-sm btn-alt-secondary" data-bs-toggle="tooltip" title="Show" href="' . route('inventory.qr_generator.show', $row->id) . '"><i class="fa fa-fw fa-eye"></i></a>';
                $btn .= '<a class="btn btn-sm btn-alt-secondary" data-bs-toggle="tooltip" title="Edit" href="' . route('inventory.qr_generator.edit', $row->id) . '"><i class="fa fa-fw fa-edit"></i></a>';
                $btn .= '<button class="btn btn-sm btn-alt-secondary delete-btn" data-bs-toggle="tooltip" title="Delete" data-url="' . route('inventory.qr_generator.delete', $row->id) . '"><i class="fa fa-fw fa-trash"></i></button>';

                return $btn . '</div>';
            })
            ->rawColumns(['summary', 'action'])
            ->make(true);
    }

    public function add()
    {
        return view('inventory.qr_generator.add');
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $code = $request->boolean('automatic')
            ? $this->generateCode()
            : strtoupper(trim($request->input('code')));

        if (MsQR::where('code', $code)->exists()) {
            return redirect()->back()->withInput()->withErrors(['QR code already exists.']);
        }

        DB::beginTransaction();
        try {
            MsQR::create([
                'code' => $code,
                'json_value' => $this->buildValuePayload($code, $data),
                'json_display' => $this->buildDisplayPayload($code, $data, $request->boolean('show_content')),
                'show_content' => $request->boolean('show_content'),
            ]);

            DB::commit();

            return redirect()->route('inventory.qr_generator')->with([
                'type' => 'success',
                'icon' => 'fa fa-fw fa-circle-check',
                'message' => 'QR successfully added!',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);

            return redirect()->back()->withInput()->withErrors(['Something went wrong!']);
        }
    }

    public function show($id)
    {
        $qr = MsQR::where('id', $id)->firstOrFail();

        return view('inventory.qr_generator.show', compact('qr'));
    }

    public function edit($id)
    {
        $qr = MsQR::where('id', $id)->firstOrFail();

        return view('inventory.qr_generator.edit', compact('qr'));
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'id' => 'required',
            'code' => 'required|string|max:30|regex:/^[A-Za-z0-9_-]+$/',
        ]);

        $qr = MsQR::where('id', $request->id)->first();
        if (!$qr) {
            return redirect()->route('inventory.qr_generator')->withErrors(['QR does not exist.']);
        }

        $data = $this->validatedData($request);
        $code = strtoupper(trim($request->input('code')));

        if (MsQR::where('code', $code)->where('id', '!=', $qr->id)->exists()) {
            return redirect()->back()->withInput()->withErrors(['QR code already exists.']);
        }

        DB::beginTransaction();
        try {
            $qr->update([
                'code' => $code,
                'json_value' => $this->buildValuePayload($code, $data),
                'json_display' => $this->buildDisplayPayload($code, $data, $request->boolean('show_content')),
                'show_content' => $request->boolean('show_content'),
            ]);

            DB::commit();

            return redirect()->route('inventory.qr_generator')->with([
                'type' => 'success',
                'icon' => 'fa fa-fw fa-circle-check',
                'message' => 'QR successfully updated!',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);

            return redirect()->back()->withInput()->withErrors(['Something went wrong!']);
        }
    }

    public function destroy($id)
    {
        try {
            $qr = MsQR::where('id', $id)->first();
            if (!$qr) {
                return response(['status' => 'failed']);
            }

            $qr->delete();

            return response(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error($e);

            return response(['status' => 'failed']);
        }
    }

    private function validatedData(Request $request): array
    {
        $this->validate($request, [
            'code' => 'required_without:automatic|nullable|string|max:30|regex:/^[A-Za-z0-9_-]+$/',
            'part_id' => 'nullable|string',
            'unit_id' => 'nullable|string',
            'batch_no' => 'nullable|string',
            'show_content' => 'nullable|boolean',
        ]);

        $partId = $request->input('part_id');
        $unitId = $this->normalizeDetailValue($request->input('unit_id'));
        $conversion = $this->resolveConversion($partId, $unitId);

        $values = [
            'PartID' => $partId,
            'UnitID' => $unitId,
            'Conversion' => $conversion,
            'BatchNo' => $this->normalizeDetailValue($request->input('batch_no')),
        ];

        return collect($values)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->toArray();
    }

    private function buildValuePayload(string $code, array $data): array
    {
        return [
            'data' => array_merge(['Code' => $code], $data),
        ];
    }

    private function buildDisplayPayload(string $code, array $data, bool $showContent): array
    {
        return [
            'data' => $showContent
                ? array_merge(['Code' => $code], $data)
                : ['Code' => $code],
        ];
    }

    private function normalizeDetailValue($value)
    {
        return $value === '__NULL__' ? null : $value;
    }

    private function resolveConversion(?string $partId, ?string $unitId): ?float
    {
        if (!$partId || !$unitId) {
            return null;
        }

        $conversion = MsPartUnit::where('PartID', $partId)->where('UnitID2', $unitId)->value('Conversion');

        return $conversion !== null ? (float) $conversion : null;
    }

    private function generateCode(): string
    {
        do {
            $code = strtoupper(Str::random(10));
        } while (MsQR::where('code', $code)->exists());

        return $code;
    }
}
