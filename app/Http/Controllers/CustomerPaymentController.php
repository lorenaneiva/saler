<?php

namespace App\Http\Controllers;

use App\Actions\RegisterCustomerPayment;
use App\Http\Requests\CustomerPaymentRequest;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;

class CustomerPaymentController extends Controller
{
    public function store(CustomerPaymentRequest $request, Customer $customer, RegisterCustomerPayment $registerPayment): RedirectResponse
    {
        $registerPayment->handle($customer, $request->user(), $request->validated());

        return to_route('customers.show', $customer)->with('status', 'Pagamento registrado e saldo atualizado.');
    }
}
