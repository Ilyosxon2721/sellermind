<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\IntegrationLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IntegrationLinkController extends Controller
{
    /**
     * Show the integration link page
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $company = $user->companies()->first();

        $link = $company
            ? IntegrationLink::where('company_id', $company->id)
                ->where('external_system', 'risment')
                ->latest()
                ->first()
            : null;

        return view('pages.integration-link', compact('link'));
    }

    /**
     * Store/update the RISMENT link token
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'link_token' => 'required|string|min:8|max:128',
        ]);

        $user = $request->user();
        $company = $user->companies()->first();

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'No company found. Please create a company first.',
            ], 422);
        }

        // Deactivate any existing RISMENT links for this company
        IntegrationLink::where('company_id', $company->id)
            ->where('external_system', 'risment')
            ->where('is_active', true)
            ->update(['is_active' => false]);

        // Create new link
        $link = IntegrationLink::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'external_system' => 'risment',
            'link_token' => $validated['link_token'],
            'is_active' => true,
            'linked_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'RISMENT integration linked successfully.',
            'data' => [
                'id' => $link->id,
                'linked_at' => $link->linked_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Disconnect RISMENT integration
     */
    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();
        $company = $user->companies()->first();

        if ($company) {
            IntegrationLink::where('company_id', $company->id)
                ->where('external_system', 'risment')
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        return response()->json([
            'success' => true,
            'message' => 'RISMENT integration disconnected.',
        ]);
    }
}
