<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\IntegrationLink;
use App\Models\Warehouse\Warehouse;
use Illuminate\Http\Request;

class IntegrationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $company = $user->companies()->first();

        $rismentLink = null;
        if ($company) {
            $rismentLink = IntegrationLink::where('company_id', $company->id)
                ->where('external_system', 'risment')
                ->where('is_active', true)
                ->latest()
                ->first();
        }

        return view('pages.integrations.index', compact('rismentLink'));
    }

    public function risment(Request $request)
    {
        $user = $request->user();
        $company = $user->companies()->first();

        $link = $company
            ? IntegrationLink::where('company_id', $company->id)
                ->where('external_system', 'risment')
                ->latest()
                ->first()
            : null;

        $warehouses = $company
            ? Warehouse::where('company_id', $company->id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name'])
            : collect();

        return view('pages.integrations.risment', compact('link', 'warehouses'));
    }
}
