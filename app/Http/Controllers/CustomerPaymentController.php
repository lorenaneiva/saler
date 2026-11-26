<?php

namespace App\Http\Controllers;

use App\Actions\PlanPaymentDistribution;
use App\Actions\RegisterCustomerPayment;
use App\Http\Requests\CustomerPaymentRequest;
use App\Models\Customer;
use Brick\Math\BigDecimal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerPaymentController extends Controller
{
    public function preview(Request $request, Customer $customer, PlanPaymentDistribution $planDistribution): View
    {
        $amount = $request->query('amount');
        $isValidAmount = is_string($amount) && preg_match('/^\d{1,18}(\.\d{1,2})?$/', $amount) === 1 && BigDecimal::of($amount)->isPositive();

        return view('customers.payment-distribution', [
            'distribution' => $isValidAmount ? $planDistribution->handle($customer, BigDecimal::of($amount)) : null,
        ]);
    }

    public function store(CustomerPaymentRequest $request, Customer $customer, RegisterCustomerPayment $registerPayment): RedirectResponse
    {
        $registerPayment->handle($customer, $request->user(), $request->validated());

        return to_route('customers.show', $customer)->with('status', 'Pagamento registrado e saldo atualizado.');
    }
}
