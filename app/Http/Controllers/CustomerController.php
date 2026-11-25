<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use App\Models\Sale;
use Brick\Math\BigDecimal;
use Illuminate\Http\RedirectResponse;
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
        $outstandingTotal = $customer->sales()->where('status', Sale::Completed)->sum('total');

        return view('customers.show', [
            'customer' => $customer,
            'sales' => $customer->sales()->orderByDesc('sold_on')->orderByDesc('id')->paginate(20),
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
