@if ($errors->any())
    <x-ui.alert tone="error" {{ $attributes }}>
        <ul class="list-disc space-y-1 pl-4">
            @foreach (array_unique($errors->all()) as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </x-ui.alert>
@endif
