<?php

namespace App\Models;

use Database\Factories\StockAdjustmentReasonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name'])]
class StockAdjustmentReason extends Model
{
    /** @use HasFactory<StockAdjustmentReasonFactory> */
    use HasFactory;

    protected $table = 'motivos';
}
