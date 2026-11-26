<?php

namespace App\Actions;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\User;
use Brick\Math\BigDecimal;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Registers a purchase with its items, creating catalogue products for new items, and confirms it
 * so stock and average cost are updated by {@see ConfirmPurchase}. Repeated submissions are recorded once.
 */
class RegisterPurchase
{
    public function __construct(private ConfirmPurchase $confirmPurchase) {}

    /**
     * @param  array{
     *     supplier_id: int|numeric-string,
     *     purchased_on: string,
     *     submission_token?: ?string,
     *     items: list<array{product_id?: ?int, new_product?: array{name: string, category_id: int, sale_price: numeric-string, barcode?: ?string}, quantity: int|numeric-string, unit_cost: numeric-string, discount?: ?numeric-string}>
     * }  $data
     */
    public function handle(User $user, array $data): Purchase
    {
        $token = $data['submission_token'] ?? null;

        try {
            return DB::transaction(function () use ($user, $data, $token): Purchase {
                if ($token !== null && ($existing = Purchase::query()->where('submission_token', $token)->first())) {
                    return $existing;
                }

                $items = array_map(fn (array $item): array => [
                    ...$item,
                    'discount' => $item['discount'] ?? '0.00',
                    'total' => (string) BigDecimal::of($item['unit_cost'])->multipliedBy($item['quantity'])->minus($item['discount'] ?? '0.00'),
                ], $data['items']);

                $purchase = Purchase::query()->create([
                    'supplier_id' => $data['supplier_id'],
                    'user_id' => $user->id,
                    'purchased_on' => $data['purchased_on'],
                    'total' => (string) array_reduce($items, fn (BigDecimal $total, array $item): BigDecimal => $total->plus($item['total']), BigDecimal::zero()),
                    'submission_token' => $token,
                ]);

                foreach ($items as $item) {
                    if (isset($item['new_product'])) {
                        $item['product_id'] = Product::query()->create([...$item['new_product'], 'supplier_id' => $purchase->supplier_id])->id;
                        unset($item['new_product']);
                    }

                    $purchase->items()->create($item);
                }

                $this->confirmPurchase->handle($purchase, $user);

                return $purchase;
            }, 3);
        } catch (UniqueConstraintViolationException $exception) {
            return $token !== null ? Purchase::query()->where('submission_token', $token)->firstOr(fn () => throw $exception) : throw $exception;
        }
    }
}
