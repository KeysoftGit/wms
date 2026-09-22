<?php

namespace App\Http\Controllers\Api\Master;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\GetCustomerRequest;
use App\Models\MsCustomer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CustomerController extends Controller
{
    protected array $columns = ['CustomerID', 'CustomerName', 'Active', 'SalesmanID'];

    public function getCustomer(GetCustomerRequest $request): JsonResponse
    {
        try {
            $customers = $this->buildQuery($request)
                ->limit(10)
                ->get($this->columns);

            $result = [
                'customers' => $customers
            ];

            return ResponseFormatter::success($result, 'Customers fetched successfully')->toResponse();
        } catch (\Exception $e) {
            Log::error($e);
            return ResponseFormatter::error($e->getMessage())->toResponse();
        }
    }

    public function getCustomerPaginated(GetCustomerRequest $request): JsonResponse
    {
        try {
            $page = (int) ($request->page ?? 1);
            $perPage = (int) ($request->per_page ?? 10);

            $paginator = $this->buildQuery($request)
                ->paginate($perPage, $this->columns, 'page', $page);

            $result = [
                'customers' => $paginator->items(),
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                    'has_more' => $paginator->hasMorePages(),
                ],
            ];

            return ResponseFormatter::success($result, 'Customers fetched successfully')->toResponse();
        } catch (\Exception $e) {
            Log::error($e);
            return ResponseFormatter::error($e->getMessage())->toResponse();
        }
    }

    protected function buildQuery(GetCustomerRequest $request): Builder
    {
        $term = $request->term;
        $isActive = $request->is_active;
        $filterBySalesman = $request->filter_by_salesman;
        $user = Auth::user();

        if ($user->EmployeeID == '-') {
            $filterBySalesman = false;
        }

        return MsCustomer::query()
            ->where('Active', $isActive)
            ->when($filterBySalesman, function ($query) use ($user) {
                $employeeId = $user->EmployeeID;
                $query->where('SalesmanID', $employeeId);
            })
            ->when($term, function ($query, $term) {
                $query->where(function ($q) use ($term) {
                    $q->where('CustomerID', 'like', "%{$term}%")
                        ->orWhere('CustomerName', 'like', "%{$term}%");
                });
            });
    }
}
