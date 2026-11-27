<?php

namespace App\Models;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Expression;

#[Fillable(['name', 'category_id', 'supplier_id', 'sale_price', 'barcode', 'submission_token'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saved(function (Product $product): void {
            if ($product->wasChanged('category_id') || $product->wasRecentlyCreated) {
                Category::query()->whereKey($product->category_id)
                    ->where('has_had_products', false)
                    ->update(['has_had_products' => true]);
            }
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'sale_price' => 'decimal:2',
            'average_cost' => 'decimal:4',
            'stock_quantity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<Supplier, $this> */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /** @return HasMany<StockMovement, $this> */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Adds `has_registered_cost`, telling whether any purchase ever brought this product into stock.
     *
     * @param  Builder<Product>  $query
     */
    #[Scope]
    protected function withRegisteredCost(Builder $query): void
    {
        $query->withExists(['stockMovements as has_registered_cost' => fn (Builder $movements) => $movements->where('type', StockMovement::PurchaseEntry)]);
    }

    /** Sortable average cost: null while no purchase was recorded, so unknown costs sort last instead of as zero. */
    public static function registeredCostColumn(): Expression
    {
        return new Expression(self::registeredCostSql());
    }

    /** Sortable profit margin, null whenever {@see profitMargin()} cannot be computed. */
    public static function profitMarginColumn(): Expression
    {
        return new Expression('(products.sale_price - ('.self::registeredCostSql().')) * 100 / NULLIF(products.sale_price, 0)');
    }

    private static function registeredCostSql(): string
    {
        return "CASE WHEN EXISTS (SELECT 1 FROM stock_movements WHERE stock_movements.product_id = products.id AND stock_movements.type = '"
            .StockMovement::PurchaseEntry."') THEN products.average_cost END";
    }

    /** The average cost is only known once a purchase was recorded; before that it is unknown, not zero. */
    public function hasRegisteredCost(): bool
    {
        if ($this->getAttribute('has_registered_cost') !== null) {
            return (bool) $this->getAttribute('has_registered_cost');
        }

        return $this->exists && $this->stockMovements()->where('type', StockMovement::PurchaseEntry)->exists();
    }

    public function grossProfit(): ?BigDecimal
    {
        if (! $this->hasRegisteredCost()) {
            return null;
        }

        return BigDecimal::of($this->sale_price)->minus($this->average_cost)->toScale(2, RoundingMode::HalfUp);
    }

    /** Margin on the sale price, (price − cost) ÷ price × 100; not markup over cost. */
    /** Stock on hand valued at the average cost; unknown while no purchase recorded the cost. */
    public function stockValue(): ?string
    {
        if (! $this->hasRegisteredCost()) {
            return null;
        }

        return (string) BigDecimal::of($this->average_cost)
            ->multipliedBy(max($this->stock_quantity, 0))
            ->toScale(2, RoundingMode::HalfUp);
    }

    public function profitMargin(): ?string
    {
        $salePrice = BigDecimal::of($this->sale_price);

        if ($salePrice->isZero() || ! $this->hasRegisteredCost()) {
            return null;
        }

        return (string) $salePrice->minus($this->average_cost)
            ->multipliedBy(100)
            ->dividedBy($salePrice, 2, RoundingMode::HalfUp);
    }
}
