<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MsUser;
use App\Models\MsWarehouse;
use App\Models\TransUserWarehouseDT;
use App\Models\TransUserWarehouseHD;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class UserWarehouseMappingController extends Controller
{
    public function index()
    {
        $mappings = TransUserWarehouseHD::query()
            ->leftJoin('Ms_User', 'Ms_User.UserID', '=', 'Trans_UserWarehouseHD.UserID')
            ->select('Trans_UserWarehouseHD.*', 'Ms_User.UserName')
            ->orderByDesc('Trans_UserWarehouseHD.EffectiveDate')
            ->orderBy('Trans_UserWarehouseHD.UserID')
            ->paginate(20);

        $details = collect();
        if ($mappings->isNotEmpty()) {
            $details = TransUserWarehouseDT::query()
                ->leftJoin('Ms_Warehouse', 'Ms_Warehouse.WarehouseID', '=', 'Trans_UserWarehouseDT.WarehouseID')
                ->whereIn('Trans_UserWarehouseDT.UserID', $mappings->pluck('UserID'))
                ->select('Trans_UserWarehouseDT.*', 'Ms_Warehouse.WarehouseName')
                ->get()
                ->groupBy('UserID');
        }

        return view('admin.user_warehouse_mapping.index', compact('mappings', 'details'));
    }

    public function create()
    {
        $users = MsUser::where('Active', 1)
            ->where('isAdmin', 0)
            ->whereNotIn('UserID', TransUserWarehouseHD::select('UserID'))
            ->orderBy('UserID')
            ->get(['UserID', 'UserName']);
        $warehouses = MsWarehouse::where('Active', 1)
            ->orderBy('WarehouseID')
            ->get(['WarehouseID', 'WarehouseName']);

        return view('admin.user_warehouse_mapping.create', compact('users', 'warehouses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'UserID' => ['required', 'string', Rule::exists('sqlsrv.Ms_User', 'UserID')->where('Active', 1)],
            'EffectiveDate' => ['required', 'date'],
            'WarehouseID' => ['required', 'array', 'min:1'],
            'WarehouseID.*' => ['required', 'string', 'distinct', Rule::exists('sqlsrv.Ms_Warehouse', 'WarehouseID')->where('Active', 1)],
            'Notes' => ['nullable', 'string'],
        ]);

        $effectiveDate = \Carbon\Carbon::parse($validated['EffectiveDate'])->format('Y-m-d H:i:s');
        $exists = TransUserWarehouseHD::where('UserID', $validated['UserID'])->exists();

        if ($exists) {
            return back()->withInput()->withErrors([
                'UserID' => 'This user already has a mapping. Use the edit action to change warehouse access.',
            ]);
        }

        try {
            DB::connection('sqlsrv')->transaction(function () use ($validated, $effectiveDate) {
                $now = now()->format('Y-m-d H:i:s');
                $actor = Auth::user()->UserID;

                TransUserWarehouseHD::create([
                    'UserID' => $validated['UserID'],
                    'EffectiveDate' => $effectiveDate,
                    'Notes' => $validated['Notes'] ?? null,
                    'CreatedBy' => $actor,
                    'EntryTime' => $now,
                    'LastUpdateBy' => $actor,
                    'LastUpdate' => $now,
                ]);

                foreach ($validated['WarehouseID'] as $warehouseID) {
                    TransUserWarehouseDT::create([
                        'UserID' => $validated['UserID'],
                        'WarehouseID' => $warehouseID,
                        'Notes' => null,
                    ]);
                }
            });
        } catch (\Throwable $exception) {
            Log::error($exception);

            return back()->withInput()->withErrors([
                'Unable to save the mapping.',
            ]);
        }

        return redirect()->route('user_warehouse_mapping.index')->with([
            'type' => 'success',
            'icon' => 'fa fa-fw fa-circle-check',
            'message' => 'User warehouse mapping successfully added.',
        ]);
    }

    public function edit(string $userId)
    {
        $mapping = TransUserWarehouseHD::where('UserID', $userId)->firstOrFail();
        $selectedWarehouseIds = TransUserWarehouseDT::where('UserID', $userId)
            ->pluck('WarehouseID')
            ->toArray();
        $warehouses = MsWarehouse::where('Active', 1)
            ->orderBy('WarehouseID')
            ->get(['WarehouseID', 'WarehouseName']);

        return view('admin.user_warehouse_mapping.edit', compact(
            'mapping',
            'selectedWarehouseIds',
            'warehouses'
        ));
    }

    public function update(Request $request, string $userId)
    {
        $validated = $request->validate([
            'WarehouseID' => ['required', 'array', 'min:1'],
            'WarehouseID.*' => ['required', 'string', 'distinct', Rule::exists('sqlsrv.Ms_Warehouse', 'WarehouseID')->where('Active', 1)],
            'Notes' => ['nullable', 'string'],
        ]);

        $mapping = TransUserWarehouseHD::where('UserID', $userId)->firstOrFail();

        try {
            DB::connection('sqlsrv')->transaction(function () use ($mapping, $validated, $userId) {
                $mapping->update([
                    'Notes' => $validated['Notes'] ?? null,
                    'LastUpdateBy' => Auth::user()->UserID,
                    'LastUpdate' => now()->format('Y-m-d H:i:s'),
                ]);

                TransUserWarehouseDT::where('UserID', $userId)->delete();

                foreach ($validated['WarehouseID'] as $warehouseID) {
                    TransUserWarehouseDT::create([
                        'UserID' => $userId,
                        'WarehouseID' => $warehouseID,
                        'Notes' => null,
                    ]);
                }
            });
        } catch (\Throwable $exception) {
            Log::error($exception);

            return back()->withInput()->withErrors(['Unable to update the mapping.']);
        }

        return redirect()->route('user_warehouse_mapping.index')->with([
            'type' => 'success',
            'icon' => 'fa fa-fw fa-circle-check',
            'message' => 'User warehouse mapping successfully updated.',
        ]);
    }
}
