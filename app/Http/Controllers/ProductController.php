<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $query = $request->query('q');
        $search = is_string($query) ? mb_substr(trim($query), 0, 100) : '';
        $pattern = '%'.addcslashes($search, '%_\\').'%';
        $category = filter_var($request->query('categoria'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $supplier = filter_var($request->query('fornecedor'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $status = $request->query('situacao');

        return view('products.index', [
            'products' => Product::query()->with(['category:id,name', 'supplier:id,name'])
                ->when($search !== '', fn (Builder $products) => $products->where(
                    fn (Builder $matches) => $matches->whereLike('name', $pattern)->orWhereLike('barcode', $pattern),
                ))
                ->when($category, fn (Builder $products) => $products->where('category_id', $category))
                ->when($supplier, fn (Builder $products) => $products->where('supplier_id', $supplier))
                ->when(in_array($status, ['ativos', 'inativos'], true), fn (Builder $products) => $products->where('is_active', $status === 'ativos'))
                ->orderBy('name')->orderBy('id')->paginate(20)->withQueryString(),
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name']),
            'filters' => ['search' => $search, 'category' => $category, 'supplier' => $supplier, 'status' => $status],
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
