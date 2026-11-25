<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
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
            'products' => Product::query()->with(['category:id,name', 'supplier:id,name'])->orderBy('name')->orderBy('id')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('products.form', $this->formData(new Product));
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        try {
            Product::query()->create($request->validated());
        } catch (QueryException $exception) {
            if ($exception->getCode() !== '23503') {
                throw $exception;
            }

            throw ValidationException::withMessages(['category_id' => 'Verifique se a categoria e o fornecedor selecionados ainda existem.']);
        }

        return to_route('products.index')->with('status', 'Produto cadastrado.');
    }

    public function edit(Product $product): View
    {
        return view('products.form', $this->formData($product));
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        try {
            $product->update($request->validated());
        } catch (QueryException $exception) {
            if ($exception->getCode() !== '23503') {
                throw $exception;
            }

            throw ValidationException::withMessages(['category_id' => 'Verifique se a categoria e o fornecedor selecionados ainda existem.']);
        }

        return to_route('products.index')->with('status', 'Produto atualizado.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->forceFill(['is_active' => false])->save();

        return to_route('products.index')->with('status', 'Produto desativado.');
    }

    /** @return array{product: Product, categories: Collection<int, Category>, suppliers: Collection<int, Supplier>} */
    private function formData(Product $product): array
    {
        return [
            'product' => $product,
            'categories' => Category::query()->orderBy('name')->orderBy('id')->get(['id', 'name']),
            'suppliers' => Supplier::query()->orderBy('name')->orderBy('id')->get(['id', 'name', 'is_active']),
        ];
    }
}
