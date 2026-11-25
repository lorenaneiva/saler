<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['reason_id', 'previous_quantity', 'counted_quantity'])]
class StockAdjustment extends Model
{
    protected $table = 'ajustes_estoque';

    /** @return BelongsTo<StockAdjustmentReason, $this> */
    public function reason(): BelongsTo
    {
        return $this->belongsTo(StockAdjustmentReason::class, 'reason_id');
    }
}
