<?php

namespace App\Actions;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Creates a catalogue product and, optionally, its first purchase through the regular purchase flow,
 * all in one transaction. Repeated submissions of the same form create nothing new.
 */
class CreateProduct
{
    public function __construct(private RegisterPurchase $registerPurchase) {}

    /**
     * @param  array{name: string, category_id: int, supplier_id?: ?int, sale_price: numeric-string, barcode?: ?string, submission_token?: ?string}  $product
     * @param  ?array{purchased_on: string, quantity: int|numeric-string, unit_cost: numeric-string, discount?: ?numeric-string}  $initialPurchase
     */
    public function handle(User $user, array $product, ?array $initialPurchase = null): Product
    {
        $token = $product['submission_token'] ?? null;

        try {
            return DB::transaction(function () use ($user, $product, $initialPurchase, $token): Product {
                if ($token !== null && ($existing = Product::query()->where('submission_token', $token)->first())) {
                    return $existing;
                }

                $created = Product::query()->create($product);

                if ($initialPurchase !== null) {
                    $this->registerPurchase->handle($user, [
                        'supplier_id' => $created->supplier_id,
                        'purchased_on' => $initialPurchase['purchased_on'],
                        'submission_token' => $token,
                        'items' => [[
                            'product_id' => $created->id,
                            'quantity' => $initialPurchase['quantity'],
                            'unit_cost' => $initialPurchase['unit_cost'],
                            'discount' => $initialPurchase['discount'] ?? null,
                        ]],
                    ]);
                }

                return $created;
            }, 3);
        } catch (UniqueConstraintViolationException $exception) {
            return $token !== null ? Product::query()->where('submission_token', $token)->firstOr(fn () => throw $exception) : throw $exception;
        }
    }
}
