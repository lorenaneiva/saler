<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Database\ForeignKeyConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('categories.index', [
            'categories' => Category::query()->orderBy('name')->orderBy('id')->paginate(20),
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
            $category->delete();
        } catch (ForeignKeyConstraintViolationException) {
            return to_route('categories.index')->withErrors(['category' => 'Categorias vinculadas a produtos não podem ser excluídas.']);
        }

        return to_route('categories.index')->with('status', 'Categoria excluída.');
    }
}
