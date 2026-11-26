@if ($categories->isEmpty())
    <div class="grid gap-3 p-5">
        <p>Cadastre uma categoria para vincular o produto.</p>
        <a href="{{ route('categories.index') }}" class="button-primary">Cadastrar categoria</a>
    </div>
@else
    <form method="POST" action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}"
        data-product-form data-average-cost="{{ $product->average_cost ?? '0.0000' }}" class="product-form">
        @csrf
        @if ($product->exists)
            @method('PATCH')
        @endif

        <div class="product-form-fields">
            <p data-product-error role="alert" tabindex="-1" class="notice notice-error" hidden></p>

            <fieldset class="product-field-group">
                <legend>Identificação</legend>
                @foreach (['name' => ['Nome do produto', 255, true], 'barcode' => ['Código de barras (opcional)', 64, false]] as $field => [$label, $maxLength, $required])
                    @php($value = old($field, $product->{$field}))
                    <div class="grid gap-1">
                        <label for="product-{{ $field }}">{{ $label }} @if ($required)<x-required-mark />@endif</label>
                        <input id="product-{{ $field }}" name="{{ $field }}" class="field" type="text"
                            maxlength="{{ $maxLength }}" @required($required) value="{{ is_scalar($value) ? $value : '' }}"
                            @if ($field === 'barcode') data-barcode-input autocomplete="off" placeholder="Digite ou use o leitor" @endif
                            aria-describedby="product-{{ $field }}-error" @error($field) aria-invalid="true" @enderror>
                        <p id="product-{{ $field }}-error" data-product-field-error="{{ $field }}" class="text-sm text-red-800" @if (! $errors->has($field)) hidden @endif>{{ $errors->first($field) }}</p>
                    </div>
                @endforeach
                <div class="grid gap-1">
                    <label for="product-category_id">Categoria <x-required-mark /></label>
                    <select id="product-category_id" name="category_id" class="field" required aria-describedby="product-category_id-error" @error('category_id') aria-invalid="true" @enderror>
                        <option value="">Selecione</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>{{ $category->name }}{{ $category->is_active ? '' : ' (inativa)' }}</option>
                        @endforeach
                    </select>
                    <p id="product-category_id-error" data-product-field-error="category_id" class="text-sm text-red-800" @if (! $errors->has('category_id')) hidden @endif>{{ $errors->first('category_id') }}</p>
                </div>
            </fieldset>

            <fieldset class="product-field-group">
                <legend>Informações comerciais</legend>
                <div class="grid gap-1">
                    <label for="product-supplier_id">Fornecedor (opcional)</label>
                    <select id="product-supplier_id" name="supplier_id" class="field" aria-describedby="product-supplier_id-error" @error('supplier_id') aria-invalid="true" @enderror>
                        <option value="">Sem vínculo</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected(old('supplier_id', $product->supplier_id) == $supplier->id)>{{ $supplier->name }}{{ $supplier->is_active ? '' : ' (inativo)' }}</option>
                        @endforeach
                    </select>
                    @if ($product->supplier_id === null && $product->supplier_name)
                        <p class="text-xs text-slate-600">Fornecedor anterior: {{ $product->supplier_name }}</p>
                    @endif
                    <p id="product-supplier_id-error" data-product-field-error="supplier_id" class="text-sm text-red-800" @if (! $errors->has('supplier_id')) hidden @endif>{{ $errors->first('supplier_id') }}</p>
                </div>
                @php($salePrice = old('sale_price', $product->sale_price))
                <div class="grid gap-1">
                    <label for="product-sale_price">Preço de venda (R$) <x-required-mark /></label>
                    <input id="product-sale_price" name="sale_price" type="number" min="0" max="9999999999.99" step="0.01" required class="field"
                        value="{{ is_scalar($salePrice) ? $salePrice : '' }}" aria-describedby="product-sale_price-error" @error('sale_price') aria-invalid="true" @enderror>
                    <p id="product-sale_price-error" data-product-field-error="sale_price" class="text-sm text-red-800" @if (! $errors->has('sale_price')) hidden @endif>{{ $errors->first('sale_price') }}</p>
                </div>
                <dl class="product-readonly grid grid-cols-2 gap-3">
                    <div><dt>Custo médio <span class="block text-xs font-normal">Somente leitura</span></dt><dd><x-ui.money :value="$product->average_cost ?? '0.0000'" /></dd></div>
                    <div><dt>Margem de lucro <span class="block text-xs font-normal">Calculada automaticamente</span></dt><dd><output data-product-margin for="product-sale_price" aria-live="polite" aria-atomic="true">{{ $product->exists && $product->profitMargin() !== null ? number_format((float) $product->profitMargin(), 2, ',', '.').'%' : '—' }}</output></dd></div>
                </dl>
            </fieldset>

            <fieldset class="product-field-group">
                <legend>Disponibilidade</legend>
                <dl class="product-readonly grid grid-cols-2 gap-3">
                    <div><dt>Estoque atual</dt><dd>{{ $product->stock_quantity ?? 0 }} un.</dd></div>
                    <div><dt>Situação</dt><dd><x-ui.badge :tone="($product->is_active ?? true) ? 'success' : 'neutral'">{{ ($product->is_active ?? true) ? 'Ativo' : 'Inativo' }}</x-ui.badge></dd></div>
                </dl>
                <p class="text-xs text-slate-600">Somente leitura. Estoque e custo são atualizados pelas movimentações. A situação é gerenciada na listagem.</p>
            </fieldset>
        </div>

        <footer class="product-form-footer">
            <a href="{{ route('products.index') }}" data-product-cancel class="button-secondary">Cancelar</a>
            <button type="submit" class="button-primary" data-product-save>{{ $product->exists ? 'Salvar alterações' : 'Cadastrar produto' }}</button>
        </footer>
    </form>
@endif
