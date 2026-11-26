<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $query = $request->query('q');
        $search = is_string($query) ? mb_substr(trim($query), 0, 100) : '';
        $pattern = '%'.addcslashes($search, '%_\\').'%';
        $status = $request->query('situacao');
        if (! in_array($status, ['ativas', 'inativas', 'todas'], true)) {
            $status = 'ativas';
        }

        return view('categories.index', [
            'categories' => Category::query()
                ->withCount(['products', 'products as active_products_count' => fn ($products) => $products->where('is_active', true)])
                ->when($search !== '', fn ($categories) => $categories->whereLike('name', $pattern))
                ->when($status !== 'todas', fn ($categories) => $categories->where('is_active', $status === 'ativas'))
                ->orderBy('name')->orderBy('id')->paginate(self::PerPage)->withQueryString(),
            'registeredCount' => Category::query()->count(),
            'search' => $search,
            'status' => $status,
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        Category::query()->create($request->validated());

        return to_route('categories.index')->with('status', 'Categoria cadastrada.');
    }

    public function edit(Category $category): View
    {
        return view('categories.edit', compact('category'));
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse|JsonResponse
    {
        $category->update($request->validated());

        if ($request->expectsJson()) {
            $request->session()->flash('status', 'Categoria atualizada.');

            return response()->json(['message' => 'Categoria atualizada.']);
        }

        return to_route('categories.index')->with('status', 'Categoria atualizada.');
    }

    public function activate(Category $category): RedirectResponse
    {
        $category->forceFill(['is_active' => true])->save();

        return back(fallback: route('categories.index'))->with('status', 'Categoria ativada.');
    }

    public function deactivate(Category $category): RedirectResponse
    {
        $deactivated = $category->getConnection()->transaction(function () use ($category): bool {
            $lockedCategory = Category::query()->lockForUpdate()->findOrFail($category->getKey());

            if ($lockedCategory->products()->where('is_active', true)->exists()) {
                return false;
            }

            return $lockedCategory->forceFill(['is_active' => false])->save();
        });

        if (! $deactivated) {
            return back(fallback: route('categories.index'))
                ->withErrors(['category' => 'Categorias com produtos ativos não podem ser desativadas; desative os produtos antes.']);
        }

        return back(fallback: route('categories.index'))->with('status', 'Categoria desativada.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $error = 'Categorias que já tiveram produtos não podem ser excluídas; desative-as.';

        try {
            $deleted = $category->getConnection()->transaction(function () use ($category): bool {
                $lockedCategory = Category::query()->lockForUpdate()->find($category->getKey());

                if ($lockedCategory === null || ! $lockedCategory->canBeDeleted() || $lockedCategory->products()->exists()) {
                    return false;
                }

                return (bool) $lockedCategory->delete();
            });
        } catch (QueryException $exception) {
            if ($exception->getCode() !== '23503') {
                throw $exception;
            }

            $deleted = false;
        }

        if (! $deleted) {
            return back(fallback: route('categories.index'))->withErrors(['category' => $error]);
        }

        return back(fallback: route('categories.index'))->with('status', 'Categoria excluída.');
    }
}
