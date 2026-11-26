@if ($distribution === null)
    <p class="payment-distribution-message">Informe o valor recebido para ver como o pagamento será distribuído.</p>
@elseif (! $distribution['unallocated']->isZero())
    <p class="payment-distribution-message text-red-800" role="alert">
        O valor informado supera o saldo em aberto do cliente (<x-ui.money :value="$distribution['outstanding']" />).
    </p>
@else
    @php
        $settledCount = collect($distribution['lines'])->filter(fn (array $line): bool => $line['remaining']->isZero())->count();
        $partialCount = count($distribution['lines']) - $settledCount;
    @endphp
    <input type="hidden" name="distribution_signature" value="{{ $distribution['signature'] }}">
    <div class="payment-distribution-table" tabindex="0" role="region" aria-label="Compras contempladas pelo pagamento">
        <table class="data-table data-table-compact">
            <thead>
                <tr>
                    <th scope="col">Compra</th>
                    <th scope="col" class="money">Saldo atual</th>
                    <th scope="col" class="money">Abatimento</th>
                    <th scope="col" class="money">Saldo restante</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($distribution['lines'] as $line)
                    @php($isSettled = $line['remaining']->isZero())
                    <tr @class(['is-settled' => $isSettled])>
                        <th scope="row">
                            <a class="cell-link" href="{{ route('sales.show', $line['sale']) }}" target="_blank" rel="noopener" title="Abrir a venda em outra aba">#{{ $line['sale']->id }}</a>
                            <x-ui.badge :tone="$isSettled ? 'success' : 'warning'" class="ml-1">{{ $isSettled ? 'Quitada' : 'Parcial' }}</x-ui.badge>
                        </th>
                        <td class="money"><x-ui.money :value="$line['balance']" /></td>
                        <td class="money font-semibold"><x-ui.money :value="$line['applied']" /></td>
                        <td class="money"><x-ui.money :value="$line['remaining']" /></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <dl class="payment-distribution-summary">
        <div><dt>Total do pagamento</dt><dd><x-ui.money :value="$distribution['amount']" /></dd></div>
        <div><dt>Compras que serão quitadas</dt><dd>{{ $settledCount }}</dd></div>
        <div><dt>Compras com pagamento parcial</dt><dd>{{ $partialCount }}</dd></div>
        <div><dt>Saldo do cliente após o pagamento</dt><dd><x-ui.money :value="$distribution['outstanding']->minus($distribution['amount'])" /></dd></div>
    </dl>
@endif
