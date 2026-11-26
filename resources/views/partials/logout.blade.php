<form method="POST" action="{{ route('logout') }}" class="border-t border-white/15 pt-3 sm:col-span-2">
    @csrf
    <button type="submit" class="module-link w-full cursor-pointer">Sair</button>
</form>
