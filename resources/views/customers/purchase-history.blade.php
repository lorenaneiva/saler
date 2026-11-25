<section class="grid gap-3">
    <h2 class="text-base font-semibold">{{ $section === 'aberto' ? 'Contas em aberto' : 'Histórico de compras' }}</h2>
    <x-ui.table label="Compras do cliente">
            <thead>
                <tr>
                    @foreach (['Compra', 'Data', 'Total', 'Pago', 'Em aberto', 'Situação'] as $heading)
                        <th scope="col" @class(['numeric' => in_array($heading, ['Total', 'Pago', 'Em aberto'], true)])>{{ $heading }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($sales as $sale)
                    <tr>
                        <th scope="row" >
                            <a href="{{ route('sales.show', $sale) }}" class="text-emerald-800 underline">#{{ $sale->id }}</a>
                        </th>
                        <td class="whitespace-nowrap">{{ $sale->sold_on->format('d/m/Y') }}</td>
                        <td class="numeric"><x-ui.money :value="$sale->total" /></td>
                        <td class="numeric"><x-ui.money :value="$sale->paidAmount()" /></td>
                        <td class="numeric"><x-ui.money :value="$sale->outstandingAmount()" /></td>
                        <td>
                            @php($status = $sale->paymentStatus())
                            <x-ui.badge :tone="match ($status) { 'Quitada' => 'success', 'Parcialmente paga', 'Pendente' => 'warning', default => 'neutral' }">
                                {{ $status }}
                            </x-ui.badge>
                        </td>
                    </tr>
                @empty
                    <x-ui.empty :columns="6">{{ $section === 'aberto' ? 'Nenhuma conta em aberto.' : 'Nenhuma compra registrada para este cliente.' }}</x-ui.empty>
                @endforelse
            </tbody>
    </x-ui.table>
    <x-ui.pagination :paginator="$sales" />
</section>
