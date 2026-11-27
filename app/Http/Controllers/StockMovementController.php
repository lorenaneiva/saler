<?php

namespace App\Http\Controllers;

use App\Actions\RegisterStockMovement;
use App\Http\Requests\StockMovementRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;

class StockMovementController extends Controller
{
    public function store(StockMovementRequest $request, Product $product, RegisterStockMovement $registerStockMovement): RedirectResponse
    {
        $registerStockMovement->handle($product, $request->user(), $request->validated());

        return to_route('products.show', $product)->with('status', 'Movimentação registrada e estoque atualizado.');
    }
}
