@extends('layout')

@section('title', 'Usuários | Saler')

@section('content')
    <x-ui.page-header title="Usuários" description="Gerencie quem acessa o sistema e redefina senhas.">
        <x-slot:actions>
            <button type="button" class="button-primary" data-dialog-open="new-user"><span aria-hidden="true">＋</span> Novo usuário</button>
        </x-slot:actions>
    </x-ui.page-header>

    @error('cargo', 'createUser')
        <x-ui.alert tone="error">{{ $message }}</x-ui.alert>
    @enderror

    <x-ui.data-table id="users" title="Usuários cadastrados" :paginator="$users" :unit="['usuário', 'usuários']">
        <x-slot:filters>
            <x-ui.list-search label="Buscar usuário" placeholder="Nome ou e-mail" :active="request()->filled('q') || array_filter($filters)" autocapitalize="none" spellcheck="false">
                <x-ui.table-filter label="Cargo">
                    <select name="cargo" class="field">
                        <option value="">Todos</option>
                        <option value="{{ \App\Models\Cargo::Root }}" @selected($filters['cargo'] === \App\Models\Cargo::Root)>Root</option>
                        <option value="{{ \App\Models\Cargo::Administrador }}" @selected($filters['cargo'] === \App\Models\Cargo::Administrador)>Administrador</option>
                    </select>
                </x-ui.table-filter>
                <x-ui.table-filter label="Situação">
                    <select name="situacao" class="field">
                        <option value="">Todas</option>
                        <option value="ativos" @selected($filters['status'] === 'ativos')>Ativos</option>
                        <option value="inativos" @selected($filters['status'] === 'inativos')>Inativos</option>
                    </select>
                </x-ui.table-filter>
            </x-ui.list-search>
        </x-slot:filters>

        <thead>
            <tr>
                <x-ui.sort-header :sort="$sort" column="id" class="numeric w-20">ID</x-ui.sort-header>
                <x-ui.sort-header :sort="$sort" column="nome">Nome</x-ui.sort-header>
                <x-ui.sort-header :sort="$sort" column="email">E-mail</x-ui.sort-header>
                <x-ui.sort-header :sort="$sort" column="cargo" class="w-32">Cargo</x-ui.sort-header>
                <x-ui.sort-header :sort="$sort" column="situacao" class="w-28">Situação</x-ui.sort-header>
                <x-ui.sort-header :sort="$sort" column="cadastro" class="w-32">Cadastro</x-ui.sort-header>
                <x-ui.sort-header :sort="$sort" column="acesso" class="w-36">Último acesso</x-ui.sort-header>
                <th scope="col" class="w-80">Ações</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($users as $user)
                @php($editForm = 'user-'.$user->id)
                @php($manageable = auth()->user()->can('manage-user', $user))
                <tr @if ($manageable) data-inline-editor @endif>
                    <td class="numeric">{{ $user->id }}</td>
                    <th scope="row" class="wrap-anywhere font-semibold">
                        @if ($manageable)
                            <x-ui.inline-field :form="$editForm" name="name" label="Nome completo" :value="$user->name" required maxlength="255" autocomplete="off" />
                        @else
                            {{ $user->name }}
                        @endif
                    </th>
                    <td class="wrap-anywhere">
                        @if ($manageable)
                            <x-ui.inline-field :form="$editForm" name="email" label="E-mail" :value="$user->email" type="email" required maxlength="255" autocomplete="off" />
                        @else
                            {{ $user->email }}
                        @endif
                    </td>
                    <td>{{ $user->cargo->name }}</td>
                    <td><x-ui.badge :tone="$user->ativo ? 'success' : 'neutral'">{{ $user->ativo ? 'Ativo' : 'Inativo' }}</x-ui.badge></td>
                    <td class="tabular-nums">{{ $user->created_at->format('d/m/Y') }}</td>
                    <td class="tabular-nums">
                        @if ($user->last_login_at)
                            {{ $user->last_login_at->format('d/m/Y') }}
                            <span class="block text-xs text-slate-600">{{ $user->last_login_at->format('H:i') }}</span>
                        @else
                            <span class="text-xs text-slate-600">Nunca acessou</span>
                        @endif
                    </td>
                    <td>
                        @if ($manageable)
                            <div class="row-actions">
                                <x-ui.inline-actions :id="$editForm" :action="route('security.users.update', $user)" :label="'Editar '.$user->name" />
                                <button type="button" data-inline-display class="button-quiet button-small" data-dialog-open="reset-password"
                                    data-reset-password="{{ route('security.users.password', $user) }}" data-user-id="{{ $user->id }}" data-user-name="{{ $user->name }}"
                                    title="Redefinir senha" aria-label="Redefinir senha de {{ $user->name }}">Redefinir</button>
                                <form data-inline-display method="POST" action="{{ $user->ativo ? route('security.users.deactivate', $user) : route('security.users.activate', $user) }}"
                                    @if ($user->ativo) data-confirm="Desativar {{ $user->name }}? A conta será desconectada imediatamente." @endif>
                                    @csrf
                                    @method('PATCH')
                                    <button @class(['button-quiet button-small', 'danger' => $user->ativo])
                                        aria-label="{{ $user->ativo ? 'Desativar' : 'Reativar' }} {{ $user->name }}">{{ $user->ativo ? 'Desativar' : 'Reativar' }}</button>
                                </form>
                            </div>
                        @else
                            <span class="text-xs text-slate-600" title="A conta Root não pode ser alterada pela interface">Conta protegida</span>
                        @endif
                    </td>
                </tr>
            @empty
                <x-ui.empty :columns="8">{{ request()->filled('q') || array_filter($filters) ? 'Nenhum usuário encontrado para estes filtros.' : 'Nenhum usuário cadastrado.' }}</x-ui.empty>
            @endforelse
        </tbody>
    </x-ui.data-table>

    <x-ui.dialog id="new-user" title="Novo usuário" drawer :auto-open="$errors->createUser->any()" inline-fallback>
        <form method="POST" action="{{ route('security.users.store') }}" class="grid gap-4" autocomplete="off">
            @csrf
            @include('security.users.field', ['bag' => 'createUser', 'field' => 'name', 'label' => 'Nome completo', 'type' => 'text', 'autocomplete' => 'off'])
            @include('security.users.field', ['bag' => 'createUser', 'field' => 'email', 'label' => 'E-mail', 'type' => 'email', 'autocomplete' => 'off'])
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid content-start gap-2">
                    <label for="new-user-cargo" class="font-medium">Cargo <x-required-mark /></label>
                    <select id="new-user-cargo" name="cargo" class="field" required>
                        @foreach ($assignableCargos as $cargo)
                            <option value="{{ $cargo->slug }}" @selected(old('cargo') === $cargo->slug)>{{ $cargo->name }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-slate-600">A conta Root só é criada pelo terminal.</p>
                </div>
                <div class="grid content-start gap-2">
                    <label for="new-user-situacao" class="font-medium">Situação <x-required-mark /></label>
                    <select id="new-user-situacao" name="situacao" class="field" required>
                        <option value="ativo" @selected(old('situacao', 'ativo') === 'ativo')>Ativo</option>
                        <option value="inativo" @selected(old('situacao') === 'inativo')>Inativo</option>
                    </select>
                </div>
            </div>
            @include('security.users.field', ['bag' => 'createUser', 'field' => 'password', 'label' => 'Senha inicial', 'type' => 'password', 'autocomplete' => 'new-password',
                'hint' => 'Mínimo de '.\App\Http\Requests\PasswordRules::MinimumLength.' caracteres. A pessoa deverá trocá-la no primeiro acesso.'])
            @include('security.users.field', ['bag' => 'createUser', 'field' => 'password_confirmation', 'label' => 'Confirmação da senha', 'type' => 'password', 'autocomplete' => 'new-password'])
            <div class="form-actions">
                <button class="button-primary">Cadastrar usuário</button>
                <button type="button" class="button-secondary" data-dialog-close>Cancelar</button>
            </div>
        </form>
    </x-ui.dialog>

    @php($resetUserId = old('reset_user_id'))
    @php($resetUserName = old('reset_user_name'))
    <x-ui.dialog id="reset-password" title="Redefinir senha" drawer :auto-open="$errors->resetPassword->any()">
        <form method="POST" action="{{ is_numeric($resetUserId) ? route('security.users.password', (int) $resetUserId) : route('security.users.index') }}"
            class="grid gap-4" autocomplete="off" data-reset-password-form>
            @csrf
            @method('PUT')
            <input type="hidden" name="reset_user_id" value="{{ is_numeric($resetUserId) ? (int) $resetUserId : '' }}">
            <input type="hidden" name="reset_user_name" value="{{ is_string($resetUserName) ? $resetUserName : '' }}">
            <p class="text-sm">
                Defina uma senha temporária para <strong data-reset-password-user>{{ is_string($resetUserName) ? $resetUserName : '' }}</strong>.
                As sessões abertas serão encerradas e a troca será exigida no próximo acesso.
            </p>
            @include('security.users.field', ['bag' => 'resetPassword', 'field' => 'password', 'id' => 'reset-password-value', 'label' => 'Nova senha', 'type' => 'password', 'autocomplete' => 'new-password',
                'hint' => 'Mínimo de '.\App\Http\Requests\PasswordRules::MinimumLength.' caracteres.'])
            @include('security.users.field', ['bag' => 'resetPassword', 'field' => 'password_confirmation', 'id' => 'reset-password-confirmation', 'label' => 'Confirmação da senha', 'type' => 'password', 'autocomplete' => 'new-password'])
            <div class="form-actions">
                <button class="button-primary">Redefinir senha</button>
                <button type="button" class="button-secondary" data-dialog-close>Cancelar</button>
            </div>
        </form>
    </x-ui.dialog>
@endsection
