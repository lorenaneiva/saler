@props(['costLabel' => 'Custo unitário líquido', 'cost' => null, 'price' => null, 'profit' => null, 'margin' => null, 'stock' => null, 'showStock' => false, 'emptyCostText' => 'Sem custo registrado'])

@php
    $formatMoney = function (mixed $value): ?string {
        if ($value === null) {
            return null;
        }
        $amount = \Brick\Math\BigDecimal::of((string) $value)->toScale(2, \Brick\Math\RoundingMode::HalfUp);

        return ($amount->isNegative() ? '-' : '').'R$ '.number_format((float) (string) $amount->abs(), 2, ',', '.');
    };
    $isLoss = $profit !== null && \Brick\Math\BigDecimal::of((string) $profit)->isNegative();
    $costText = $formatMoney($cost);
    $marginText = $margin === null ? null : number_format((float) $margin, 2, ',', '.').'%';
@endphp

<section {{ $attributes->class(['commercial-summary']) }} data-commercial-summary aria-label="Resumo comercial">
    <h3 class="commercial-summary-title">Resumo comercial</h3>
    <dl aria-live="polite">
        <div><dt data-summary-label="cost">{{ $costLabel }}</dt><dd data-summary="cost" data-empty="{{ $emptyCostText }}" @class(['text-unknown' => $costText === null])>{{ $costText ?? $emptyCostText }}</dd></div>
        <div><dt>Preço de venda</dt><dd data-summary="price">{{ $formatMoney($price) ?? '—' }}</dd></div>
        <div><dt>Lucro bruto unitário</dt><dd data-summary="profit" @class(['is-loss' => $isLoss])>{{ $formatMoney($profit) ?? '—' }}</dd></div>
        <div><dt>Margem de lucro</dt><dd data-summary="margin" @class(['is-loss' => $isLoss])>{{ $marginText ?? '—' }}</dd></div>
        @if ($showStock)
            <div data-summary-row="stock"><dt>Estoque inicial</dt><dd data-summary="stock">{{ $stock ?? '—' }}</dd></div>
        @endif
    </dl>
    <p class="commercial-summary-alert" data-summary-alert="loss" role="status" @if (! $isLoss) hidden @endif>O preço de venda está abaixo do custo: cada unidade vendida dará prejuízo.</p>
    <p class="commercial-summary-alert" data-summary-alert="discount" role="status" hidden>O desconto é maior que o valor bruto da compra (quantidade × custo unitário).</p>
</section>
