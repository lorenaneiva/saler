<?php

namespace App\Http\Requests;

use App\Http\TableSort;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Foundation\Http\FormRequest;

class ListSearchRequest extends FormRequest
{
    public function term(string $key = 'q'): string
    {
        $value = $this->query()[$key] ?? '';

        return is_string($value) ? mb_substr(trim($value), 0, 100) : '';
    }

    /**
     * Reads the ordering a user picked by clicking a column header of one table.
     *
     * @param  array<string, string|Expression|Builder<*>|QueryBuilder>  $columns  Column, expression or subquery by sort key.
     * @param  list<string>  $text  Keys sorted alphabetically.
     * @param  ?string  $default  Current ordering of the table (e.g. "nome" or "-data"), kept until the user picks another.
     * @param  ?string  $pageName  Page parameter of the table, or null when it is not paginated.
     */
    public function sorting(array $columns, array $text = [], ?string $default = null, string $key = 'ordem', ?string $pageName = 'page'): TableSort
    {
        return new TableSort($key, $columns, $text, $this->url(), $this->query(), $default, $pageName);
    }

    /**
     * @param  list<string>  $fields  Text columns or relationship.column paths.
     * @param  array<string, array<string, string>>  $labels  Display labels by column and stored value.
     */
    public function filter(Builder|Relation $records, array $fields, string $key = 'q', array $labels = []): Builder|Relation
    {
        $term = $this->term($key);
        if ($term === '') {
            return $records;
        }

        $pattern = '%'.addcslashes($term, '%_\\').'%';

        return $records->where(function (Builder $matches) use ($fields, $labels, $term, $pattern): void {
            foreach ($fields as $field) {
                if ($field === 'id') {
                    $id = filter_var(preg_replace('/^#/', '', $term), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                    if ($id !== false) {
                        $matches->orWhere($matches->getModel()->getQualifiedKeyName(), $id);
                    }

                    continue;
                }

                $separator = strrpos($field, '.');
                if ($separator === false) {
                    $matches->orWhereLike($matches->qualifyColumn($field), $pattern);
                } else {
                    $matches->orWhereHas(substr($field, 0, $separator), fn (Builder $related) => $related
                        ->whereLike(substr($field, $separator + 1), $pattern));
                }
            }

            foreach ($labels as $column => $options) {
                $values = array_keys(array_filter($options, fn (string $label): bool => mb_stripos($label, $term) !== false));
                $matches->orWhereIn($matches->qualifyColumn($column), $values);
            }
        });
    }
}
