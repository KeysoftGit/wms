<?php

namespace App\Http\Controllers\Api\Master;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\GetVehicleRequest;
use App\Models\MsVehicle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class VehicleController extends Controller
{
    protected array $columns = ['VehicleID', 'VehicleName', 'LicenseNo', 'Active'];

    public function getVehicle(GetVehicleRequest $request): JsonResponse
    {
        try {
            $vehicles = $this->buildQuery($request)
                ->limit(10)
                ->get($this->columns);

            $result = [
                'vehicles' => $vehicles
            ];

            return ResponseFormatter::success($result, 'Vehicles fetched successfully')->toResponse();
        } catch (\Exception $e) {
            return ResponseFormatter::error($e->getMessage())->toResponse();
        }
    }

    public function getVehiclePaginated(GetVehicleRequest $request): JsonResponse
    {
        try {
            $page = (int) ($request->page ?? 1);
            $perPage = (int) ($request->per_page ?? 10);

            $paginator = $this->buildQuery($request)
                ->paginate($perPage, $this->columns, 'page', $page);

            $result = [
                'vehicles' => $paginator->items(),
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                    'has_more' => $paginator->hasMorePages(),
                ],
            ];

            return ResponseFormatter::success($result, 'Vehicles fetched successfully')->toResponse();
        } catch (\Exception $e) {
            return ResponseFormatter::error($e->getMessage())->toResponse();
        }
    }

    protected function buildQuery(GetVehicleRequest $request): Builder
    {
        $term = $request->term;
        $isActive = $request->is_active;

        return MsVehicle::query()
            ->where('Active', $isActive)
            ->when($term, function ($query, $term) {
                $query->where(function ($q) use ($term) {
                    $q->where('VehicleID', 'like', "%{$term}%")
                        ->orWhere('VehicleName', 'like', "%{$term}%");
                });
            });
    }
}
