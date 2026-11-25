@extends('layout')

@section('title', 'Produto | Saler')

@section('content')
    <div class="grid max-w-3xl gap-6">
        <h1 class="page-title">{{ $product->exists ? 'Editar produto' : 'Novo produto' }}</h1>

        @if ($categories->isEmpty())
            <div class="panel grid gap-3 p-6">
                <p>Cadastre uma categoria para vincular o produto.</p>
                <a href="{{ route('categories.index') }}" class="button-primary">Cadastrar categoria</a>
                <a href="{{ route('products.index') }}" class="w-fit underline">Voltar aos produtos</a>
            </div>
        @else
            <form method="POST" action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}"
                class="panel grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
                @csrf
                @if ($product->exists)
                    @method('PATCH')
                @endif

                @foreach ([
                    'name' => ['Nome', 255, true],
                    'barcode' => ['Código de barras (opcional)', 64, false],
                    'supplier_name' => ['Fornecedor (opcional)', 255, false],
                ] as $field => [$label, $maxLength, $required])
                    @php($value = old($field, $product->{$field}))
                    <div class="grid content-start gap-2">
                        <label for="{{ $field }}" class="font-medium">{{ $label }}</label>
                        <input id="{{ $field }}" name="{{ $field }}" class="field" type="text"
                            maxlength="{{ $maxLength }}" @required($required) value="{{ is_scalar($value) ? $value : '' }}"
                            @error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror>
                        @error($field)
                            <p id="{{ $field }}-error" class="text-sm text-red-800">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach

                <div class="grid content-start gap-2">
                    <label for="category_id" class="font-medium">Categoria</label>
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

                @php($salePrice = old('sale_price', $product->sale_price))
                <div class="grid content-start gap-2">
                    <label for="sale_price" class="font-medium">Preço de venda (R$)</label>
                    <input id="sale_price" name="sale_price" class="field" type="number" min="0"
                        max="9999999999.99" step="0.01" required value="{{ is_scalar($salePrice) ? $salePrice : '' }}"
                        @error('sale_price') aria-invalid="true" aria-describedby="sale_price-error" @enderror>
                    @error('sale_price')
                        <p id="sale_price-error" class="text-sm text-red-800">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex gap-3 sm:col-span-2">
                    <button class="button-primary">{{ $product->exists ? 'Salvar' : 'Cadastrar produto' }}</button>
                    <a href="{{ route('products.index') }}" class="button-secondary">Cancelar</a>
                </div>
            </form>
        @endif
    </div>
@endsection
