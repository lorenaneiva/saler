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

    <div class="grid max-w-4xl gap-6">
        <h1 class="page-title">Nova venda</h1>

        <form method="POST" action="{{ route('sales.store') }}" class="panel grid gap-6 p-5 sm:p-6">
            @csrf
            @if ($errors->any())
                <ul role="alert" class="list-disc rounded-lg bg-red-50 py-3 pr-4 pl-9 text-sm text-red-900">
                    @foreach (array_unique($errors->all()) as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <label for="sale-customer" class="font-medium">Cliente <x-required-mark /></label>
                    <select id="sale-customer" name="customer_id" class="field" required>
                        <option value="">Selecione</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>{{ $customer->name }}</option>
                        @endforeach
                    </select>
                    @if ($customers->isEmpty())
                        <a href="{{ route('customers.create') }}" class="w-fit text-sm text-emerald-800 underline">Cadastrar cliente</a>
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
                    <a href="{{ route('stock.index') }}" class="w-fit text-sm text-emerald-800 underline">Consultar estoque</a>
                @endif
                <div id="sale-items" class="grid gap-3">
                    @foreach ($rows as $index => $item)
                        @include('sales.item-fields', ['index' => $index, 'item' => is_array($item) ? $item : []])
                    @endforeach
                </div>
                <button type="button" id="add-sale-item" class="button-secondary disabled:opacity-50">Adicionar produto</button>
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

            <div class="flex flex-wrap gap-3">
                <button class="button-primary">Registrar venda</button>
                <a href="{{ route('sales.index') }}" class="button-secondary">Voltar</a>
            </div>
        </form>

        <template id="sale-item-template">
            @include('sales.item-fields', ['index' => 0, 'item' => []])
        </template>
        <script>
            const items = document.getElementById('sale-items');
            const addItem = document.getElementById('add-sale-item');
            const template = document.getElementById('sale-item-template');

            function updateRows() {
                const rows = Array.from(items.children);
                rows.forEach((row, index) => {
                    row.querySelectorAll('[data-item-field]').forEach((field) => {
                        field.name = `items[${index}][${field.dataset.itemField}]`;
                    });
                    row.querySelector('[data-remove-item]').disabled = rows.length === 1;
                });
                addItem.disabled = rows.length >= 50;
            }

            addItem.addEventListener('click', () => {
                items.append(template.content.cloneNode(true));
                updateRows();
                items.lastElementChild.querySelector('select').focus();
            });

            items.addEventListener('click', (event) => {
                const button = event.target.closest('[data-remove-item]');

                if (button && items.children.length > 1) {
                    button.closest('[data-item-row]').remove();
                    updateRows();
                    addItem.focus();
                }
            });
            updateRows();
        </script>
    </div>
@endsection
