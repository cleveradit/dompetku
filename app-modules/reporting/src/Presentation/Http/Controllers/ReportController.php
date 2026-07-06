<?php

declare(strict_types=1);

namespace Modules\Reporting\Presentation\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Reporting\Application\Queries\SpendingByCategoryQuery;
use Modules\Reporting\Application\Queries\SpendingByPeriodQuery;
use Modules\Reporting\Application\Queries\SpendingByWalletQuery;
use Modules\Shared\Enums\ReportInterval;
use Modules\Shared\ValueObjects\DatePeriod;
use Modules\Wallet\Application\Queries\WalletOptionsQuery;

class ReportController extends Controller
{
    public function index(
        Request $request,
        SpendingByPeriodQuery $spendingByPeriod,
        SpendingByCategoryQuery $spendingByCategory,
        SpendingByWalletQuery $spendingByWallet,
        WalletOptionsQuery $walletOptions,
    ): Response {
        $validated = $request->validate([
            'interval' => ['nullable', 'in:daily,weekly,monthly,yearly,custom'],
            'anchor' => ['nullable', 'date_format:Y-m-d'],
            'start' => ['nullable', 'required_if:interval,custom', 'date_format:Y-m-d'],
            'end' => ['nullable', 'required_if:interval,custom', 'date_format:Y-m-d', 'after_or_equal:start'],
            'wallet' => ['nullable', 'integer'],
        ]);

        $interval = ReportInterval::from($validated['interval'] ?? 'monthly');
        $anchor = CarbonImmutable::parse($validated['anchor'] ?? now('Asia/Jakarta')->toDateString(), 'Asia/Jakarta');

        if ($interval === ReportInterval::Custom) {
            $start = CarbonImmutable::parse($validated['start'], 'Asia/Jakarta');
            $end = CarbonImmutable::parse($validated['end'], 'Asia/Jakarta');

            if ($start->diffInDays($end) > 366) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'end' => 'Rentang custom maksimal 366 hari.',
                ]);
            }

            $period = DatePeriod::custom($start, $end);
        } else {
            $period = DatePeriod::for($interval, $anchor);
        }

        $userId = (int) $request->user()->id;
        $walletId = isset($validated['wallet']) ? (int) $validated['wallet'] : null;

        return Inertia::render('reports/index', [
            'interval' => $interval->value,
            'period' => [
                'start' => $period->start->toDateString(),
                'end' => $period->end->toDateString(),
                'label' => $this->label($period),
            ],
            'navigation' => [
                'prev_anchor' => $period->previous()->start->toDateString(),
                'next_anchor' => $period->next()->start->toDateString(),
                'can_go_next' => $period->end->lessThan(now('Asia/Jakarta')->endOfDay()),
            ],
            'walletId' => $walletId,
            'wallets' => $walletOptions->all($userId),
            'totals' => $spendingByPeriod->totals($userId, $period, $walletId),
            'trend' => $spendingByPeriod->trend($userId, $period, $walletId),
            'categories' => $spendingByCategory->handle($userId, $period, 'expense', $walletId),
            'incomeCategories' => $spendingByCategory->handle($userId, $period, 'income', $walletId),
            'walletBreakdown' => $spendingByWallet->handle($userId, $period),
        ]);
    }

    private function label(DatePeriod $period): string
    {
        $start = $period->start->locale('id');
        $end = $period->end->locale('id');

        return match ($period->interval) {
            ReportInterval::Daily => $start->translatedFormat('j F Y'),
            ReportInterval::Weekly, ReportInterval::Custom => $start->translatedFormat('j M').' – '.$end->translatedFormat('j M Y'),
            ReportInterval::Monthly => $start->translatedFormat('F Y'),
            ReportInterval::Yearly => $start->format('Y'),
        };
    }
}
