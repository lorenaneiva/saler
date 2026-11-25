<?php

namespace App\Models;

use Brick\Math\BigDecimal;
use Database\Factories\SaleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['customer_id', 'user_id', 'sold_on', 'subtotal', 'discount', 'total', 'notes'])]
class Sale extends Model
{
    /** @use HasFactory<SaleFactory> */
    use HasFactory;

    public const Completed = 'completed';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['sold_on' => 'date', 'subtotal' => 'decimal:2', 'discount' => 'decimal:2', 'total' => 'decimal:2'];
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return HasMany<PaymentAllocation, $this> */
    public function paymentAllocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    /** @param Builder<Sale> $query */
    #[Scope]
    protected function withPaidTotal(Builder $query): void
    {
        $query->withSum('paymentAllocations as paid_total', 'amount');
    }

    public function paidAmount(): BigDecimal
    {
        return BigDecimal::of($this->paid_total ?? 0)->toScale(2);
    }

    public function outstandingAmount(): BigDecimal
    {
        if ($this->status !== self::Completed) {
            return BigDecimal::zero()->toScale(2);
        }

        return BigDecimal::of($this->total)->minus($this->paidAmount());
    }

    public function paymentStatus(): string
    {
        if ($this->status !== self::Completed) {
            return '—';
        }

        if ($this->outstandingAmount()->isZero()) {
            return 'Quitada';
        }

        return $this->paidAmount()->isZero() ? 'Pendente' : 'Parcialmente paga';
    }

    /** @return HasMany<SaleItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
}
