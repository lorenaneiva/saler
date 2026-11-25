<?php

namespace App\Http\Controllers;

use App\Actions\ConfirmPurchase;
use App\Http\Requests\PurchaseRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use Brick\Math\BigDecimal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function index(): View
    {
        return view('purchases.index', [
            'purchases' => Purchase::query()->with('supplier:id,name')->orderByDesc('purchased_on')->orderByDesc('id')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('purchases.create', [
            'categories' => Category::query()->orderBy('name')->orderBy('id')->get(['id', 'name']),
            'suppliers' => Supplier::query()->where('is_active', true)->orderBy('name')->orderBy('id')->get(['id', 'name']),
            'products' => Product::query()->where('is_active', true)->orderBy('name')->orderBy('id')->get(['id', 'name']),
        ]);
    }

    public function store(PurchaseRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $total = BigDecimal::zero();
        $items = [];

        foreach ($data['items'] as $item) {
            $item['discount'] ??= '0.00';
            $itemTotal = BigDecimal::of($item['unit_cost'])->multipliedBy($item['quantity'])->minus($item['discount']);
            $item['total'] = (string) $itemTotal;
            $items[] = $item;
            $total = $total->plus($itemTotal);
        }

        $purchase = DB::transaction(function () use ($request, $data, $total, $items): Purchase {
            $purchase = Purchase::query()->create([
                'supplier_id' => $data['supplier_id'],
                'user_id' => $request->user()->id,
                'purchased_on' => $data['purchased_on'],
                'total' => (string) $total,
            ]);

            foreach ($items as $item) {
                if (isset($item['new_product'])) {
                    $product = Product::query()->create([
                        ...$item['new_product'],
                        'supplier_id' => $purchase->supplier_id,
                    ]);

                    $item['product_id'] = $product->id;
                    unset($item['new_product']);
                }

                $purchase->items()->create($item);
            }

            return $purchase;
        });

        return to_route('purchases.show', $purchase)->with('status', 'Compra registrada e aguardando confirmação.');
    }

    public function show(Purchase $purchase): View
    {
        return view('purchases.show', ['purchase' => $purchase->load(['supplier:id,name', 'items.product:id,name'])]);
    }

    public function confirm(Request $request, Purchase $purchase, ConfirmPurchase $confirmPurchase): RedirectResponse
    {
        $confirmPurchase->handle($purchase, $request->user());

        return to_route('purchases.show', $purchase)->with('status', 'Compra confirmada e estoque atualizado.');
    }

    public function cancel(Purchase $purchase): RedirectResponse
    {
        $updated = Purchase::query()->whereKey($purchase->id)->where('status', Purchase::Pending)
            ->update(['status' => Purchase::Cancelled]);

        if ($updated === 0 && $purchase->refresh()->status !== Purchase::Cancelled) {
            throw ValidationException::withMessages(['purchase' => 'Somente compras pendentes podem ser canceladas.']);
        }

        return to_route('purchases.show', $purchase)->with('status', 'Compra cancelada.');
    }
}
