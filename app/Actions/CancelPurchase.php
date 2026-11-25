<?php

namespace App\Actions;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelPurchase
{
    public function handle(Purchase $purchase, User $user): void
    {
        DB::transaction(function () use ($purchase, $user): void {
            $purchase = Purchase::query()->lockForUpdate()->findOrFail($purchase->id);

            if ($purchase->status === Purchase::Cancelled) {
                return;
            }

            if ($purchase->status === Purchase::Confirmed) {
                $this->reverseEntries($purchase, $user);
            }

            $purchase->forceFill(['status' => Purchase::Cancelled])->save();
        }, 3);
    }

    private function reverseEntries(Purchase $purchase, User $user): void
    {
        $itemIds = $purchase->items()->pluck('id');
        $entries = StockMovement::query()->whereIn('purchase_item_id', $itemIds)
            ->where('type', StockMovement::PurchaseEntry)->get();

        if ($entries->isEmpty() || $entries->count() !== $itemIds->count()) {
            throw ValidationException::withMessages(['purchase' => 'As entradas da compra não foram localizadas para estorno.']);
        }

        $products = Product::query()->whereKey($entries->pluck('product_id')->all())
            ->orderBy('id')->lockForUpdate()->get()->keyBy('id');

        foreach ($entries as $entry) {
            $product = $products->get($entry->product_id);
            $newQuantity = $product->stock_quantity - $entry->quantity_delta;

            if ($newQuantity < 0) {
                throw ValidationException::withMessages(['purchase' => "Estoque insuficiente para estornar o produto {$product->name}."]);
            }

            $product->stock_quantity = $newQuantity;
            $product->save();

            $product->stockMovements()->create([
                'purchase_item_id' => $entry->purchase_item_id,
                'user_id' => $user->id,
                'quantity_delta' => -$entry->quantity_delta,
                'unit_cost' => $product->average_cost,
                'type' => StockMovement::PurchaseReversal,
            ]);
        }
    }
}
