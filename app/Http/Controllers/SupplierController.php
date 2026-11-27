<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListSearchRequest;
use App\Http\Requests\SupplierRequest;
use App\Http\TableSort;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(ListSearchRequest $request): View
    {
        $sort = $request->sorting([
            'nome' => 'name',
            'contato' => 'contact',
            'representante' => 'representative',
            'situacao' => TableSort::labels('suppliers.is_active', [1 => 'Ativo', 0 => 'Inativo']),
        ], text: ['nome', 'contato', 'representante', 'situacao'], default: 'nome');

        return view('suppliers.index', [
            'suppliers' => $sort->apply($request->filter(Supplier::query(), ['name', 'contact', 'representative']))
                ->paginate(self::PerPage)->withQueryString(),
            'sort' => $sort,
        ]);
    }

    public function create(): View
    {
        return view('suppliers.form', ['supplier' => new Supplier]);
    }

    public function store(SupplierRequest $request): RedirectResponse
    {
        Supplier::query()->create($request->validated());

        return to_route('suppliers.index')->with('status', 'Fornecedor cadastrado.');
    }

    public function edit(Supplier $supplier): View
    {
        return view('suppliers.form', compact('supplier'));
    }

    public function update(SupplierRequest $request, Supplier $supplier): RedirectResponse|JsonResponse
    {
        $supplier->update($request->validated());

        if ($request->expectsJson()) {
            $request->session()->flash('status', 'Fornecedor atualizado.');

            return response()->json(['message' => 'Fornecedor atualizado.']);
        }

        return to_route('suppliers.index')->with('status', 'Fornecedor atualizado.');
    }

    public function activate(Supplier $supplier): RedirectResponse
    {
        $supplier->forceFill(['is_active' => true])->save();

        return to_route('suppliers.index')->with('status', 'Fornecedor ativado.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $supplier->forceFill(['is_active' => false])->save();

        return to_route('suppliers.index')->with('status', 'Fornecedor desativado.');
    }
}
