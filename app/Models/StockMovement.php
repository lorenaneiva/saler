<?php

namespace App\Models;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Query\Expression;

#[Fillable(['product_id', 'purchase_item_id', 'sale_item_id', 'user_id', 'quantity_delta', 'unit_cost', 'type', 'justification'])]
class StockMovement extends Model
{
    public const PurchaseEntry = 'purchase_entry';

    public const PurchaseReversal = 'purchase_reversal';

    public const SaleExit = 'sale_exit';

    public const ManualEntry = 'manual_entry';

    public const ManualExit = 'manual_exit';

    public const Adjustment = 'adjustment';

    public const TypeLabels = [
        self::PurchaseEntry => 'Entrada de compra',
        self::PurchaseReversal => 'Estorno de compra',
        self::SaleExit => 'Venda',
        self::ManualEntry => 'Entrada manual',
        self::ManualExit => 'Perda de estoque',
        self::Adjustment => 'Ajuste manual',
    ];

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
        return self::TypeLabels[$this->type];
    }

    /**
     * Stock on hand right after the movement: the running total of the product's movements in the order they happened
     * (time, then id), so it never depends on how the history is filtered, sorted or paginated.
     */
    public static function balanceAfterColumn(): Expression
    {
        return new Expression('(SELECT SUM(earlier.quantity_delta) FROM stock_movements AS earlier'
            .' WHERE earlier.product_id = stock_movements.product_id'
            .' AND (earlier.created_at, earlier.id) <= (stock_movements.created_at, stock_movements.id))');
    }

    /** @param Builder<StockMovement> $query */
    #[Scope]
    protected function withBalanceAfter(Builder $query): void
    {
        $query->select('stock_movements.*')->selectRaw(self::balanceAfterColumn()->getValue($query->getGrammar()).' AS balance_after');
    }

    /** Price the product was actually sold for in this movement; null when no sale is linked. */
    public function salePrice(): ?string
    {
        return $this->saleItem?->unit_price;
    }

    /** Sortable {@see lossValue()}: null for every movement that is not a stock loss. */
    public static function lossColumn(): Expression
    {
        return new Expression("CASE WHEN stock_movements.type = '".self::ManualExit."' THEN stock_movements.unit_cost * -stock_movements.quantity_delta END");
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
        return ['quantity_delta' => 'integer', 'unit_cost' => 'decimal:4', 'balance_after' => 'integer'];
    }
}
