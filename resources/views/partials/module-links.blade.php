@foreach ($groups as $group => $links)
    <div class="module-group">
        <p>{{ $group }}</p>
        @foreach ($links as [$route, $pattern, $label, $icon])
            <a href="{{ route($route) }}" class="module-link" @if (request()->routeIs($pattern)) aria-current="page" @endif>
                <span class="module-icon" aria-hidden="true">{{ $icon }}</span>{{ $label }}
            </a>
        @endforeach
    </div>
@endforeach
