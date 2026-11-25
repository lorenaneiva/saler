<?php

namespace App\Models;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['product_id', 'purchase_item_id', 'sale_item_id', 'user_id', 'quantity_delta', 'unit_cost', 'type', 'justification'])]
class StockMovement extends Model
{
    public const PurchaseEntry = 'purchase_entry';

    public const PurchaseReversal = 'purchase_reversal';

    public const SaleExit = 'sale_exit';

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

    /** @return BelongsTo<SaleItem, $this> */
    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }

    /** @return HasOne<StockAdjustment, $this> */
    public function adjustment(): HasOne
    {
        return $this->hasOne(StockAdjustment::class);
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::PurchaseEntry => 'Entrada de compra',
            self::PurchaseReversal => 'Estorno de compra',
            self::SaleExit => 'Venda',
            self::ManualEntry => 'Entrada manual',
            self::ManualExit => 'Perda de estoque',
            self::Adjustment => 'Ajuste manual',
        };
    }

    public function lossValue(): ?string
    {
        if ($this->type !== self::ManualExit) {
            return null;
        }

        return (string) BigDecimal::of($this->unit_cost)
            ->multipliedBy(-$this->quantity_delta)
            ->toScale(2, RoundingMode::HalfUp);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['quantity_delta' => 'integer', 'unit_cost' => 'decimal:4'];
    }
}
