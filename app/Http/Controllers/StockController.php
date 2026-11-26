<?php

namespace App\Http\Controllers;

use App\Actions\RegisterStockMovement;
use App\Http\Requests\ListSearchRequest;
use App\Http\Requests\StockMovementRequest;
use App\Models\Product;
use App\Models\StockAdjustmentReason;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StockController extends Controller
{
    public function index(ListSearchRequest $request): View
    {
        return view('stock.index', [
            'products' => $request->filter(Product::query()->withRegisteredCost(), ['name', 'barcode'])
                ->orderBy('name')->orderBy('id')->paginate(20)->withQueryString(),
        ]);
    }

    public function show(ListSearchRequest $request, Product $product): View
    {
        return view('stock.show', [
            'product' => $product,
            'reasons' => StockAdjustmentReason::query()->orderBy('name')->get(['id', 'name']),
            'movements' => $request->filter($product->stockMovements(), ['justification', 'user.name', 'adjustment.reason.name'])
                ->with(['user:id,name', 'purchaseItem:id,purchase_id', 'saleItem:id,sale_id', 'adjustment.reason'])
                ->latest()->orderByDesc('id')->paginate(20)->withQueryString(),
        ]);
    }

    public function store(StockMovementRequest $request, Product $product, RegisterStockMovement $registerStockMovement): RedirectResponse
    {
        $registerStockMovement->handle($product, $request->user(), $request->validated());

        return to_route('stock.show', $product)->with('status', 'Movimentação registrada e estoque atualizado.');
    }
}
