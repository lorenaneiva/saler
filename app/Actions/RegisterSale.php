<?php

namespace App\Actions;

use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegisterSale
{
    /** @param array{customer_id: int|numeric-string, sold_on: string, items: list<array{product_id: int|numeric-string, quantity: int|numeric-string}>, discount?: int|float|numeric-string|null, notes?: ?string} $data */
    public function handle(User $user, array $data): Sale
    {
        return DB::transaction(function () use ($user, $data): Sale {
            $products = Product::query()->whereKey(array_column($data['items'], 'product_id'))
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $subtotal = BigDecimal::zero();
            $items = [];

            foreach ($data['items'] as $index => $item) {
                $product = $products->get((int) $item['product_id']);
                $quantity = (int) $item['quantity'];

                if (! $product || ! $product->is_active) {
                    throw ValidationException::withMessages(["items.$index.product_id" => 'Selecione um produto ativo.']);
                }

                if ($quantity > $product->stock_quantity) {
                    throw ValidationException::withMessages(["items.$index.quantity" => "Estoque insuficiente para vender {$product->name}."]);
                }

                $itemSubtotal = BigDecimal::of($product->sale_price)->multipliedBy($quantity);
                $subtotal = $subtotal->plus($itemSubtotal);
                $items[] = [
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $product->sale_price,
                    'unit_cost' => $product->average_cost,
                    'subtotal' => (string) $itemSubtotal,
                ];
            }

            $discount = BigDecimal::of($data['discount'] ?? 0);

            if ($discount->isGreaterThan($subtotal)) {
                throw ValidationException::withMessages(['discount' => 'O desconto não pode ultrapassar o subtotal da venda.']);
            }

            $sale = Sale::query()->create([
                'customer_id' => $data['customer_id'],
                'user_id' => $user->id,
                'sold_on' => $data['sold_on'],
                'subtotal' => (string) $subtotal,
                'discount' => (string) $discount,
                'total' => (string) $subtotal->minus($discount),
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($items as $item) {
                $saleItem = $sale->items()->create($item);
                $product = $products->get($saleItem->product_id);
                $product->stock_quantity -= $saleItem->quantity;
                $product->save();

                $product->stockMovements()->create([
                    'sale_item_id' => $saleItem->id,
                    'user_id' => $user->id,
                    'quantity_delta' => -$saleItem->quantity,
                    'unit_cost' => $saleItem->unit_cost,
                    'type' => StockMovement::SaleExit,
                ]);
            }

            return $sale;
        }, 3);
    }
}
