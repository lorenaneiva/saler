<?php

namespace App\Actions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class LoadAutocompleteOptions
{
    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @param  array<array-key, mixed>  $selectedIds
     * @param  list<string>  $columns
     * @return Collection<int, TModel>
     */
    public function handle(Builder $query, array $selectedIds, array $columns): Collection
    {
        $ids = array_values(array_filter(array_slice($selectedIds, 0, 50), fn (mixed $id): bool => is_scalar($id) && filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false));
        $options = (clone $query)->orderBy('name')->orderBy('id')->limit(30)->get($columns);

        if ($ids !== []) {
            $options = $options->merge((clone $query)->whereKey($ids)->get($columns));
        }

        return $options;
    }
}
