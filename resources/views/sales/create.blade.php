@extends('layout')

@section('title', 'Nova venda | Saler')

@section('content')
    @php
        $previousItems = old('items');
        $rows = is_array($previousItems) && $previousItems !== [] ? array_slice(array_values($previousItems), 0, 50) : [[]];
        $date = old('sold_on', now()->toDateString());
        $discount = old('discount');
        $notes = old('notes');
    @endphp

    <x-ui.page-header title="Nova venda" description="Selecione o cliente e os produtos que serão vendidos." :back="route('sales.index')" back-label="Vendas" />
    <div class="grid gap-4" data-line-editor>

        <form method="POST" action="{{ route('sales.store') }}" class="panel form-panel">
            @csrf
            <x-ui.errors />

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <label for="sale-customer" class="font-medium">Cliente <x-required-mark /></label>
                    <select id="sale-customer" name="customer_id" class="field" required data-autocomplete-url="{{ route('autocomplete.options', 'customers') }}">
                        <option value="">Selecione</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>{{ $customer->name }}</option>
                        @endforeach
                    </select>
                    @if ($customers->isEmpty())
                        <a href="{{ route('customers.create') }}" class="w-fit text-sm text-brand underline">Cadastrar cliente</a>
                    @endif
                </div>
                <label class="grid content-start gap-2 font-medium">
                    <span>Data da venda <x-required-mark /></span>
                    <input name="sold_on" class="field" type="date" required value="{{ is_string($date) ? $date : '' }}">
                </label>
            </div>

            <fieldset class="grid gap-3">
                <legend class="mb-3 text-lg font-semibold">Itens da venda</legend>
                @if ($products->isEmpty())
                    <p class="text-sm text-slate-600">Nenhum produto ativo com saldo disponível.</p>
                    <a href="{{ route('products.index') }}" class="w-fit text-sm text-brand underline">Consultar estoque</a>
                @endif
                <div data-line-items class="grid gap-3">
                    @foreach ($rows as $index => $item)
                        @include('sales.item-fields', ['index' => $index, 'item' => is_array($item) ? $item : []])
                    @endforeach
                </div>
                <button type="button" data-line-add class="button-secondary disabled:opacity-50">Adicionar produto</button>
            </fieldset>

            <div class="grid gap-4 sm:grid-cols-2">
                <label class="grid content-start gap-2 font-medium">
                    <span>Desconto total (R$, opcional)</span>
                    <input name="discount" type="number" class="field" min="0" step="0.01" value="{{ is_scalar($discount) ? $discount : '' }}">
                </label>
                <label class="grid gap-2 font-medium">
                    <span>Observação (opcional)</span>
                    <textarea name="notes" class="field" maxlength="2000" rows="2">{{ is_string($notes) ? $notes : '' }}</textarea>
                </label>
            </div>

            <div class="page-actions">
                <button class="button-primary">Registrar venda</button>
                <a href="{{ route('sales.index') }}" class="button-secondary">Voltar</a>
            </div>
        </form>

        <template data-line-template>
            @include('sales.item-fields', ['index' => 0, 'item' => []])
        </template>
    </div>
@endsection
