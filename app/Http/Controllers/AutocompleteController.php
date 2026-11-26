<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListSearchRequest;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class AutocompleteController extends Controller
{
    public function __invoke(ListSearchRequest $request, string $source): JsonResponse
    {
        $query = match ($source) {
            'customers' => Customer::query(),
            'sale-products' => Product::query()->where('is_active', true)->where('stock_quantity', '>', 0),
            'purchase-products' => Product::query()->where('is_active', true),
            default => abort(404),
        };
        $pattern = '%'.addcslashes($request->term(), '%_\\').'%';
        $query->where(function (Builder $matches) use ($pattern, $source): void {
            $combiningMarks = "[\u{0300}-\u{036f}]";
            $matches->whereRaw(
                "regexp_replace(normalize(lower(name), NFD), ?, '', 'g') LIKE regexp_replace(normalize(lower(?), NFD), ?, '', 'g')",
                [$combiningMarks, $pattern, $combiningMarks],
            );
            if ($source !== 'customers') {
                $matches->orWhereLike('barcode', $pattern);
            }
        });
        $columns = $source === 'sale-products' ? ['id', 'name', 'sale_price', 'stock_quantity'] : ['id', 'name'];
        $results = $query->orderBy('name')->orderBy('id')
            ->simplePaginate(30, $columns, page: max(1, min(10000, $request->integer('page', 1))));

        return response()->json([
            'options' => $results->getCollection()->map(fn (Customer|Product $record): array => [
                'value' => (string) $record->id,
                'label' => $source === 'sale-products'
                    ? $record->name.' · R$ '.number_format((float) $record->sale_price, 2, ',', '.').' · '.$record->stock_quantity.' em estoque'
                    : $record->name,
            ]),
            'next_page' => $results->hasMorePages() ? $results->currentPage() + 1 : null,
        ]);
    }
}
