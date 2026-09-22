<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\MsCompanyProfile;
use App\Models\MsUser;
use App\Services\JWTService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected JWTService $jwtService;

    public function __construct(JWTService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    /**
     * Handle the login request.
     *
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            // Finding the user by username (matching the Ms_User table structure)
            $user = MsUser::where('Active', 1)->where('UserName', $request->username)->first();

            // Check if user exists and password is correct
            if (!$user || !Hash::check($request->password, $user->WebPassword)) {
                throw ValidationException::withMessages([
                    'username' => ['The provided credentials are incorrect.'],
                ]);
            }

            // Fetch company name from Ms_CompanyProfile
            $companyName = MsCompanyProfile::value('CompanyName');

            // Create JWT token
            $payload = [
                'sub' => $user->UserID,
                'username' => $user->UserName,
                'companyName' => $companyName,
                'guid' => $request->guid,
            ];

            $token = $this->jwtService->createToken($payload);

            // Store token in database to enforce single-device login
            $user->update(['jwt_token' => $token]);

            $result = [
                'access_token' => $token,
                'company_name' => $companyName,
                'user' => [
                    'user_id' => $user->UserID,
                    'username' => $user->UserName,
                ],
            ];

            return ResponseFormatter::success($result, 'Login successfully')->toResponse();
        } catch (ValidationException $e) {
            // Standard API 422 Unprocessable Entity
            return ResponseFormatter::error(
                $e->getMessage(),
                422,
                $e->errors()
            )->toResponse();
        } catch (\Exception $e) {
            Log::error($e);
            return ResponseFormatter::error($e->getMessage())->toResponse();
        }
    }

    /**
     * Handle the logout request.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            // JWT logout is purely client-side unless using a blacklist.
            // Since we're not storing tokens in the DB, we just return success.
            return ResponseFormatter::success(null, 'Logout successfully')->toResponse();
        } catch (\Exception $e) {
            return ResponseFormatter::error($e->getMessage())->toResponse();
        }
    }
}
