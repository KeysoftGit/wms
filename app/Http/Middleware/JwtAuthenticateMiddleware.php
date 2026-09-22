<?php

namespace App\Http\Middleware;

use App\Helpers\ResponseFormatter;
use App\Models\MsUser;
use App\Services\JWTService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class JwtAuthenticateMiddleware
{
    public function __construct(
        protected JWTService $jwtService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return ResponseFormatter::error('Token not provided', 401)->toResponse();
        }

        $decoded = $this->jwtService->decodeToken($token);

        if (!$decoded) {
            return ResponseFormatter::error('Invalid or expired token', 401)->toResponse();
        }

        // Find the user (using the dynamic connection already set by the previous middleware)
        $user = MsUser::where('UserID', $decoded->sub)->first();

        if (!$user) {
            return ResponseFormatter::error('User not found', 401)->toResponse();
        }

        // Single-device login check: current token MUST match the one stored in database
        if ($user->jwt_token !== $token) {
            return ResponseFormatter::error('Invalid token.', 401)->toResponse();
        }

        // Set the user for the current request
        Auth::setUser($user);

        return $next($request);
    }
}
