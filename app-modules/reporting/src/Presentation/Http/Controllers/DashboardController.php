<?php

declare(strict_types=1);

namespace Modules\Reporting\Presentation\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Reporting\Application\Queries\DashboardSummaryQuery;

class DashboardController extends Controller
{
    public function index(Request $request, DashboardSummaryQuery $summary): Response
    {
        return Inertia::render('dashboard', [
            'summary' => $summary->handle((int) $request->user()->id),
        ]);
    }
}
