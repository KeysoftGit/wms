<?php

namespace App\Http\Controllers\Api\Master;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\GetInventoryTypeRequest;
use App\Models\MsInventoryType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class InventoryTypeController extends Controller
{
    protected array $columns = ['InventoryTypeID', 'InventoryTypeName'];

    public function getInventoryType(GetInventoryTypeRequest $request): JsonResponse
    {
        try {
            $types = $this->buildQuery($request)
                ->limit(10)
                ->get($this->columns);

            $result = [
                'types' => $types
            ];

            return ResponseFormatter::success($result, 'Inventory type fetched successfully')->toResponse();
        } catch (\Exception $e) {
            return ResponseFormatter::error($e->getMessage())->toResponse();
        }
    }

    public function getInventoryTypePaginated(GetInventoryTypeRequest $request): JsonResponse
    {
        try {
            $page = (int) ($request->page ?? 1);
            $perPage = (int) ($request->per_page ?? 10);

            $paginator = $this->buildQuery($request)
                ->paginate($perPage, $this->columns, 'page', $page);

            // NOTE: the client's paginated repository expects the list under
            // "inventory_types" here, unlike the plain endpoint above which
            // uses "types" — this mismatch is baked into the Flutter client,
            // do not "fix" one without updating the other.
            $result = [
                'inventory_types' => $paginator->items(),
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                    'has_more' => $paginator->hasMorePages(),
                ],
            ];

            return ResponseFormatter::success($result, 'Inventory type fetched successfully')->toResponse();
        } catch (\Exception $e) {
            return ResponseFormatter::error($e->getMessage())->toResponse();
        }
    }

    protected function buildQuery(GetInventoryTypeRequest $request): Builder
    {
        $term = $request->term;

        return MsInventoryType::query()
            ->when($term, function ($query, $term) {
                $query->where(function ($q) use ($term) {
                    $q->where('InventoryTypeID', 'like', "%{$term}%")
                        ->orWhere('InventoryTypeName', 'like', "%{$term}%");
                });
            });
    }
}
