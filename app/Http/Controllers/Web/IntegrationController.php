<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\IntegrationLink;
use App\Models\Risment\RismentClient;
use App\Models\Warehouse\Warehouse;
use Illuminate\Http\Request;

class IntegrationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $company = $user->companies()->first();

        $rismentLink = null;
        $rismentClientsCount = 0;

        if ($company) {
            $rismentLink = IntegrationLink::where('company_id', $company->id)
                ->where('external_system', 'risment')
                ->where('is_active', true)
                ->latest()
                ->first();

            $rismentClientsCount = RismentClient::where('company_id', $company->id)
                ->where('is_active', true)
                ->count();
        }

        return view('pages.integrations.index', compact('rismentLink', 'rismentClientsCount'));
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

        $clients = $company
            ? RismentClient::where('company_id', $company->id)
                ->with(['activeLink'])
                ->orderByDesc('created_at')
                ->get()
            : collect();

        $activeClientsCount = $clients->where('is_active', true)->count();
        $linkedClientsCount = $clients->filter(fn ($c) => $c->activeLink !== null)->count();

        return view('pages.integrations.risment', compact(
            'link',
            'warehouses',
            'clients',
            'activeClientsCount',
            'linkedClientsCount',
        ));
    }
}
