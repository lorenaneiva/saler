<?php

namespace Database\Seeders;

use App\Models\StockAdjustmentReason;
use Illuminate\Database\Seeder;

class StockAdjustmentReasonSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Contagem física', 'Perda ou avaria', 'Correção de lançamento'] as $name) {
            StockAdjustmentReason::query()->firstOrCreate(['name' => $name]);
        }
    }
}
