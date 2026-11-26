@if ($categories->isEmpty())
    <div class="grid gap-3 p-5">
        <p>Cadastre uma categoria para vincular o produto.</p>
        <a href="{{ route('categories.index') }}" class="button-primary">Cadastrar categoria</a>
    </div>
@else
    <form method="POST" action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}"
        data-product-form data-average-cost="{{ $product->exists && $product->hasRegisteredCost() ? $product->average_cost : '' }}" class="product-form">
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

            @php($creating = ! $product->exists)
            @php($withInitialPurchase = $creating && (bool) old('initial_purchase'))
            @php($salePrice = old('sale_price', $product->sale_price))

            <fieldset class="product-field-group">
                <legend>Informações comerciais</legend>
                <div class="grid gap-1">
                    <label for="product-supplier_id">Fornecedor <span data-supplier-optional @if ($withInitialPurchase) hidden @endif>(opcional)</span><span data-supplier-required @if (! $withInitialPurchase) hidden @endif><x-required-mark /></span></label>
                    <select id="product-supplier_id" name="supplier_id" class="field" @required($withInitialPurchase) aria-describedby="product-supplier_id-error" @error('supplier_id') aria-invalid="true" @enderror>
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
                <div class="grid gap-1">
                    <label for="product-sale_price">Preço de venda (R$) <x-required-mark /></label>
                    <input id="product-sale_price" name="sale_price" type="number" min="0" max="9999999999.99" step="0.01" required class="field" data-money-input
                        value="{{ is_scalar($salePrice) ? $salePrice : '' }}" aria-describedby="product-sale_price-error" @error('sale_price') aria-invalid="true" @enderror>
                    <p id="product-sale_price-error" data-product-field-error="sale_price" class="text-sm text-red-800" @if (! $errors->has('sale_price')) hidden @endif>{{ $errors->first('sale_price') }}</p>
                </div>
            </fieldset>

            @if ($creating)
                <input type="hidden" name="submission_token" value="{{ $submissionToken }}">
                <fieldset class="product-field-group">
                    <legend>Compra inicial <span class="font-normal text-slate-600">(opcional)</span></legend>
                    <label class="switch-field">
                        <input type="checkbox" name="initial_purchase" value="1" role="switch" data-initial-purchase-toggle @checked($withInitialPurchase)>
                        <span class="switch-track" aria-hidden="true"></span>
                        <span>Registrar compra inicial deste produto</span>
                    </label>
                    <div class="collapsible" data-initial-purchase-panel @if ($withInitialPurchase) data-open @endif>
                        <fieldset data-initial-purchase-fields @disabled(! $withInitialPurchase)>
                            <p class="text-xs text-slate-600">A compra usa o fornecedor informado acima, entra no histórico de compras e já atualiza o estoque e o custo médio.</p>
                            <div class="grid gap-3 sm:grid-cols-2">
                                @foreach ([
                                    'purchased_on' => ['Data da compra', true],
                                    'quantity' => ['Quantidade adquirida', true],
                                    'unit_cost' => ['Custo unitário (R$)', true],
                                    'discount' => ['Desconto total do item (R$)', false],
                                ] as $field => [$label, $required])
                                    @php($value = old($field, $field === 'purchased_on' ? now()->toDateString() : null))
                                    <div class="grid content-start gap-1">
                                        <label for="product-{{ $field }}">{{ $label }} @if ($required)<x-required-mark />@endif</label>
                                        <input id="product-{{ $field }}" name="{{ $field }}" class="field" @required($required)
                                            @if ($field === 'purchased_on') type="date"
                                            @elseif ($field === 'quantity') type="number" min="1" max="1000000" step="1" inputmode="numeric"
                                            @else type="number" min="0" step="0.01" max="{{ $field === 'unit_cost' ? '999999.99' : '999999999999.99' }}" data-money-input @endif
                                            value="{{ is_scalar($value) ? $value : '' }}" aria-describedby="product-{{ $field }}-error" @error($field) aria-invalid="true" @enderror>
                                        <p id="product-{{ $field }}-error" data-product-field-error="{{ $field }}" class="text-sm text-red-800" @if (! $errors->has($field)) hidden @endif>{{ $errors->first($field) }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </fieldset>
                    </div>
                </fieldset>
            @endif

            <x-ui.commercial-summary class="product-field-group"
                :cost-label="$creating ? 'Custo unitário líquido' : 'Custo médio'"
                :cost="$product->exists && $product->hasRegisteredCost() ? $product->average_cost : null"
                :price="is_numeric($salePrice) ? $salePrice : null"
                :profit="$product->exists ? $product->grossProfit() : null"
                :margin="$product->exists ? $product->profitMargin() : null"
                :show-stock="$creating"
                :empty-cost-text="$creating ? 'Informe a compra inicial' : 'Sem custo registrado'" />

            @unless ($creating)
                <fieldset class="product-field-group">
                    <legend>Disponibilidade</legend>
                    <dl class="product-readonly grid grid-cols-2 gap-3">
                        <div><dt>Estoque atual</dt><dd>{{ $product->stock_quantity ?? 0 }} un.</dd></div>
                        <div><dt>Situação</dt><dd><x-ui.badge :tone="($product->is_active ?? true) ? 'success' : 'neutral'">{{ ($product->is_active ?? true) ? 'Ativo' : 'Inativo' }}</x-ui.badge></dd></div>
                    </dl>
                    <p class="text-xs text-slate-600">Somente leitura. Estoque e custo médio são atualizados pelas compras e movimentações. A situação é gerenciada na listagem.</p>
                </fieldset>
            @endunless
        </div>

        <footer class="product-form-footer">
            <a href="{{ route('products.index') }}" data-product-cancel class="button-secondary">Cancelar</a>
            <button type="submit" class="button-primary" data-product-save>{{ $product->exists ? 'Salvar alterações' : 'Cadastrar produto' }}</button>
        </footer>
    </form>
@endif
