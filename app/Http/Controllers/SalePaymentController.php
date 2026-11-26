<?php

namespace App\Http\Controllers;

use App\Actions\RegisterCustomerPayment;
use App\Http\Requests\CustomerPaymentRequest;
use App\Models\Sale;
use Illuminate\Http\RedirectResponse;

class SalePaymentController extends Controller
{
    public function store(CustomerPaymentRequest $request, Sale $sale, RegisterCustomerPayment $registerPayment): RedirectResponse
    {
        $registerPayment->handle($sale->customer, $request->user(), $request->validated(), $sale);

        return back(fallback: route('sales.show', $sale))->with('status', 'Pagamento registrado nesta venda e saldo atualizado.');
    }
}
