@extends('layout')

@section('title', 'Nova compra | Saler')

@section('content')
    <x-ui.page-header title="Nova compra" description="Selecione o fornecedor e informe os itens recebidos." :back="route('purchases.index')" back-label="Compras" />
    <div class="grid gap-4" data-line-editor>

        @if ($suppliers->isEmpty())
            <div class="panel flex flex-wrap items-center gap-4 p-5">
                <p>Cadastre ou ative um fornecedor para registrar a compra.</p>
                <a class="button-secondary" href="{{ route('suppliers.index') }}">Fornecedores</a>
            </div>
        @endif

        @php
            $previousItems = old('items');
            $rows = is_array($previousItems) && $previousItems !== []
                ? array_slice(array_values($previousItems), 0, 50)
                : [[]];
            $date = old('purchased_on', now()->toDateString());
        @endphp
        <form method="POST" action="{{ route('purchases.store') }}" class="panel form-panel">
            @csrf
            <x-ui.errors />
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="grid gap-2 font-medium">
                    <span>Fornecedor <x-required-mark /></span>
                    <select name="supplier_id" class="field" required>
                        <option value="">Selecione</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="grid gap-2 font-medium">
                    <span>Data da compra <x-required-mark /></span>
                    <input name="purchased_on" class="field" type="date" required value="{{ is_string($date) ? $date : '' }}">
                </label>
            </div>

            <fieldset class="grid gap-4">
                <legend class="mb-3 text-lg font-semibold">Itens da compra</legend>
                <p class="text-sm text-slate-600">Informe o desconto em reais para o total de cada item.</p>
                <div data-line-items class="grid gap-3">
                    @foreach ($rows as $index => $item)
                        @include('purchases.item-fields', ['index' => $index, 'item' => is_array($item) ? $item : []])
                    @endforeach
                </div>
                <button type="button" data-line-add class="button-secondary">Adicionar produto</button>
            </fieldset>

            <div class="page-actions">
                <button class="button-primary">Registrar compra</button>
                <a href="{{ route('purchases.index') }}" class="button-secondary">Voltar</a>
            </div>
        </form>

        <template data-line-template>
            @include('purchases.item-fields', ['index' => 0, 'item' => []])
        </template>
    </div>
@endsection
