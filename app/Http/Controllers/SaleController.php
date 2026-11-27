<?php

namespace App\Http\Controllers;

use App\Actions\LoadAutocompleteOptions;
use App\Actions\RegisterSale;
use App\Http\Requests\ListSearchRequest;
use App\Http\Requests\SaleRequest;
use App\Http\TableSort;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function index(ListSearchRequest $request): View
    {
        $sort = $request->sorting([
            'numero' => 'id',
            'cliente' => Customer::query()->select('name')->whereColumn('customers.id', 'sales.customer_id'),
            'data' => 'sold_on',
            'total' => 'total',
            'situacao' => Sale::paymentStatusColumn(),
        ], text: ['cliente'], default: '-data');

        return view('sales.index', [
            'sales' => $sort->apply($request->filter(Sale::query(), ['id', 'customer.name', 'items.product.name']))
                ->with('customer:id,name')->withPaidTotal()->paginate(self::PerPage)->withQueryString(),
            'sort' => $sort,
        ]);
    }

    public function create(Request $request, LoadAutocompleteOptions $options): View
    {
        $previousItems = $request->old('items', []);
        $productIds = is_array($previousItems) ? collect($previousItems)->pluck('product_id')->all() : [];

        return view('sales.create', [
            'customers' => $options->handle(Customer::query(), [$request->old('customer_id')], ['id', 'name']),
            'products' => $options->handle(Product::query()->where('is_active', true)->where('stock_quantity', '>', 0),
                $productIds, ['id', 'name', 'sale_price', 'stock_quantity']),
        ]);
    }

    public function store(SaleRequest $request, RegisterSale $registerSale): RedirectResponse
    {
        $sale = $registerSale->handle($request->user(), $request->validated());

        return to_route('sales.show', $sale)->with('status', 'Venda registrada e estoque atualizado.');
    }

    public function show(ListSearchRequest $request, Sale $sale): View
    {
        $itemsSort = $request->sorting([
            'produto' => Product::query()->select('name')->whereColumn('products.id', 'sale_items.product_id'),
            'quantidade' => 'quantity',
            'preco' => 'unit_price',
            'custo' => 'unit_cost',
            'subtotal' => 'subtotal',
        ], text: ['produto'], key: 'ordem_itens', pageName: null);
        $paymentsSort = $request->sorting([
            'numero' => 'id',
            'data' => 'paid_on',
            'forma' => TableSort::labels('payments.method', Payment::Methods),
            'valor' => 'payment_allocations.amount',
        ], text: ['forma'], default: '-data', key: 'ordem_pagamentos');

        $sale->load([
            'customer:id,name',
            'items' => fn ($items) => $itemsSort->apply($request->filter($items, ['product.name', 'product.barcode'], 'q_itens'))
                ->with('product:id,name'),
        ])->loadCount(['items', 'payments'])->loadSum('paymentAllocations as paid_total', 'amount');

        return view('sales.show', [
            'sale' => $sale,
            'paymentToken' => (string) Str::uuid(),
            'itemsSort' => $itemsSort,
            'paymentsSort' => $paymentsSort,
            'payments' => $paymentsSort->apply($request->filter($sale->payments(), ['id', 'notes'], 'q_pagamentos', ['method' => Payment::Methods]))
                ->paginate(self::PerPage)->withQueryString(),
        ]);
    }
}
