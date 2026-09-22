<?php

namespace App\Http\Controllers\Api\Master;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\GetPartRequest;
use App\Models\MsPart;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Schema;

class PartController extends Controller
{
    protected array $columns = ['PartID', 'PartName', 'Active', 'InventoryTypeID', 'Notes', 'WithSerialNo'];

    public function getPart(GetPartRequest $request): JsonResponse
    {
        try {
            $parts = $this->buildQuery($request)
                ->limit(10)
                ->get($this->columns);

            $result = [
                'parts' => $parts
            ];

            return ResponseFormatter::success($result, 'Parts fetched successfully')->toResponse();
        } catch (\Exception $e) {
            return ResponseFormatter::error($e->getMessage())->toResponse();
        }
    }

    public function getPartPaginated(GetPartRequest $request): JsonResponse
    {
        try {
            $page = (int) ($request->page ?? 1);
            $perPage = (int) ($request->per_page ?? 10);

            $paginator = $this->buildQuery($request)
                ->paginate($perPage, $this->columns, 'page', $page);

            $result = [
                'parts' => $paginator->items(),
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                    'has_more' => $paginator->hasMorePages(),
                ],
            ];

            return ResponseFormatter::success($result, 'Parts fetched successfully')->toResponse();
        } catch (\Exception $e) {
            return ResponseFormatter::error($e->getMessage())->toResponse();
        }
    }

    protected function buildQuery(GetPartRequest $request): Builder
    {
        $term = $request->term;
        $isActive = $request->is_active;
        $module = $request->module;

        $query = MsPart::query()
            ->with([
                'units:PartID,UnitID1,UnitID2,Conversion',
                'units.unit1:UnitID,UnitName',
                'units.unit2:UnitID,UnitName',
                'type:InventoryTypeID,InventoryTypeName'
            ])
            ->where('Active', $isActive);

        // Check if NotShow column exists in Ms_Part table
        if (Schema::connection('sqlsrv')->hasColumn('Ms_Part', 'NotShow')) {
            $query->where('NotShow', 0);
        }

        $visibilityColumns = [
            'transfer_request' => 'ShowInTrasferRequest',
            'purchase_order' => 'ShowInPurchaseOrder',
        ];

        if (isset($visibilityColumns[$module])) {
            $column = $visibilityColumns[$module];

            if (Schema::connection('sqlsrv')->hasColumn('Ms_Part', $column)) {
                $query->where($column, 1);
            }
        }

        return $query->when($term, function ($query, $term) {
            $query->where(function ($q) use ($term) {
                $q->where('PartID', 'like', "%{$term}%")
                    ->orWhere('PartName', 'like', "%{$term}%")
                    ->orWhere('Notes', 'like', "%{$term}%");
            });
        });
    }
}
