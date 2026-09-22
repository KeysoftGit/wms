<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Permission\GetSpecificPermissionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PermissionController extends Controller
{
    public function getAllPermission(): JsonResponse
    {
        try {
            $user = Auth::user();

            $permissionNames = $user->permissions()->pluck('name');

            $result = [
                'user_id' => $user->UserID,
                'permissions' => $permissionNames->toArray(),
            ];

            return ResponseFormatter::success($result, 'All permissions retrieved successfully')->toResponse();
        } catch (\Exception $e) {
            Log::error($e);
            return ResponseFormatter::error($e->getMessage())->toResponse();
        }
    }

    public function getSpecificPermission(GetSpecificPermissionRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();

            $permissionNames = $user->permissions()
                ->whereIn('name', $request->names)
                ->pluck('name');

            $result = [
                'user_id' => $user->UserID,
                'permissions' => $permissionNames->toArray(),
            ];

            return ResponseFormatter::success($result, 'Specific permissions retrieved successfully')->toResponse();
        } catch (\Exception $e) {
            Log::error($e);
            return ResponseFormatter::error($e->getMessage())->toResponse();
        }
    }
}
