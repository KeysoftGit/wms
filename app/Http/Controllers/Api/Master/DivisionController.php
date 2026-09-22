<?php

namespace App\Http\Controllers\Api\Master;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\GetDivisionRequest;
use App\Models\ControlPanel;
use App\Models\MsDivision;
use App\Models\MsWarehouse;
use App\Models\TransUserWarehouseDT;
use App\Models\TransUserWarehouseHD;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DivisionController extends Controller
{
    protected array $columns = ['DivisionID', 'DivisionName', 'Active'];

    public function getDivision(GetDivisionRequest $request): JsonResponse
    {
        try {
            $divisions = $this->buildQuery($request)
                ->limit(10)
                ->get($this->columns);

            $result = [
                'divisions' => $divisions
            ];

            return ResponseFormatter::success($result, 'Divisions fetched successfully')->toResponse();
        } catch (\Exception $e) {
            return ResponseFormatter::error($e->getMessage())->toResponse();
        }
    }

    public function getDivisionPaginated(GetDivisionRequest $request): JsonResponse
    {
        try {
            $page = (int) ($request->page ?? 1);
            $perPage = (int) ($request->per_page ?? 10);

            $paginator = $this->buildQuery($request)
                ->paginate($perPage, $this->columns, 'page', $page);

            $result = [
                'divisions' => $paginator->items(),
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                    'has_more' => $paginator->hasMorePages(),
                ],
            ];

            return ResponseFormatter::success($result, 'Divisions fetched successfully')->toResponse();
        } catch (\Exception $e) {
            return ResponseFormatter::error($e->getMessage())->toResponse();
        }
    }

    protected function buildQuery(GetDivisionRequest $request): Builder
    {
        $term = $request->term;
        $isActive = $request->is_active;

        $query = MsDivision::query()
            ->where('Active', $isActive);

        if (ControlPanel::isEnabled('implement_user_warehouse_mapping')) {
            $userId = Auth::user()->UserID;
            $allowedDivisionIds = [];

            // 1. Get the latest User Warehouse Authorization Header
            $hd = TransUserWarehouseHD::where('UserID', $userId)
                ->orderBy('EntryTime', 'desc')
                ->first();

            if ($hd) {
                // 2. Check Effective Date
                $effectiveDate = Carbon::parse($hd->EffectiveDate);

                if (Carbon::now()->gte($effectiveDate)) {
                    // 3. Get Division IDs from authorized warehouses
                    $allowedWarehouseIds = TransUserWarehouseDT::where('UserID', $userId)
                        ->pluck('WarehouseID')
                        ->toArray();

                    if (!empty($allowedWarehouseIds)) {
                        $allowedDivisionIds = MsWarehouse::whereIn('WarehouseID', $allowedWarehouseIds)
                            ->whereNotNull('DivisionID')
                            ->distinct()
                            ->pluck('DivisionID')
                            ->toArray();
                    }
                }
            }

            $query->whereIn('DivisionID', $allowedDivisionIds);
        }

        return $query->when($term, function ($query, $term) {
            $query->where(function ($q) use ($term) {
                $q->where('DivisionID', 'like', "%{$term}%")
                    ->orWhere('DivisionName', 'like', "%{$term}%");
            });
        });
    }
}
