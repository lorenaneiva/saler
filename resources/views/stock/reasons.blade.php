@extends('layout')

@section('title', 'Motivos de ajuste | Saler')

@section('content')
    <x-ui.page-header title="Motivos de ajuste" :back="route('stock.index')" back-label="Estoque">
        <x-slot:actions>
            <button type="button" class="button-primary" data-dialog-open="stock-reason">Novo motivo</button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.data-table id="stock-reasons" title="Motivos cadastrados" :paginator="$reasons" :unit="['motivo', 'motivos']">
        <x-slot:filters>
            <x-ui.list-search label="Buscar motivo" placeholder="Nome do motivo" />
        </x-slot:filters>
        <thead>
            <tr><x-ui.sort-header :sort="$sort" column="motivo">Motivo</x-ui.sort-header></tr>
        </thead>
        <tbody>
            @forelse ($reasons as $reason)
                <tr><td class="wrap-anywhere">{{ $reason->name }}</td></tr>
            @empty
                <x-ui.empty :columns="1">{{ request()->filled('q') ? 'Nenhum resultado para esta busca.' : 'Nenhum motivo cadastrado.' }}</x-ui.empty>
            @endforelse
        </tbody>
    </x-ui.data-table>

    <x-ui.dialog id="stock-reason" title="Novo motivo" :auto-open="$errors->has('name')" inline-fallback>
        <form method="POST" action="{{ route('stock.reasons.store') }}" class="grid gap-3">
            @csrf
            @php($reasonName = old('name'))
            <label for="reason-name" class="font-medium">Nome do motivo <x-required-mark /></label>
            <input id="reason-name" name="name" class="field" required maxlength="100"
                value="{{ is_string($reasonName) ? $reasonName : '' }}"
                @error('name') aria-invalid="true" aria-describedby="reason-name-error" @enderror>
            @error('name')
                <p id="reason-name-error" role="alert" class="text-sm text-red-800">{{ $message }}</p>
            @enderror
            <button class="button-primary">Cadastrar motivo</button>
        </form>
    </x-ui.dialog>
@endsection
