<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use App\Models\Sale;
use Brick\Math\BigDecimal;
use Illuminate\Http\RedirectResponse;
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

    public function show(Customer $customer): View
    {
        $completedSales = $customer->sales()->where('status', Sale::Completed)
            ->select('sales.total')->withPaidTotal();
        $outstandingTotal = DB::query()->fromSub($completedSales, 'customer_sales')
            ->selectRaw('COALESCE(SUM(total - COALESCE(paid_total, 0)), 0) as outstanding')
            ->value('outstanding');

        return view('customers.show', [
            'customer' => $customer,
            'sales' => $customer->sales()->withPaidTotal()->orderByDesc('sold_on')->orderByDesc('id')->paginate(20),
            'payments' => $customer->payments()->with('allocations')->orderByDesc('paid_on')->orderByDesc('id')
                ->paginate(10, pageName: 'pagamentos'),
            'paymentToken' => (string) Str::uuid(),
            'outstandingTotal' => (string) BigDecimal::of($outstandingTotal)->toScale(2),
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
