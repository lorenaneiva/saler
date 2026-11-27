<?php

namespace App\Http\Controllers;

use App\Actions\RegisterUser;
use App\Actions\RevokeUserSessions;
use App\Http\Requests\ListSearchRequest;
use App\Http\Requests\ResetUserPasswordRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\TableSort;
use App\Models\Cargo;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** Security module: Root manages the administrators. Every route also requires the `manage-users` gate. */
class UserController extends Controller
{
    public function index(ListSearchRequest $request): View
    {
        $cargo = $request->query('cargo');
        if (! is_string($cargo) || ! in_array($cargo, [Cargo::Root, Cargo::Administrador], true)) {
            $cargo = null;
        }
        $status = $request->query('situacao');
        if (! in_array($status, ['ativos', 'inativos'], true)) {
            $status = null;
        }

        $sort = $request->sorting([
            'id' => 'id',
            'nome' => 'name',
            'email' => 'email',
            'cargo' => Cargo::query()->select('name')->whereColumn('cargos.id', 'users.cargo_id'),
            'situacao' => TableSort::labels('users.ativo', [1 => 'Ativo', 0 => 'Inativo']),
            'cadastro' => 'created_at',
            'acesso' => 'last_login_at',
        ], text: ['nome', 'email', 'cargo', 'situacao'], default: 'nome');

        return view('security.users.index', [
            'users' => $sort->apply($request->filter(User::query(), ['name', 'email']))
                ->with('cargo:id,name,slug')
                ->when($cargo, fn ($users) => $users->whereHas('cargo', fn ($cargos) => $cargos->where('slug', $cargo)))
                ->when($status, fn ($users) => $users->where('ativo', $status === 'ativos'))
                ->paginate(self::PerPage)->withQueryString(),
            'sort' => $sort,
            'filters' => ['cargo' => $cargo, 'status' => $status],
            'assignableCargos' => Cargo::query()->whereIn('slug', Cargo::AssignableInInterface)->orderBy('name')->get(['name', 'slug']),
        ]);
    }

    public function store(StoreUserRequest $request, RegisterUser $registerUser): RedirectResponse
    {
        $user = $registerUser->handle(
            $request->safe()->only(['name', 'email', 'password']),
            $request->validated('cargo'),
            mustChangePassword: true,
            ativo: $request->validated('situacao') === 'ativo',
        );

        return to_route('security.users.index')->with('status', "Usuária {$user->email} cadastrada. Ela deverá trocar a senha no primeiro acesso.");
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse|JsonResponse
    {
        $user->update($request->validated());

        if ($request->expectsJson()) {
            $request->session()->flash('status', 'Usuária atualizada.');

            return response()->json(['message' => 'Usuária atualizada.']);
        }

        return to_route('security.users.index')->with('status', 'Usuária atualizada.');
    }

    public function deactivate(User $user, RevokeUserSessions $revokeUserSessions): RedirectResponse
    {
        Gate::authorize('manage-user', $user);

        DB::transaction(function () use ($user, $revokeUserSessions): void {
            $user->forceFill(['ativo' => false])->save();
            $revokeUserSessions->handle($user);
        });

        return back(fallback: route('security.users.index'))->with('status', "Usuária {$user->email} desativada e desconectada.");
    }

    public function activate(User $user): RedirectResponse
    {
        Gate::authorize('manage-user', $user);

        $user->forceFill(['ativo' => true])->save();

        return back(fallback: route('security.users.index'))->with('status', "Usuária {$user->email} reativada.");
    }

    public function resetPassword(ResetUserPasswordRequest $request, User $user, RevokeUserSessions $revokeUserSessions): RedirectResponse
    {
        DB::transaction(function () use ($request, $user, $revokeUserSessions): void {
            $user->forceFill(['password' => $request->validated('password'), 'must_change_password' => true])->save();
            $revokeUserSessions->handle($user);
        });

        return back(fallback: route('security.users.index'))
            ->with('status', "Senha de {$user->email} redefinida. As sessões dela foram encerradas e a troca será exigida no próximo acesso.");
    }
}
