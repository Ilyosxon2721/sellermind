<?php

namespace App\Http\Middleware;

use App\Models\Risment\RismentApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateRisment
{
    public function handle(Request $request, Closure $next, ?string $scope = null): Response
    {
        $bearer = $request->bearerToken();

        if (!$bearer) {
            return response()->json([
                'success' => false,
                'message' => 'API token required. Pass Bearer token in Authorization header.',
            ], 401);
        }

        $tokenHash = hash('sha256', $bearer);

        $apiToken = RismentApiToken::where('token', $tokenHash)
            ->where('is_active', true)
            ->first();

        if (!$apiToken || !$apiToken->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired API token.',
            ], 401);
        }

        if ($scope && !$apiToken->hasScope($scope)) {
            return response()->json([
                'success' => false,
                'message' => "Token does not have required scope: {$scope}",
            ], 403);
        }

        // Attach company and token to request for controllers
        $request->attributes->set('risment_company', $apiToken->company);
        $request->attributes->set('risment_token', $apiToken);

        // Update last_used_at (throttled to once per minute)
        if (!$apiToken->last_used_at || $apiToken->last_used_at->diffInMinutes(now()) >= 1) {
            $apiToken->markUsed();
        }

        return $next($request);
    }
}
