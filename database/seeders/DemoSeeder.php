<?php

namespace Database\Seeders;

use App\Actions\CancelPurchase;
use App\Actions\RegisterCustomerPayment;
use App\Actions\RegisterPurchase;
use App\Actions\RegisterSale;
use App\Actions\RegisterStockMovement;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockAdjustmentReason;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Brick\Math\BigDecimal;
use Faker\Factory as FakerFactory;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Fills the database with enough related records to browse every table across several pages.
 * Everything goes through the application's actions, so stock, average cost and balances stay consistent.
 * Run with: vendor/bin/sail artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    private Generator $faker;

    private User $user;

    public function __construct(
        private RegisterPurchase $registerPurchase,
        private CancelPurchase $cancelPurchase,
        private RegisterSale $registerSale,
        private RegisterCustomerPayment $registerPayment,
        private RegisterStockMovement $registerStockMovement,
    ) {}

    public function run(): void
    {
        $this->faker = FakerFactory::create('pt_BR');
        $this->faker->seed(2026);
        $this->user = User::query()->orderBy('id')->firstOrFail();

        $categories = $this->categories();
        $suppliers = $this->suppliers();
        $customers = $this->customers();
        $reasons = $this->reasons();
        $products = $this->products($categories, $suppliers);

        $this->purchases($products->take(55), $suppliers);
        $this->stockMovements($products->first(), $reasons);
        $sales = $this->sales($products->take(55), $customers);
        $this->payments($sales, $customers);

        $suppliers->take(-3)->each(fn (Supplier $supplier) => $supplier->forceFill(['is_active' => false])->save());
        $products->slice(50, 3)->each(fn (Product $product) => $product->forceFill(['is_active' => false])->save());
    }

    /** @return Collection<int, Category> */
    private function categories(): Collection
    {
        $names = ['Anéis', 'Brincos', 'Colares', 'Pulseiras', 'Tornozeleiras', 'Pingentes', 'Relógios', 'Óculos de sol', 'Bolsas', 'Carteiras',
            'Cintos', 'Lenços', 'Chapéus', 'Presilhas', 'Tiaras', 'Piercings', 'Correntes', 'Berloques', 'Alianças', 'Conjuntos',
            'Porta-joias', 'Perfumes', 'Maquiagem', 'Esmaltes', 'Acessórios infantis'];

        $categories = collect($names)->map(fn (string $name): Category => Category::query()->firstOrCreate(['name' => $name]));
        $categories->take(-3)->each(fn (Category $category) => $category->forceFill(['is_active' => false])->save());

        return $categories->slice(0, -3)->values();
    }

    /** @return Collection<int, Supplier> */
    private function suppliers(): Collection
    {
        return collect(range(1, 25))->map(fn (int $number): Supplier => Supplier::query()->create([
            'name' => $this->faker->unique()->company().' '.$this->faker->randomElement(['Distribuidora', 'Atacado', 'Importadora', 'Comércio']),
            'contact' => $this->faker->randomElement([$this->faker->safeEmail(), $this->faker->cellphoneNumber()]),
            'representative' => $number % 3 === 0 ? null : $this->faker->name(),
        ]));
    }

    /** @return Collection<int, Customer> */
    private function customers(): Collection
    {
        return collect(range(1, 45))->map(fn (int $number): Customer => Customer::query()->create([
            'name' => $this->faker->unique()->name(),
            'phone' => '91'.$this->faker->numerify('9########'),
            'email' => $number % 2 === 0 ? $this->faker->unique()->safeEmail() : null,
            'cpf' => $number % 4 === 0 ? $this->faker->cpf(false) : null,
        ]));
    }

    /** @return Collection<int, StockAdjustmentReason> */
    private function reasons(): Collection
    {
        $names = ['Contagem física', 'Perda ou avaria', 'Correção de lançamento', 'Peça quebrada', 'Peça oxidada', 'Extravio', 'Furto', 'Brinde para cliente',
            'Amostra para vitrine', 'Devolução ao fornecedor', 'Troca de cliente', 'Defeito de fabricação', 'Uso interno', 'Doação', 'Produto vencido',
            'Embalagem danificada', 'Ajuste de inventário', 'Erro de cadastro', 'Transferência de loja', 'Peça de mostruário', 'Sobra no inventário',
            'Falta no inventário', 'Reposição de garantia', 'Sinistro', 'Outros'];

        return collect($names)->map(fn (string $name): StockAdjustmentReason => StockAdjustmentReason::query()->firstOrCreate(['name' => $name]));
    }

    /**
     * @param  Collection<int, Category>  $categories
     * @param  Collection<int, Supplier>  $suppliers
     * @return Collection<int, Product>
     */
    private function products(Collection $categories, Collection $suppliers): Collection
    {
        $items = ['Brinco', 'Colar', 'Anel', 'Pulseira', 'Pingente', 'Tornozeleira', 'Corrente', 'Argola', 'Gargantilha', 'Bracelete'];
        $styles = ['dourado', 'prateado', 'com zircônia', 'de pérola', 'trançado', 'minimalista', 'de coração', 'cravejado', 'de estrela', 'rosé'];

        return collect(range(0, 59))->map(fn (int $index): Product => Product::query()->create([
            'name' => $items[$index % 10].' '.$styles[intdiv($index, 10) + ($index % 4)],
            'category_id' => $categories[$index % $categories->count()]->id,
            'supplier_id' => $suppliers[$index % 20]->id,
            'sale_price' => $this->money($this->faker->numberBetween(25, 600)),
            'barcode' => $index % 3 === 0 ? null : $this->faker->unique()->ean13(),
        ]));
    }

    /**
     * Confirmed purchases for every given product (several of them bought twice at different costs), plus two cancelled ones.
     *
     * @param  Collection<int, Product>  $products
     * @param  Collection<int, Supplier>  $suppliers
     */
    private function purchases(Collection $products, Collection $suppliers): void
    {
        $batches = $products->chunk(2)->values()->concat($products->take(30)->chunk(3)->values());

        foreach ($batches as $number => $batch) {
            $this->registerPurchase->handle($this->user, [
                'supplier_id' => $suppliers[$number % 22]->id,
                'purchased_on' => $this->daysAgo(150 - $number * 2),
                'submission_token' => (string) Str::uuid(),
                'items' => $batch->values()->map(fn (Product $product, int $index): array => [
                    'product_id' => $product->id,
                    'quantity' => $this->faker->numberBetween(15, 40),
                    // One product is bought above its sale price to show a negative margin.
                    'unit_cost' => $this->money($product->is($products->last()) ? (float) $product->sale_price * 1.2 : (float) $product->sale_price * $this->faker->randomFloat(2, 0.35, 0.7)),
                    'discount' => $index === 0 && $number % 4 === 0 ? $this->money(5) : null,
                ])->all(),
            ]);
        }

        foreach ([0, 1] as $offset) {
            $purchase = $this->registerPurchase->handle($this->user, [
                'supplier_id' => $suppliers[$offset]->id,
                'purchased_on' => $this->daysAgo(20 - $offset),
                'items' => [['product_id' => $products[$offset + 10]->id, 'quantity' => 3, 'unit_cost' => $this->money(10)]],
            ]);
            $this->cancelPurchase->handle($purchase, $this->user);
        }
    }

    /** Manual entries and losses so one product's movement history spans several pages. */
    private function stockMovements(Product $product, Collection $reasons): void
    {
        foreach (range(1, 24) as $number) {
            $this->registerStockMovement->handle($product->fresh(), $this->user, [
                'type' => $number % 2 === 0 ? StockMovement::ManualExit : StockMovement::ManualEntry,
                'quantity' => $number % 2 === 0 ? 1 : 2,
                'reason_id' => $reasons[$number % $reasons->count()]->id,
                'justification' => $number % 3 === 0 ? $this->faker->sentence(6) : null,
            ]);
        }
    }

    /**
     * Sales spread over the last months; the first customer buys often enough to page through their history.
     *
     * @param  Collection<int, Product>  $products
     * @param  Collection<int, Customer>  $customers
     * @return Collection<int, Sale>
     */
    private function sales(Collection $products, Collection $customers): Collection
    {
        return collect(range(1, 75))->map(function (int $number) use ($products, $customers): Sale {
            $available = $products->map->fresh()->filter(fn (Product $product): bool => $product->is_active && $product->stock_quantity >= 3);
            $items = $available->random(min($this->faker->numberBetween(1, 3), $available->count()))
                ->map(fn (Product $product): array => ['product_id' => $product->id, 'quantity' => $this->faker->numberBetween(1, 2)])->values()->all();

            return $this->registerSale->handle($this->user, [
                'customer_id' => ($number <= 26 ? $customers->first() : $customers[$number % $customers->count()])->id,
                'sold_on' => $this->daysAgo(90 - $number),
                'items' => $items,
                'discount' => $number % 5 === 0 ? $this->money(5) : null,
                'notes' => $number % 7 === 0 ? $this->faker->sentence(5) : null,
            ]);
        });
    }

    /**
     * General customer payments (distributed by smallest balance) and, for one sale, many small payments made on the sale itself.
     *
     * @param  Collection<int, Sale>  $sales
     * @param  Collection<int, Customer>  $customers
     */
    private function payments(Collection $sales, Collection $customers): void
    {
        $bigSale = $sales->where('customer_id', '!==', $customers->first()->id)->sortByDesc(fn (Sale $sale): float => (float) $sale->total)->first();
        foreach (range(1, 24) as $number) {
            $this->pay($bigSale->customer, $this->money(min(3, (float) $bigSale->total / 30)), $bigSale->sold_on->copy()->addDays(1 + intdiv($number, 3)), $bigSale);
        }

        foreach (range(1, 14) as $number) {
            $this->pay($customers->first(), $this->money($this->faker->numberBetween(40, 180)), Carbon::parse($this->daysAgo(60 - $number * 3)));
        }

        $customers->slice(1)->each(function (Customer $customer, int $index): void {
            if ($index % 3 !== 0) {
                $this->pay($customer, $this->money($this->faker->numberBetween(20, 300)), Carbon::parse($this->daysAgo($this->faker->numberBetween(1, 20))));
            }
        });
    }

    private function pay(Customer $customer, string $amount, Carbon $paidOn, ?Sale $sale = null): void
    {
        $balance = $sale
            ? Sale::query()->withPaidTotal()->findOrFail($sale->id)->outstandingAmount()
            : $customer->sales()->where('status', Sale::Completed)->withPaidTotal()->get()
                ->reduce(fn (BigDecimal $total, Sale $open): BigDecimal => $total->plus($open->outstandingAmount()), BigDecimal::zero());
        $amount = BigDecimal::min(BigDecimal::of($amount), $balance);

        if (! $amount->isPositive()) {
            return;
        }

        $this->registerPayment->handle($customer, $this->user, [
            'submission_token' => (string) Str::uuid(),
            'amount' => (string) $amount,
            'paid_on' => $paidOn->min(now())->toDateString(),
            'method' => $this->faker->randomElement(['cash', 'pix', 'pix', 'credit_card', 'debit_card', 'transfer']),
            'notes' => $this->faker->boolean(25) ? $this->faker->sentence(4) : null,
        ], $sale);
    }

    private function money(float $value): string
    {
        return number_format(max($value, 0.01), 2, '.', '');
    }

    private function daysAgo(int $days): string
    {
        return now()->subDays(max($days, 0))->toDateString();
    }
}
