<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Database\QueryException;
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

        return view('categories.index', [
            'categories' => Category::query()->withCount('products')
                ->when($search !== '', fn ($categories) => $categories->whereLike('name', $pattern))
                ->orderBy('name')->orderBy('id')->paginate(20)->withQueryString(),
            'registeredCount' => Category::query()->count(),
            'search' => $search,
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

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return to_route('categories.index')->with('status', 'Categoria atualizada.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        try {
            $category->getConnection()->transaction(fn () => $category->delete());
        } catch (QueryException $exception) {
            if ($exception->getCode() !== '23503') {
                throw $exception;
            }

            return to_route('categories.index')->withErrors(['category' => 'Categorias vinculadas a produtos não podem ser excluídas.']);
        }

        return to_route('categories.index')->with('status', 'Categoria excluída.');
    }
}
