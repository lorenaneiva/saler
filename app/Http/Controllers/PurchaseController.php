<?php

namespace App\Http\Controllers;

use App\Actions\CancelPurchase;
use App\Actions\ConfirmPurchase;
use App\Actions\LoadAutocompleteOptions;
use App\Actions\RegisterPurchase;
use App\Http\Requests\ListSearchRequest;
use App\Http\Requests\PurchaseRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function index(ListSearchRequest $request): View
    {
        return view('purchases.index', [
            'purchases' => $request->filter(Purchase::query(), ['id', 'supplier.name', 'items.product.name'])
                ->with('supplier:id,name')->orderByDesc('purchased_on')->orderByDesc('id')->paginate(self::PerPage)->withQueryString(),
        ]);
    }

    public function create(Request $request, LoadAutocompleteOptions $options): View
    {
        $previousItems = $request->old('items', []);
        $productIds = is_array($previousItems) ? collect($previousItems)->pluck('product_id')->all() : [];

        return view('purchases.create', [
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->orderBy('id')->get(['id', 'name']),
            'suppliers' => Supplier::query()->where('is_active', true)->orderBy('name')->orderBy('id')->get(['id', 'name']),
            'products' => $options->handle(Product::query()->where('is_active', true), $productIds, ['id', 'name']),
        ]);
    }

    public function store(PurchaseRequest $request, RegisterPurchase $registerPurchase): RedirectResponse
    {
        $purchase = $registerPurchase->handle($request->user(), $request->validated());

        return to_route('purchases.show', $purchase)->with('status', 'Compra registrada e estoque atualizado.');
    }

    public function show(ListSearchRequest $request, Purchase $purchase): View
    {
        return view('purchases.show', ['purchase' => $purchase->load([
            'supplier:id,name',
            'items' => fn ($items) => $request->filter($items, ['product.name', 'product.barcode'], 'q_itens')
                ->with('product:id,name'),
        ])->loadCount('items')]);
    }

    public function confirm(Request $request, Purchase $purchase, ConfirmPurchase $confirmPurchase): RedirectResponse
    {
        $confirmPurchase->handle($purchase, $request->user());

        return to_route('purchases.show', $purchase)->with('status', 'Compra confirmada e estoque atualizado.');
    }

    public function cancel(Request $request, Purchase $purchase, CancelPurchase $cancelPurchase): RedirectResponse
    {
        $cancelPurchase->handle($purchase, $request->user());

        return to_route('purchases.show', $purchase)->with('status', 'Compra cancelada.');
    }
}
