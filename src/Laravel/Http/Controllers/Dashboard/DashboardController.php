<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Http\Controllers\Dashboard;

use Illuminate\Contracts\View\View as ContractView;
use Illuminate\Routing\Controller;

/**
 * Dashboard index: a server-rendered shell that lists the tenant's apps and
 * accounts and drives the phone-login flow against the JSON API below it.
 * The host's own auth guard protects the whole group (default `web`+`auth`),
 * so this page is only reachable by an authenticated host user.
 */
final class DashboardController extends Controller
{
    use Concerns\ConcernsScopesVault;

    public function index(): ContractView
    {
        return view('teleframe::dashboard');
    }
}
