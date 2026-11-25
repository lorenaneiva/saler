<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['product_id', 'purchase_item_id', 'user_id', 'quantity_delta', 'unit_cost', 'type', 'justification'])]
class StockMovement extends Model
{
    public const PurchaseEntry = 'purchase_entry';

    public const ManualEntry = 'manual_entry';

    public const ManualExit = 'manual_exit';

    public const Adjustment = 'adjustment';

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<PurchaseItem, $this> */
    public function purchaseItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseItem::class);
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::PurchaseEntry => 'Entrada de compra',
            self::ManualEntry => 'Entrada manual',
            self::ManualExit => 'Saída manual',
            self::Adjustment => 'Ajuste manual',
        };
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['quantity_delta' => 'integer', 'unit_cost' => 'decimal:4'];
    }
}
