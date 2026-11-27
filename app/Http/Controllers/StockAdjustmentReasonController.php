<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListSearchRequest;
use App\Models\StockAdjustmentReason;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StockAdjustmentReasonController extends Controller
{
    public function index(ListSearchRequest $request): View
    {
        $sort = $request->sorting(['motivo' => 'name'], text: ['motivo'], default: 'motivo');

        return view('products.reasons', [
            'reasons' => $sort->apply($request->filter(StockAdjustmentReason::query(), ['name']))
                ->paginate(self::PerPage)->withQueryString(),
            'sort' => $sort,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(
            ['name' => ['required', 'string', 'max:100']],
            [
                'name.required' => 'Informe o nome do motivo.',
                'name.string' => 'O nome do motivo deve ser um texto.',
                'name.max' => 'O nome do motivo deve ter até 100 caracteres.',
            ],
        );

        $reason = StockAdjustmentReason::query()->createOrFirst($data);

        if (! $reason->wasRecentlyCreated) {
            throw ValidationException::withMessages(['name' => 'Este motivo já está cadastrado.']);
        }

        return to_route('products.reasons.index')->with('status', 'Motivo cadastrado.');
    }
}
