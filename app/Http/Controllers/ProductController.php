<?php

namespace App\Http\Controllers;

use App\Actions\CreateProduct;
use App\Http\Requests\ListSearchRequest;
use App\Http\Requests\ProductRequest;
use App\Http\TableSort;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(ListSearchRequest $request): View
    {
        $query = $request->query('q');
        $search = is_string($query) ? mb_substr(trim($query), 0, 100) : '';
        $pattern = '%'.addcslashes($search, '%_\\').'%';
        $category = filter_var($request->query('categoria'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $supplier = filter_var($request->query('fornecedor'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $status = $request->query('situacao');
        $sort = $request->sorting([
            'nome' => 'name',
            'categoria' => Category::query()->select('name')->whereColumn('categories.id', 'products.category_id'),
            'preco' => 'sale_price',
            'custo' => Product::registeredCostColumn(),
            'margem' => Product::profitMarginColumn(),
            'estoque' => 'stock_quantity',
            'situacao' => TableSort::labels('products.is_active', [1 => 'Ativo', 0 => 'Inativo']),
        ], text: ['nome', 'categoria', 'situacao'], default: 'nome');

        return view('products.index', [
            'products' => $sort->apply(Product::query())->with(['category:id,name', 'supplier:id,name'])->withRegisteredCost()
                ->when($search !== '', fn (Builder $products) => $products->where(
                    fn (Builder $matches) => $matches->whereLike('name', $pattern)->orWhereLike('barcode', $pattern),
                ))
                ->when($category, fn (Builder $products) => $products->where('category_id', $category))
                ->when($supplier, fn (Builder $products) => $products->where('supplier_id', $supplier))
                ->when(in_array($status, ['ativos', 'inativos'], true), fn (Builder $products) => $products->where('is_active', $status === 'ativos'))
                ->paginate(self::PerPage)->withQueryString(),
            'sort' => $sort,
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name']),
            'filters' => ['search' => $search, 'category' => $category, 'supplier' => $supplier, 'status' => $status],
        ]);
    }

    public function create(Request $request): View|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['html' => view('products.editor', $this->formData(new Product))->render()]);
        }

        return view('products.form', $this->formData(new Product));
    }

    public function store(ProductRequest $request, CreateProduct $createProduct): RedirectResponse|JsonResponse
    {
        $initialPurchase = $request->initialPurchase();

        try {
            $createProduct->handle($request->user(), $request->productData(), $initialPurchase);
        } catch (QueryException $exception) {
            if ($exception->getCode() !== '23503') {
                throw $exception;
            }

            throw ValidationException::withMessages(['category_id' => 'Verifique se a categoria e o fornecedor selecionados ainda existem.']);
        }

        $message = $initialPurchase ? 'Produto cadastrado e compra inicial registrada no estoque.' : 'Produto cadastrado.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 201);
        }

        return to_route('products.index')->with('status', $message);
    }

    public function edit(Request $request, Product $product): View|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['html' => view('products.editor', $this->formData($product))->render()]);
        }

        return view('products.form', $this->formData($product));
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse|JsonResponse
    {
        try {
            $product->update($request->validated());
        } catch (QueryException $exception) {
            if ($exception->getCode() !== '23503') {
                throw $exception;
            }

            throw ValidationException::withMessages(['category_id' => 'Verifique se a categoria e o fornecedor selecionados ainda existem.']);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Produto atualizado.']);
        }

        return to_route('products.index')->with('status', 'Produto atualizado.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->forceFill(['is_active' => false])->save();

        return to_route('products.index')->with('status', 'Produto desativado.');
    }

    /** @return array{product: Product, submissionToken: string, categories: Collection<int, Category>, suppliers: Collection<int, Supplier>} */
    private function formData(Product $product): array
    {
        return [
            'product' => $product,
            'submissionToken' => (string) Str::uuid(),
            'categories' => Category::query()
                ->where(fn (Builder $categories) => $categories->where('is_active', true)
                    ->when($product->category_id, fn (Builder $active) => $active->orWhere('id', $product->category_id)))
                ->orderBy('name')->orderBy('id')->get(['id', 'name', 'is_active']),
            'suppliers' => Supplier::query()->orderBy('name')->orderBy('id')->get(['id', 'name', 'is_active']),
        ];
    }
}
