<?php

namespace App\Models;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'category_id', 'supplier_id', 'sale_price', 'barcode'])]
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

    public function profitMargin(): ?string
    {
        $salePrice = BigDecimal::of($this->sale_price);

        if ($salePrice->isZero()) {
            return null;
        }

        return (string) $salePrice->minus($this->average_cost)
            ->multipliedBy(100)
            ->dividedBy($salePrice, 2, RoundingMode::HalfUp);
    }
}
