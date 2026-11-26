<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListSearchRequest;
use App\Http\Requests\SupplierRequest;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(ListSearchRequest $request): View
    {
        return view('suppliers.index', [
            'suppliers' => $request->filter(Supplier::query(), ['name', 'contact', 'representative'])
                ->orderBy('name')->orderBy('id')->paginate(20)->withQueryString(),
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

    public function update(SupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($request->validated());

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
