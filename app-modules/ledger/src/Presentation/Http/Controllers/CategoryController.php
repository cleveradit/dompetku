<?php

declare(strict_types=1);

namespace Modules\Ledger\Presentation\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Ledger\Application\Actions\CreateCategory;
use Modules\Ledger\Application\Actions\DeleteCategory;
use Modules\Ledger\Application\Actions\UpdateCategory;
use Modules\Ledger\Domain\Enums\CategoryType;
use Modules\Ledger\Domain\Exceptions\CategoryInUse;
use Modules\Ledger\Domain\Exceptions\CategoryTypeLocked;
use Modules\Ledger\Infrastructure\Models\Category;
use Modules\Ledger\Presentation\Http\Requests\StoreCategoryRequest;

class CategoryController extends Controller
{
    use AuthorizesRequests;

    public function index(): Response
    {
        $categories = Category::query()
            ->orderBy('type')
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'type' => $category->type->value,
                'color' => $category->color,
                'icon' => $category->icon,
                'is_default' => $category->is_default,
            ]);

        return Inertia::render('categories/index', ['categories' => $categories]);
    }

    public function store(StoreCategoryRequest $request, CreateCategory $createCategory): RedirectResponse
    {
        $validated = $request->validated();

        $createCategory->handle(
            userId: $request->user()->id,
            name: trim($validated['name']),
            type: CategoryType::from($validated['type']),
            color: $validated['color'] ?? null,
            icon: $validated['icon'] ?? null,
        );

        return back()->with('success', __('ui.category_created'));
    }

    public function update(StoreCategoryRequest $request, Category $category, UpdateCategory $updateCategory): RedirectResponse
    {
        $this->authorize('update', $category);

        $validated = $request->validated();

        try {
            $updateCategory->handle(
                category: $category,
                name: trim($validated['name']),
                type: CategoryType::from($validated['type']),
                color: $validated['color'] ?? null,
                icon: $validated['icon'] ?? null,
            );
        } catch (CategoryTypeLocked $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', __('ui.category_updated'));
    }

    public function destroy(Category $category, DeleteCategory $deleteCategory): RedirectResponse
    {
        $this->authorize('delete', $category);

        try {
            $deleteCategory->handle($category);
        } catch (CategoryInUse $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', __('ui.category_deleted'));
    }
}
