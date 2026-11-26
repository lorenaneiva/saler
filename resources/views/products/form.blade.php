@extends('layout')

@section('title', 'Produto | Saler')

@section('content')
    <x-ui.page-header :title="$product->exists ? 'Editar produto' : 'Novo produto'" :back="route('products.index')" back-label="Produtos" />

    @if ($categories->isEmpty())
        <div class="panel grid gap-3 p-6">
            <p>Cadastre uma categoria para vincular o produto.</p>
            <a href="{{ route('categories.index') }}" class="button-primary">Cadastrar categoria</a>
            <a href="{{ route('products.index') }}" class="w-fit underline">Voltar aos produtos</a>
        </div>
    @else
        <form method="POST" action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}"
            class="panel form-panel form-grid">
            @csrf
            @if ($product->exists)
                @method('PATCH')
            @endif

            @foreach ([
                'name' => ['Nome', 255, true],
                'barcode' => ['Código de barras (opcional)', 64, false],
            ] as $field => [$label, $maxLength, $required])
                @php($value = old($field, $product->{$field}))
                <div class="grid content-start gap-2">
                    <label for="{{ $field }}" class="font-medium">{{ $label }} @if ($required)<x-required-mark />@endif</label>
                    <input id="{{ $field }}" name="{{ $field }}" class="field" type="text"
                        @if ($field === 'barcode') data-barcode-input autocomplete="off" autocapitalize="none" spellcheck="false" placeholder="Digite ou use o leitor" @endif
                        maxlength="{{ $maxLength }}" @required($required) value="{{ is_scalar($value) ? $value : '' }}"
                        @error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror>
                    @error($field)
                        <p id="{{ $field }}-error" class="text-sm text-red-800">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach

            <div class="grid content-start gap-2">
                <label for="category_id" class="font-medium">Categoria <x-required-mark /></label>
                <select id="category_id" name="category_id" class="field" required
                    @error('category_id') aria-invalid="true" aria-describedby="category_id-error" @enderror>
                    <option value="">Selecione</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                @error('category_id')
                    <p id="category_id-error" class="text-sm text-red-800">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid content-start gap-2">
                <label for="supplier_id" class="font-medium">Fornecedor (opcional)</label>
                <select id="supplier_id" name="supplier_id" class="field"
                    @error('supplier_id') aria-invalid="true" aria-describedby="supplier_id-error" @enderror>
                    <option value="">Sem vínculo</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected(old('supplier_id', $product->supplier_id) == $supplier->id)>
                            {{ $supplier->name }}{{ $supplier->is_active ? '' : ' (inativo)' }}
                        </option>
                    @endforeach
                </select>
                @if ($product->supplier_id === null && $product->supplier_name)
                    <p class="text-sm text-slate-600">Fornecedor informado no cadastro anterior: {{ $product->supplier_name }}</p>
                @endif
                @error('supplier_id')
                    <p id="supplier_id-error" class="text-sm text-red-800">{{ $message }}</p>
                @enderror
            </div>

            @php($salePrice = old('sale_price', $product->sale_price))
            <div class="grid content-start gap-2">
                <label for="sale_price" class="font-medium">Preço de venda (R$) <x-required-mark /></label>
                <input id="sale_price" name="sale_price" class="field" type="number" min="0"
                    max="9999999999.99" step="0.01" required value="{{ is_scalar($salePrice) ? $salePrice : '' }}"
                    @error('sale_price') aria-invalid="true" aria-describedby="sale_price-error" @enderror>
                @error('sale_price')
                    <p id="sale_price-error" class="text-sm text-red-800">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex gap-3 sm:col-span-full">
                <button class="button-primary">{{ $product->exists ? 'Salvar' : 'Cadastrar produto' }}</button>
                <a href="{{ route('products.index') }}" class="button-secondary">Cancelar</a>
            </div>
        </form>
    @endif
@endsection
