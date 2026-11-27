<?php

namespace App\Http\Controllers;

use App\Actions\RegisterStockMovement;
use App\Http\Requests\ListSearchRequest;
use App\Http\Requests\StockMovementRequest;
use App\Http\TableSort;
use App\Models\Product;
use App\Models\StockAdjustmentReason;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Query\Expression;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StockController extends Controller
{
    public function index(ListSearchRequest $request): View
    {
        $sort = $request->sorting([
            'nome' => 'name',
            'saldo' => 'stock_quantity',
            'custo' => Product::registeredCostColumn(),
        ], text: ['nome'], default: 'nome');

        return view('stock.index', [
            'products' => $sort->apply($request->filter(Product::query()->withRegisteredCost(), ['name', 'barcode']))
                ->paginate(self::PerPage)->withQueryString(),
            'sort' => $sort,
        ]);
    }

    public function show(ListSearchRequest $request, Product $product): View
    {
        $sort = $request->sorting([
            'data' => 'created_at',
            'tipo' => new Expression("CASE WHEN stock_movements.quantity_delta > 0 THEN 'Entrada' ELSE 'Saída' END"),
            'origem' => TableSort::labels('stock_movements.type', StockMovement::TypeLabels),
            'quantidade' => 'quantity_delta',
            'custo' => 'unit_cost',
            'prejuizo' => StockMovement::lossColumn(),
            'responsavel' => User::query()->select('name')->whereColumn('users.id', 'stock_movements.user_id'),
            'motivo' => new Expression('COALESCE(('.StockAdjustmentReason::query()->select('motivos.name')
                ->join('ajustes_estoque', 'ajustes_estoque.reason_id', '=', 'motivos.id')
                ->whereColumn('ajustes_estoque.stock_movement_id', 'stock_movements.id')->toSql().'), stock_movements.justification)'),
        ], text: ['tipo', 'origem', 'responsavel', 'motivo'], default: '-data');

        return view('stock.show', [
            'sort' => $sort,
            'product' => $product,
            'reasons' => StockAdjustmentReason::query()->orderBy('name')->get(['id', 'name']),
            'movements' => $sort->apply($request->filter($product->stockMovements(), ['justification', 'user.name', 'adjustment.reason.name']))
                ->with(['user:id,name', 'purchaseItem:id,purchase_id', 'saleItem:id,sale_id', 'adjustment.reason'])
                ->paginate(self::PerPage)->withQueryString(),
        ]);
    }

    public function store(StockMovementRequest $request, Product $product, RegisterStockMovement $registerStockMovement): RedirectResponse
    {
        $registerStockMovement->handle($product, $request->user(), $request->validated());

        return to_route('stock.show', $product)->with('status', 'Movimentação registrada e estoque atualizado.');
    }
}
