<?php

namespace App\Http\Controllers;

use App\Actions\RegisterStockMovement;
use App\Http\Requests\StockMovementRequest;
use App\Models\Product;
use App\Models\StockAdjustmentReason;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StockController extends Controller
{
    public function index(): View
    {
        return view('stock.index', [
            'products' => Product::query()->orderBy('name')->orderBy('id')->paginate(20),
        ]);
    }

    public function show(Product $product): View
    {
        return view('stock.show', [
            'product' => $product,
            'reasons' => StockAdjustmentReason::query()->orderBy('name')->get(['id', 'name']),
            'movements' => $product->stockMovements()->with(['user:id,name', 'purchaseItem:id,purchase_id', 'saleItem:id,sale_id', 'adjustment.reason'])
                ->latest()->orderByDesc('id')->paginate(20),
        ]);
    }

    public function store(StockMovementRequest $request, Product $product, RegisterStockMovement $registerStockMovement): RedirectResponse
    {
        $registerStockMovement->handle($product, $request->user(), $request->validated());

        return to_route('stock.show', $product)->with('status', 'Movimentação registrada e estoque atualizado.');
    }
}
