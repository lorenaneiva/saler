<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        return view('products.index', [
            'products' => Product::query()->with('category:id,name')->orderBy('name')->orderBy('id')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('products.form', ['product' => new Product, 'categories' => $this->categories()]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        try {
            Product::query()->create($request->validated());
        } catch (QueryException $exception) {
            if ($exception->getCode() !== '23503') {
                throw $exception;
            }

            throw ValidationException::withMessages(['category_id' => 'A categoria selecionada não existe.']);
        }

        return to_route('products.index')->with('status', 'Produto cadastrado.');
    }

    public function edit(Product $product): View
    {
        return view('products.form', ['product' => $product, 'categories' => $this->categories()]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        try {
            $product->update($request->validated());
        } catch (QueryException $exception) {
            if ($exception->getCode() !== '23503') {
                throw $exception;
            }

            throw ValidationException::withMessages(['category_id' => 'A categoria selecionada não existe.']);
        }

        return to_route('products.index')->with('status', 'Produto atualizado.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->forceFill(['is_active' => false])->save();

        return to_route('products.index')->with('status', 'Produto desativado.');
    }

    /** @return Collection<int, Category> */
    private function categories(): Collection
    {
        return Category::query()->orderBy('name')->orderBy('id')->get(['id', 'name']);
    }
}
