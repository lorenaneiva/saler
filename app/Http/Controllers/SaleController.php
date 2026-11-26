<?php

namespace App\Http\Controllers;

use App\Actions\RegisterSale;
use App\Http\Requests\ListSearchRequest;
use App\Http\Requests\SaleRequest;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function index(ListSearchRequest $request): View
    {
        return view('sales.index', [
            'sales' => $request->filter(Sale::query(), ['id', 'customer.name', 'items.product.name'])
                ->with('customer:id,name')->withPaidTotal()->orderByDesc('sold_on')->orderByDesc('id')->paginate(20)->withQueryString(),
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

    public function show(ListSearchRequest $request, Sale $sale): View
    {
        $sale->load([
            'customer:id,name',
            'items' => fn ($items) => $request->filter($items, ['product.name', 'product.barcode'], 'q_itens')
                ->with('product:id,name'),
        ])->loadSum('paymentAllocations as paid_total', 'amount');

        return view('sales.show', [
            'sale' => $sale,
            'payments' => $request->filter($sale->payments(), ['id', 'notes'], 'q_pagamentos', ['method' => Payment::Methods])
                ->orderByDesc('paid_on')->orderByDesc('payments.id')->paginate(20)->withQueryString(),
        ]);
    }
}
