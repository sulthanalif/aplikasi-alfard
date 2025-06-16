<?php

use App\Models\User;
use App\Models\Sales;
use Mary\Traits\Toast;
use App\Models\Product;
use App\Models\SalesDetail;
use App\Traits\LogFormatter;
use Livewire\Volt\Component;
use App\Models\PurchaseOrder;
use Livewire\Attributes\Title;
use Illuminate\Support\Collection;
use App\Models\PurchaseOrderDetail;

new #[Title('Form Purchase Order')] class extends Component {
    use Toast, LogFormatter;
    // public bool $is_customer = false;

    public Collection $products;

    public string $date = '';
    public ?int $product_id = null;
    public array $productSelected = [];
    public float $total_price = 0;

    public function mount(): void
    {
        $this->date = now()->format('Y-m-d');
        $this->searchProduct();
    }

    public function back(): void
    {
        $this->redirect(route('po'), navigate: true);
    }

    public function searchProduct(string $value = '')
    {
        $selectedOption = Product::where('id', $this->product_id)->get();

        $this->products = Product::where('status', true)
            ->where('stock', '>', 0)
            ->where(function ($query) use ($value) {
                $query->where('name', 'like', "%{$value}%")
                    ->orWhere('code', 'like', "%{$value}%");
            })
            ->orderBy('code')
            ->get()
            ->merge($selectedOption);
    }


    public function selectProduct($value)
    {
        if (!$value) return;

        $product = Product::find($value);

        if (!$product) return;

        $existingProductKey = collect($this->productSelected)->search(fn ($prod) => $prod['product_id'] === $product->id);

        if ($existingProductKey !== false) {
            $this->productSelected[$existingProductKey]['qty']++;
        } else {
            $this->productSelected[] = [
                'product_id' => $product->id,
                'name' => "{$product->name} ({$product->code})",
                'unit' => $product->unit->name,
                'price' => $product->price,
                'qty' => 1,
                'subtotal' => 0, // sementara
            ];
        }

        $this->recalculateSubtotal($product->id);
    }

    public function recalculateSubtotal($productId)
    {
        foreach ($this->productSelected as &$prod) {
            if ($prod['product_id'] === $productId) {
                $prod['subtotal'] = $prod['price'] * $prod['qty'];
                break;
            }
        }

        $this->recalculateTotal();
    }

    public function recalculateTotal()
    {
        $this->total_price = collect($this->productSelected)->sum('subtotal');
    }

    public function removeProduct($index)
    {
        unset($this->productSelected[$index]);
        $this->productSelected = array_values($this->productSelected);

        $this->recalculateTotal();
    }

    public function save(): void
    {
        // dd($this->customer_id);
        $data = $this->validate([
            'date' => ['required', 'date'],
            'productSelected' => ['required', 'array', 'min:1'],
            'productSelected.*.product_id' => ['required', 'exists:products,id'],
            'productSelected.*.qty' => ['required', 'numeric', 'min:1'],
            'productSelected.*.price' => ['required', 'numeric', 'min:1'],
            'productSelected.*.subtotal' => ['required', 'numeric', 'min:1'],
            'total_price' => ['required', 'numeric', 'min:1']
        ]);


        try {
            DB::beginTransaction();

            $po = PurchaseOrder::create([
                'date' => $data['date'],
                'total_price' => $data['total_price'],
            ]);

            foreach ($data['productSelected'] as $prod) {
                $detail = PurchaseOrderDetail::create([
                    'purchase_order_id' => $po->id,
                    'product_id' => $prod['product_id'],
                    'quantity' => $prod['qty'],
                    'price' => $prod['price'],
                    'subtotal' => $prod['subtotal'],
                ]);

                if ($detail) {
                    $product = Product::find($prod['product_id']);
                    if ($product) {
                        $product->update([
                            'stock' => $product->stock + $prod['qty']
                        ]);
                    }
                }
            }

            DB::commit();

            $this->success('Purchase Order created successfully.', position: 'toast-bottom', redirectTo: route('po'));
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->error('Failed to create Purchase Order.', position: 'toast-bottom');
            $this->logError($th);
        }
    }

}; ?>

<div>
    <!-- HEADER -->
    <x-header title="Form Purchase Order" separator>
        <x-slot:actions>
            <x-button label="Back" @click="$wire.back" responsive icon="fas.arrow-left" />
        </x-slot:actions>
    </x-header>

    <x-form wire:submit="save" no-separator>
        <div class="flex flex-col md:flex-row gap-4">
            <x-card shadow class="w-full md:w-1/3">
                <div>
                    <x-datepicker label="Date" wire:model="date" icon="o-calendar" :config="[
                        'altFormat' => 'd F Y'
                    ]"  required />
                </div>
            </x-card>
            <x-card shadow class="w-full md:w-2/3">
                <div>
                    <x-choices-offline
                    label="Product"
                    wire:model="product_id"
                    :options="$products"
                    placeholder="Search ..."
                    search-function="searchProduct"
                    @change-selection="$wire.selectProduct($event.detail.value)"
                    single
                    clearable
                    searchable
                    >
                    @scope('item', $product)
                        <x-list-item :item="$product" sub-value="code" />
                    @endscope

                    {{-- Selection slot--}}
                    @scope('selection', $product)
                        {{ $product->name }} ({{ $product->code }})
                    @endscope
                    </x-choices-offline>
                </div>
                <div>
                    <x-table :headers="[
                        [
                            'key' => 'name',
                            'label' => 'Product',
                        ],
                        [
                            'key' => 'price',
                            'label' => 'Price',
                        ],
                        [
                            'key' => 'qty',
                            'label' => 'Qty',
                            'class' => 'w-12',
                        ],
                        [
                            'key' => 'unit',
                            'label' => 'Unit',
                        ],
                        [
                            'key' => 'subtotal',
                            'label' => 'Subtotal',
                        ],
                    ]" :rows="$productSelected" show-empty-text>

                        @scope('cell_qty', $data)
                            <x-input type="number" wire:model.live="productSelected.{{ $loop->index }}.qty" min="1"
                                wire:change="recalculateSubtotal({{ $data['product_id'] }})" class="w-12" />
                        @endscope
                        @scope('cell_price', $data)
                            <x-input type="number" wire:model.live="productSelected.{{ $loop->index }}.price" min="0" wire:change="recalculateSubtotal({{ $data['product_id'] }})" class="w-32" />
                        @endscope
                        @scope('cell_subtotal', $data)
                            <p>Rp {{ number_format($data['subtotal'], 0, ',', '.') }}</p>
                        @endscope
                        @scope('actions', $data)
                            <x-button class="btn-error btn-sm" @click="$wire.removeProduct({{ $loop->index }})" icon="fas.trash"
                                spinner="removeProduct({{ $loop->index }})" />
                        @endscope
                        @if (count($productSelected) > 0)
                            <x-slot:footer class="bg-base-200 text-center">
                                <tr>
                                    <td colspan="4">Total Price</td>
                                    <td class="text-left">Rp {{ number_format($total_price, 0, ',', '.') }}</td>
                                    <td></td>
                                </tr>
                            </x-slot:footer>
                        @endif
                    </x-table>
                </div>
            </x-card>

            <x-slot:actions>
                <x-button label="Submit" type="submit" responsive icon="fas.check" spinner="save" />
            </x-slot:actions>
        </div>
    </x-form>
</div>
