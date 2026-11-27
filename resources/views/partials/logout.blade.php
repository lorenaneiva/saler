<div class="grid gap-0.5 border-t border-white/15 pt-3 sm:col-span-2">
    <a href="{{ route('password.edit') }}" class="module-link" @if (request()->routeIs('password.edit')) aria-current="page" @endif>
        <x-ui.module-icon name="vpn_key" />
        Alterar senha
    </a>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="module-link w-full cursor-pointer">Sair</button>
    </form>
</div>
