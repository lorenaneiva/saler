<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['product_id', 'quantity', 'unit_cost', 'discount', 'total'])]
class PurchaseItem extends Model
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['quantity' => 'integer', 'unit_cost' => 'decimal:2', 'discount' => 'decimal:2', 'total' => 'decimal:2'];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
