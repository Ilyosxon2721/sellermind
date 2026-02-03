<?php

namespace App\Http\Controllers\Api\Risment;

use App\Http\Controllers\Controller;
use App\Models\Risment\RismentApiToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TokenController extends Controller
{
    /**
     * POST /api/v1/integration/tokens
     * Generate a new API token for RISMENT integration
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'name' => 'required|string|max:255',
            'scopes' => 'nullable|array',
            'scopes.*' => 'string|in:products,stock,orders,webhooks',
            'expires_in_days' => 'nullable|integer|min:1|max:365',
        ]);

        // Check user has access to this company
        $user = $request->user();
        if (!$user->companies()->where('companies.id', $validated['company_id'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied to this company.',
            ], 403);
        }

        // Generate token
        $plainToken = Str::random(48);
        $tokenHash = hash('sha256', $plainToken);

        $expiresAt = isset($validated['expires_in_days'])
            ? now()->addDays($validated['expires_in_days'])
            : null;

        $apiToken = RismentApiToken::create([
            'company_id' => $validated['company_id'],
            'name' => $validated['name'],
            'token' => $tokenHash,
            'scopes' => $validated['scopes'] ?? null,
            'expires_at' => $expiresAt,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $apiToken->id,
                'name' => $apiToken->name,
                'token' => $plainToken, // shown only once
                'scopes' => $apiToken->scopes,
                'expires_at' => $apiToken->expires_at,
                'created_at' => $apiToken->created_at,
            ],
            'message' => 'Save this token securely. It will not be shown again.',
        ], 201);
    }

    /**
     * GET /api/v1/integration/tokens
     * List all tokens for the company
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
        ]);

        $user = $request->user();
        $companyId = $request->integer('company_id');

        if (!$user->companies()->where('companies.id', $companyId)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied to this company.',
            ], 403);
        }

        $tokens = RismentApiToken::where('company_id', $companyId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'scopes' => $t->scopes,
                'is_active' => $t->is_active,
                'last_used_at' => $t->last_used_at,
                'expires_at' => $t->expires_at,
                'created_at' => $t->created_at,
            ]);

        return response()->json([
            'success' => true,
            'data' => $tokens,
        ]);
    }

    /**
     * DELETE /api/v1/integration/tokens/{id}
     * Revoke an API token
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
        ]);

        $user = $request->user();
        $companyId = $request->integer('company_id');

        if (!$user->companies()->where('companies.id', $companyId)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied to this company.',
            ], 403);
        }

        $token = RismentApiToken::where('id', $id)
            ->where('company_id', $companyId)
            ->firstOrFail();

        $token->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Token revoked successfully.',
        ]);
    }
}
