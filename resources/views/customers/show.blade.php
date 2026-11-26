@extends('layout')

@section('title', 'Detalhes do cliente | Saler')

@section('content')
    @php($editForm = 'customer-'.$customer->id)
    <div data-inline-editor class="grid gap-4">
        <x-ui.page-header :title="$customer->name" :back="route('customers.index')" back-label="Clientes">
            <x-slot:actions>
                <x-ui.inline-actions :id="$editForm" :action="route('customers.update', $customer)" label="Editar cliente" text="Editar cliente" />
                @if ($outstandingTotal !== '0.00')
                    <button type="button" class="button-primary" data-dialog-open="customer-payment">Registrar pagamento</button>
                @endif
            </x-slot:actions>
        </x-ui.page-header>

        <section class="grid gap-2" aria-labelledby="customer-data-title">
            <h2 id="customer-data-title" class="text-base font-semibold">Dados cadastrais</h2>
            <dl class="panel grid gap-4 p-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ([
                    'name' => ['Nome', 'text', 255, 'name', true],
                    'phone' => ['Telefone', 'tel', 30, 'tel', true],
                    'cpf' => ['CPF (opcional)', 'text', 14, 'off', false],
                    'email' => ['E-mail (opcional)', 'email', 255, 'email', false],
                    'postal_code' => ['CEP (opcional)', 'text', 9, 'postal-code', false],
                ] as $field => [$label, $type, $maxLength, $autocomplete, $required])
                    <div class="min-w-0">
                        <dt data-inline-display class="text-xs font-medium text-slate-600">{{ $label }}</dt>
                        <dd class="wrap-anywhere text-sm">
                            <x-ui.inline-field :form="$editForm" :name="$field" :label="$label" :value="$customer->{$field}"
                                :type="$type" :maxlength="$maxLength" :autocomplete="$autocomplete" :required="$required">
                                @if ($field === 'name')
                                    {{ $customer->name }}
                                @else
                                    <x-ui.contact :value="$customer->{$field}" :kind="$field" />
                                @endif
                            </x-ui.inline-field>
                        </dd>
                    </div>
                @endforeach
            </dl>
        </section>
    </div>

    <dl class="metrics" aria-label="Resumo financeiro">
        <div class="metric">
            <dt>Total comprado</dt>
            <dd><x-ui.money :value="$purchasedTotal" /></dd>
        </div>
        <div class="metric">
            <dt>Total pago</dt>
            <dd><x-ui.money :value="$paidTotal" /></dd>
        </div>
        <div class="metric metric-emphasis">
            <dt>Saldo em aberto</dt>
            <dd><x-ui.money :value="$outstandingTotal" /></dd>
        </div>
        <div class="metric">
            <dt>Vendas em aberto</dt>
            <dd>{{ $openSalesCount }}</dd>
        </div>
    </dl>

    <nav class="section-tabs" aria-label="Seções do cliente">
        @foreach (['compras' => 'Compras', 'aberto' => 'Contas em aberto', 'pagamentos' => 'Pagamentos', 'cadastro' => 'Cadastro'] as $key => $label)
            <a href="{{ $key === 'compras' ? route('customers.show', $customer) : route('customers.show', [$customer, 'secao' => $key]) }}"
                @if ($section === $key) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>

    @if ($section === 'compras' || $section === 'aberto')
        @include('customers.purchase-history', ['sales' => $sales, 'section' => $section])
    @elseif ($section === 'pagamentos')
        @include('customers.payment-history', ['payments' => $payments])
    @endif

    @if ($outstandingTotal !== '0.00' || $errors->any())
        <x-ui.dialog id="customer-payment" title="Registrar pagamento" :auto-open="$errors->any()" drawer inline-fallback>
            @include('customers.payment-form')
        </x-ui.dialog>
    @endif
@endsection
