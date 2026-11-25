<?php

namespace App\Http\Controllers;

use App\Actions\RegisterSale;
use App\Http\Requests\SaleRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function index(): View
    {
        return view('sales.index', [
            'sales' => Sale::query()->with('customer:id,name')->orderByDesc('sold_on')->orderByDesc('id')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('sales.create', [
            'customers' => Customer::query()->orderBy('name')->orderBy('id')->get(['id', 'name']),
            'products' => Product::query()->where('is_active', true)->where('stock_quantity', '>', 0)
                ->orderBy('name')->orderBy('id')->get(['id', 'name', 'sale_price', 'stock_quantity']),
        ]);
    }

    public function store(SaleRequest $request, RegisterSale $registerSale): RedirectResponse
    {
        $sale = $registerSale->handle($request->user(), $request->validated());

        return to_route('sales.show', $sale)->with('status', 'Venda registrada e estoque atualizado.');
    }

    public function show(Sale $sale): View
    {
        return view('sales.show', ['sale' => $sale->load(['customer:id,name', 'items.product:id,name'])]);
    }
}
