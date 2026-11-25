<?php

namespace App\Actions;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegisterStockMovement
{
    /** @param array{type: string, quantity: int|numeric-string, unit_cost?: numeric-string|int|float, justification?: ?string, reason_id?: int|numeric-string} $data */
    public function handle(Product $product, User $user, array $data): void
    {
        DB::transaction(function () use ($product, $user, $data): void {
            $product = Product::query()->lockForUpdate()->findOrFail($product->id);
            $quantity = (int) $data['quantity'];
            $delta = match ($data['type']) {
                StockMovement::ManualEntry => $quantity,
                StockMovement::ManualExit => -$quantity,
                StockMovement::Adjustment => $quantity - $product->stock_quantity,
            };

            $previousQuantity = $product->stock_quantity;
            $newQuantity = $previousQuantity + $delta;

            if ($newQuantity < 0) {
                throw ValidationException::withMessages(['quantity' => 'A quantidade de saída excede o estoque disponível.']);
            }

            if ($newQuantity > 2147483647) {
                throw ValidationException::withMessages(['quantity' => 'O saldo de estoque ultrapassaria o limite permitido.']);
            }

            if ($delta === 0) {
                throw ValidationException::withMessages(['quantity' => 'Informe um saldo diferente do estoque atual.']);
            }

            $movementCost = $product->average_cost;

            if ($data['type'] === StockMovement::ManualEntry) {
                $movementCost = $data['unit_cost'];
                $inventoryValue = BigDecimal::of($product->average_cost)->multipliedBy($product->stock_quantity);
                $entryValue = BigDecimal::of($movementCost)->multipliedBy($delta);
                $product->average_cost = (string) $inventoryValue->plus($entryValue)->dividedBy($newQuantity, 4, RoundingMode::HalfUp);
            }

            $product->stock_quantity = $newQuantity;
            $product->save();

            $movement = $product->stockMovements()->create([
                'user_id' => $user->id,
                'quantity_delta' => $delta,
                'unit_cost' => $movementCost,
                'type' => $data['type'],
                'justification' => $data['justification'] ?? null,
            ]);

            if ($data['type'] === StockMovement::Adjustment) {
                $movement->adjustment()->create([
                    'reason_id' => $data['reason_id'],
                    'previous_quantity' => $previousQuantity,
                    'counted_quantity' => $newQuantity,
                ]);
            }
        }, 3);
    }
}
