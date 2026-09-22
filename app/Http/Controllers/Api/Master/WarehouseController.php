<?php

namespace App\Http\Controllers\Api\Master;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\GetWarehouseRequest;
use App\Models\MsWarehouse;
use App\Services\WarehouseAccessCriteria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class WarehouseController extends Controller
{
    protected array $columns = ['WarehouseID', 'WarehouseName', 'ParentID', 'DivisionID', 'Active', 'StaffInChargeID'];

    public function getWarehouse(GetWarehouseRequest $request): JsonResponse
    {
        try {
            $warehouses = $this->buildQuery($request)
                ->limit(10)
                ->get($this->columns);

            $result = [
                'warehouses' => $warehouses
            ];

            return ResponseFormatter::success($result, 'Warehouses fetched successfully')->toResponse();
        } catch (\Exception $e) {
            return ResponseFormatter::error($e->getMessage())->toResponse();
        }
    }

    public function getWarehousePaginated(GetWarehouseRequest $request): JsonResponse
    {
        try {
            $page = (int) ($request->page ?? 1);
            $perPage = (int) ($request->per_page ?? 10);

            $paginator = $this->buildQuery($request)
                ->paginate($perPage, $this->columns, 'page', $page);

            $result = [
                'warehouses' => $paginator->items(),
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                    'has_more' => $paginator->hasMorePages(),
                ],
            ];

            return ResponseFormatter::success($result, 'Warehouses fetched successfully')->toResponse();
        } catch (\Exception $e) {
            return ResponseFormatter::error($e->getMessage())->toResponse();
        }
    }

    protected function buildQuery(GetWarehouseRequest $request): Builder
    {
        $term = $request->term;
        $isActive = $request->is_active;
        $parentId = $request->parent_id;
        $includeSelf = $request->boolean('include_self');
        $ignoreAuthorization = $request->boolean('ignore_authorization');
        $stockMonitor = $request->boolean('stock_monitor');

        $query = MsWarehouse::query()
            ->where('Active', $isActive);

        if (!$ignoreAuthorization) {
            if ($stockMonitor) {
                // Broader rule for the stock monitoring screen: user-assigned
                // warehouses PLUS any warehouse flagged openInStockMonitoring=1.
                // Self-guards on the implement_user_warehouse_mapping toggle,
                // same as the branch below.
                WarehouseAccessCriteria::applyForStockMonitoring(
                    $query,
                    $query->getModel()->qualifyColumn('WarehouseID')
                );
            } else {
                WarehouseAccessCriteria::applyWithChildren($query);
            }
        }

        return $query
            ->when($parentId, function ($query, $parentId) use ($includeSelf) {
                $query->where(function ($q) use ($parentId, $includeSelf) {
                    $q->where('ParentID', $parentId);
                    if ($includeSelf) {
                        $q->orWhere('WarehouseID', $parentId);
                    }
                });
            })
            ->when($term, function ($query, $term) {
                $query->where(function ($q) use ($term) {
                    $q->where('WarehouseID', 'like', "%{$term}%")
                        ->orWhere('WarehouseName', 'like', "%{$term}%");
                });
            });
    }
}
