<?php

namespace App\Http\Requests;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Http\FormRequest;

class ListSearchRequest extends FormRequest
{
    public function term(string $key = 'q'): string
    {
        $value = $this->query()[$key] ?? '';

        return is_string($value) ? mb_substr(trim($value), 0, 100) : '';
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
