<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function password(Request $request): Response
    {
        return Inertia::render('settings/password', [
            'status' => $request->session()->get('status'),
        ]);
    }

    public function appearance(): Response
    {
        return Inertia::render('settings/appearance');
    }

    public function account(): Response
    {
        return Inertia::render('settings/account');
    }
}
