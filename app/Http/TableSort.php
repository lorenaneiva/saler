<?php

namespace App\Http;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\Expression as QueryExpression;
use Illuminate\Support\Arr;

/**
 * The ordering a user picked for one table, applied to the whole query and kept in the URL.
 *
 * The query parameter holds the column key, prefixed with "-" when descending (e.g. "preco" or "-data").
 */
class TableSort
{
    /** Linguistic collation so text sorts case- and accent-insensitively on PostgreSQL. */
    private const TextCollation = 'und-x-icu';

    public readonly ?string $column;

    public readonly string $direction;

    /**
     * @param  array<string, string|Expression|Builder<*>|QueryBuilder>  $columns  Column, expression or subquery by sort key.
     * @param  list<string>  $textColumns  Keys sorted alphabetically.
     * @param  array<string, mixed>  $query  Current query string.
     * @param  ?string  $default  Encoded ordering used until the user picks one (e.g. "nome" or "-data").
     * @param  ?string  $pageName  Page parameter of this table, dropped when the ordering changes.
     */
    public function __construct(
        public readonly string $parameter,
        private readonly array $columns,
        private readonly array $textColumns,
        private readonly string $url,
        private readonly array $query,
        private readonly ?string $default = null,
        private readonly ?string $pageName = 'page',
    ) {
        $requested = $query[$parameter] ?? null;
        [$column, $direction] = $this->decode(is_string($requested) ? $requested : null)
            ?? $this->decode($default)
            ?? [null, 'asc'];

        $this->column = $column;
        $this->direction = $direction;
    }

    /**
     * Expression that sorts a coded column by the label users see (e.g. payment methods or active/inactive).
     *
     * @param  string  $column  Qualified column name.
     * @param  array<array-key, string>  $labels  Display label by stored value.
     */
    public static function labels(string $column, array $labels): Expression
    {
        $quote = fn (string $value): string => "'".str_replace("'", "''", $value)."'";
        $cases = collect($labels)->map(fn (string $label, int|string $value): string => 'WHEN '.$quote((string) $value).' THEN '.$quote($label));

        return new QueryExpression("CASE {$column} {$cases->implode(' ')} END");
    }

    /**
     * @template TRecords of Builder|Relation
     *
     * @param  TRecords  $records
     * @return TRecords
     */
    public function apply(Builder|Relation $records): Builder|Relation
    {
        if ($this->column === null) {
            return $records;
        }

        [$sql, $bindings] = $this->compile($records, $this->columns[$this->column]);
        if (in_array($this->column, $this->textColumns, true) && $records->getModel()->getConnection()->getDriverName() === 'pgsql') {
            $sql .= ' COLLATE "'.self::TextCollation.'"';
        }

        return $records->orderByRaw("{$sql} {$this->direction} NULLS LAST", $bindings)
            ->orderBy($records->getModel()->getQualifiedKeyName(), $this->direction);
    }

    /** @return 'asc'|'desc'|null */
    public function directionOf(string $column): ?string
    {
        return $this->column === $column ? $this->direction : null;
    }

    /** The direction a click on the column header switches to. */
    public function nextDirection(string $column): string
    {
        return $this->directionOf($column) === 'asc' ? 'desc' : 'asc';
    }

    /** Link that orders by the column, toggling the direction when it is already active, from the first page. */
    public function url(string $column): string
    {
        $ordering = ($this->nextDirection($column) === 'desc' ? '-' : '').$column;
        $query = Arr::except(array_filter($this->query, 'is_string'), array_filter([$this->parameter, $this->pageName]));
        if ($ordering !== $this->default) {
            $query[$this->parameter] = $ordering;
        }

        return $query === [] ? $this->url : $this->url.'?'.Arr::query($query);
    }

    /** @return array{string, 'asc'|'desc'}|null */
    private function decode(?string $ordering): ?array
    {
        if ($ordering === null) {
            return null;
        }

        $column = ltrim($ordering, '-');

        return array_key_exists($column, $this->columns) ? [$column, str_starts_with($ordering, '-') ? 'desc' : 'asc'] : null;
    }

    /**
     * @param  string|Expression|Builder<*>|QueryBuilder  $column
     * @return array{string, array<int, mixed>}
     */
    private function compile(Builder|Relation $records, string|Expression|Builder|QueryBuilder $column): array
    {
        $grammar = $records->getModel()->getConnection()->getQueryGrammar();

        return match (true) {
            is_string($column) => [$grammar->wrap($records->qualifyColumn($column)), []],
            $column instanceof Expression => ['('.$column->getValue($grammar).')', []],
            default => ['('.$column->toSql().')', $column->getBindings()],
        };
    }
}
