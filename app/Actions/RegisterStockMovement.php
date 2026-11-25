<?php

namespace App\Actions;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegisterStockMovement
{
    /** @param array{type: string, quantity: int|numeric-string, justification?: ?string, reason_id: int|numeric-string} $data */
    public function handle(Product $product, User $user, array $data): void
    {
        DB::transaction(function () use ($product, $user, $data): void {
            $product = Product::query()->lockForUpdate()->findOrFail($product->id);
            $quantity = (int) $data['quantity'];
            $delta = match ($data['type']) {
                StockMovement::ManualEntry => $quantity,
                StockMovement::ManualExit => -$quantity,
            };

            $previousQuantity = $product->stock_quantity;
            $newQuantity = $previousQuantity + $delta;

            if ($newQuantity < 0) {
                throw ValidationException::withMessages(['quantity' => 'A quantidade de saída excede o estoque disponível.']);
            }

            if ($newQuantity > 2147483647) {
                throw ValidationException::withMessages(['quantity' => 'O saldo de estoque ultrapassaria o limite permitido.']);
            }

            $product->stock_quantity = $newQuantity;
            $product->save();

            $movement = $product->stockMovements()->create([
                'user_id' => $user->id,
                'quantity_delta' => $delta,
                'unit_cost' => $product->average_cost,
                'type' => $data['type'],
                'justification' => $data['justification'] ?? null,
            ]);

            $movement->adjustment()->create([
                'reason_id' => $data['reason_id'],
                'previous_quantity' => $previousQuantity,
                'counted_quantity' => $newQuantity,
            ]);
        }, 3);
    }
}
