<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['product_id', 'purchase_item_id', 'user_id', 'quantity_delta', 'unit_cost', 'type'])]
class StockMovement extends Model
{
    public const PurchaseEntry = 'purchase_entry';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['quantity_delta' => 'integer', 'unit_cost' => 'decimal:4'];
    }
}
