<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use App\Models\PaymentAllocation;
use App\Models\Sale;
use Brick\Math\BigDecimal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(): View
    {
        return view('customers.index', [
            'customers' => Customer::query()->orderBy('name')->orderBy('id')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('customers.form', ['customer' => new Customer]);
    }

    public function store(CustomerRequest $request): RedirectResponse
    {
        $customer = Customer::query()->create($request->validated());

        return to_route('customers.show', $customer)->with('status', 'Cliente cadastrado.');
    }

    public function show(Request $request, Customer $customer): View
    {
        $section = $request->query('secao');
        if (! in_array($section, ['compras', 'aberto', 'pagamentos', 'cadastro'], true)) {
            $section = 'geral';
        }

        $completedSales = $customer->sales()->where('status', Sale::Completed)
            ->select('sales.total')->withPaidTotal();
        $summary = DB::query()->fromSub($completedSales, 'customer_sales')
            ->selectRaw('COALESCE(SUM(total), 0) as purchased')
            ->selectRaw('COALESCE(SUM(paid_total), 0) as received')
            ->selectRaw('COALESCE(SUM(total - COALESCE(paid_total, 0)), 0) as outstanding')
            ->selectRaw('COUNT(CASE WHEN total > COALESCE(paid_total, 0) THEN 1 END) as open_sales')
            ->first();

        $sales = $customer->sales()->withPaidTotal()->orderByDesc('sold_on')->orderByDesc('id');
        if ($section === 'aberto') {
            $sales->where('status', Sale::Completed)
                ->where('sales.total', '>', PaymentAllocation::query()->selectRaw('COALESCE(SUM(amount), 0)')
                    ->whereColumn('sale_id', 'sales.id'));
        }

        return view('customers.show', [
            'customer' => $customer,
            'section' => $section,
            'sales' => in_array($section, ['geral', 'compras', 'aberto'], true) ? $sales->paginate(20)->withQueryString() : null,
            'payments' => in_array($section, ['geral', 'pagamentos'], true)
                ? $customer->payments()->with('allocations')->orderByDesc('paid_on')->orderByDesc('id')
                    ->paginate(10, pageName: 'pagamentos')->withQueryString()
                : null,
            'paymentToken' => (string) Str::uuid(),
            'purchasedTotal' => (string) BigDecimal::of($summary->purchased)->toScale(2),
            'paidTotal' => (string) BigDecimal::of($summary->received)->toScale(2),
            'outstandingTotal' => (string) BigDecimal::of($summary->outstanding)->toScale(2),
            'openSalesCount' => $summary->open_sales,
        ]);
    }

    public function edit(Customer $customer): View
    {
        return view('customers.form', compact('customer'));
    }

    public function update(CustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated());

        return to_route('customers.show', $customer)->with('status', 'Cliente atualizado.');
    }
}
