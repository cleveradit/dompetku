<?php

declare(strict_types=1);

namespace Modules\Budget\Presentation\Http\Controllers;

use Brick\Math\BigDecimal;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Budget\Application\Actions\CopyBudgetsFromPreviousMonth;
use Modules\Budget\Application\Actions\DeleteBudget;
use Modules\Budget\Application\Actions\UpsertBudget;
use Modules\Budget\Infrastructure\Models\Budget;
use Modules\Budget\Presentation\Http\Requests\UpsertBudgetRequest;
use Modules\Ledger\Infrastructure\Models\Category;
use Modules\Reporting\Application\Queries\BudgetProgressQuery;

class BudgetController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request, BudgetProgressQuery $progressQuery): Response
    {
        $request->validate(['month' => ['nullable', 'date_format:Y-m-d']]);

        $month = CarbonImmutable::parse($request->query('month', now('Asia/Jakarta')->toDateString()))->startOfMonth();
        $userId = (int) $request->user()->id;

        $progress = $progressQuery->handle($userId, $month->toDateString());

        $totalBudget = '0.00';
        $totalSpent = '0.00';
        foreach ($progress as $item) {
            $totalBudget = (string) BigDecimal::of($totalBudget)->plus(BigDecimal::of((string) $item['amount']))->toScale(2);
            $totalSpent = (string) BigDecimal::of($totalSpent)->plus(BigDecimal::of((string) $item['spent']))->toScale(2);
        }

        $budgetedCategoryIds = array_map(fn (array $item) => $item['category_id'], $progress);

        $availableCategories = Category::query()
            ->where('type', 'expense')
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'color' => $category->color,
                'icon' => $category->icon,
                'has_budget' => in_array($category->id, $budgetedCategoryIds, true),
            ]);

        $previousMonthHasBudgets = Budget::query()
            ->where('month', $month->subMonthNoOverflow()->toDateString())
            ->exists();

        return Inertia::render('budgets/index', [
            'month' => $month->toDateString(),
            'monthLabel' => $month->locale('id')->translatedFormat('F Y'),
            'navigation' => [
                'prev' => $month->subMonthNoOverflow()->toDateString(),
                'next' => $month->addMonthNoOverflow()->toDateString(),
            ],
            'budgets' => $progress,
            'summary' => ['total_budget' => $totalBudget, 'total_spent' => $totalSpent],
            'categories' => $availableCategories,
            'canCopyPreviousMonth' => $previousMonthHasBudgets,
        ]);
    }

    public function store(UpsertBudgetRequest $request, UpsertBudget $upsertBudget): RedirectResponse
    {
        $validated = $request->validated();

        $upsertBudget->handle(
            userId: $request->user()->id,
            categoryId: (int) $validated['category_id'],
            month: $validated['month'],
            amount: $validated['amount'],
        );

        return back()->with('success', __('ui.budget_saved'));
    }

    public function copy(Request $request, CopyBudgetsFromPreviousMonth $copyBudgets): RedirectResponse
    {
        $request->validate(['month' => ['required', 'date_format:Y-m-d']]);

        $copyBudgets->handle($request->user()->id, $request->input('month'));

        return back()->with('success', __('ui.budgets_copied'));
    }

    public function destroy(Budget $budget, DeleteBudget $deleteBudget): RedirectResponse
    {
        $this->authorize('delete', $budget);

        $deleteBudget->handle($budget);

        return back()->with('success', __('ui.budget_deleted'));
    }
}
