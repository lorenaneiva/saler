<?php

namespace App\Actions;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\StockMovement;
use App\Models\User;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmPurchase
{
    public function handle(Purchase $purchase, User $user): void
    {
        DB::transaction(function () use ($purchase, $user): void {
            $lockedPurchase = Purchase::query()->lockForUpdate()->findOrFail($purchase->id);

            if ($lockedPurchase->status === Purchase::Confirmed) {
                return;
            }

            if ($lockedPurchase->status !== Purchase::Pending) {
                throw ValidationException::withMessages(['purchase' => 'Somente compras pendentes podem ser confirmadas.']);
            }

            $items = $lockedPurchase->items()->get();

            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['purchase' => 'Adicione itens antes de confirmar a compra.']);
            }

            $products = Product::query()->whereKey($items->pluck('product_id')->all())
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            foreach ($items as $item) {
                $product = $products->get($item->product_id);
                $newQuantity = $product->stock_quantity + $item->quantity;

                if ($newQuantity > 2147483647) {
                    throw ValidationException::withMessages(['purchase' => 'O saldo de estoque ultrapassaria o limite permitido.']);
                }

                $effectiveUnitCost = BigDecimal::of($item->total)->dividedBy($item->quantity, 4, RoundingMode::HalfUp);
                $previousInventoryValue = BigDecimal::of($product->average_cost)->multipliedBy($product->stock_quantity);
                $newAverageCost = $previousInventoryValue->plus($item->total)->dividedBy($newQuantity, 4, RoundingMode::HalfUp);

                $product->forceFill(['stock_quantity' => $newQuantity, 'average_cost' => (string) $newAverageCost])->save();

                StockMovement::query()->create([
                    'product_id' => $product->id,
                    'purchase_item_id' => $item->id,
                    'user_id' => $user->id,
                    'quantity_delta' => $item->quantity,
                    'unit_cost' => (string) $effectiveUnitCost,
                    'type' => StockMovement::PurchaseEntry,
                ]);
            }

            $lockedPurchase->forceFill(['status' => Purchase::Confirmed, 'confirmed_at' => now()])->save();
        }, 3);
    }
}
