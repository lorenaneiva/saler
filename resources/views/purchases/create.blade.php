@extends('layout')

@section('title', 'Nova compra | Saler')

@section('content')
    <div class="grid max-w-5xl gap-6">
        <h1 class="page-title">Nova compra</h1>

        @if ($suppliers->isEmpty() || ($products->isEmpty() && $categories->isEmpty()))
            <div class="panel flex flex-wrap items-center gap-4 p-5">
                @if ($suppliers->isEmpty())
                    <p class="w-full">Cadastre ou ative um fornecedor para registrar a compra.</p>
                    <a class="button-secondary" href="{{ route('suppliers.index') }}">Fornecedores</a>
                @endif
                @if ($products->isEmpty() && $categories->isEmpty())
                    <p class="w-full">Cadastre uma categoria para poder criar o primeiro produto da compra.</p>
                    <a class="button-secondary" href="{{ route('categories.index') }}">Categorias</a>
                @endif
            </div>
        @else
            @php
                $previousItems = old('items');
                $rows = is_array($previousItems) && $previousItems !== []
                    ? array_slice(array_values($previousItems), 0, 50)
                    : [[]];
                $date = old('purchased_on', now()->toDateString());
            @endphp
            <form method="POST" action="{{ route('purchases.store') }}" class="panel grid gap-6 p-5 sm:p-6">
                @csrf
                @if ($errors->any())
                    <ul role="alert" class="list-disc rounded-lg bg-red-50 py-3 pr-4 pl-9 text-sm text-red-900">
                        @foreach (array_unique($errors->all()) as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                @endif
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="grid gap-2 font-medium">
                        Fornecedor
                        <select name="supplier_id" class="field" required>
                            <option value="">Selecione</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="grid gap-2 font-medium">
                        Data da compra
                        <input name="purchased_on" class="field" type="date" required value="{{ is_string($date) ? $date : '' }}">
                    </label>
                </div>

                <fieldset class="grid gap-4">
                    <legend class="mb-3 text-lg font-semibold">Itens da compra</legend>
                    <p class="text-sm text-slate-600">Informe o desconto em reais para o total de cada item.</p>
                    <div id="purchase-items" class="grid gap-3">
                        @foreach ($rows as $index => $item)
                            @include('purchases.item-fields', ['index' => $index, 'item' => is_array($item) ? $item : []])
                        @endforeach
                    </div>
                    <button type="button" id="add-purchase-item" class="button-secondary">Adicionar produto</button>
                </fieldset>

                <div class="flex gap-3">
                    <button class="button-primary">Registrar compra</button>
                    <a href="{{ route('purchases.index') }}" class="button-secondary">Voltar</a>
                </div>
            </form>

            <template id="purchase-item-template">
                @include('purchases.item-fields', ['index' => 0, 'item' => []])
            </template>
            <script>
                const items = document.getElementById('purchase-items');
                const addItem = document.getElementById('add-purchase-item');
                const itemTemplate = document.getElementById('purchase-item-template');

                function updateItemRows() {
                    const rows = Array.from(items.children);
                    rows.forEach((row, index) => {
                        const productSelect = row.querySelector('[data-product-select]');
                        const isNewProduct = productSelect.value === 'new';
                        const productFields = row.querySelector('[data-new-product-fields]');

                        if (isNewProduct) {
                            productSelect.removeAttribute('name');
                        } else {
                            productSelect.name = `items[${index}][product_id]`;
                        }

                        if (productFields) {
                            productFields.hidden = !isNewProduct;
                            productFields.disabled = !isNewProduct;
                            productFields.querySelectorAll('[data-new-product-field]').forEach((field) => {
                                field.name = `items[${index}][new_product][${field.dataset.newProductField}]`;
                            });
                        }

                        row.querySelectorAll('[data-item-field]').forEach((field) => {
                            field.name = `items[${index}][${field.dataset.itemField}]`;
                        });
                        row.querySelector('[data-remove-item]').disabled = rows.length === 1;
                    });
                    addItem.disabled = rows.length >= 50;
                }

                addItem.addEventListener('click', () => {
                    items.append(itemTemplate.content.cloneNode(true));
                    updateItemRows();
                    items.lastElementChild.querySelector('select').focus();
                });

                items.addEventListener('change', (event) => {
                    if (event.target.matches('[data-product-select]')) {
                        updateItemRows();
                    }
                });

                items.addEventListener('click', (event) => {
                    const removeButton = event.target.closest('[data-remove-item]');

                    if (removeButton && items.children.length > 1) {
                        removeButton.closest('[data-item-row]').remove();
                        updateItemRows();
                        addItem.focus();
                    }
                });

                updateItemRows();
            </script>
        @endif
    </div>
@endsection
