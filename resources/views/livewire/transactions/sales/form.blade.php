<?php

use App\Models\User;
use App\Models\Sales;
use Mary\Traits\Toast;
use App\Models\Product;
use App\Models\SalesDetail;
use App\Traits\LogFormatter;
use Livewire\Volt\Component;
use Livewire\Attributes\Title;
use Illuminate\Support\Collection;

new #[Title('Form Sales')] class extends Component {
    use Toast, LogFormatter;
    // public bool $is_customer = false;

    public Collection $customers;
    public Collection $products;

    public string $date = '';
    public ?string $customer_id = null;
    public string $address = '';
    public ?int $product_id = null;
    public array $productSelected = [];
    public float $total_price = 0;

    public function mount(): void
    {
        $this->date = now()->format('Y-m-d');
        $this->searchProduct();
        $this->searchCustomer();
        if (auth()->user()->hasRole('customer')) {
            $this->customer_id = auth()->user()->id;
            $this->address = auth()->user()->address;
        }

    }

    public function back(): void
    {
        $this->redirect(route('sales'), navigate: true);
    }

    public function searchCustomer(string $value = '')
    {
        $selectedOption = User::role('customer')->where('customer_id', $this->customer_id)->get();

        $this->customers = User::role('customer')
            ->where('status', true)
            ->where(function ($query) use ($value) {
                $query->where('customer_id', 'like', "%{$value}%")
                    ->orWhere('name', 'like', "%{$value}%");
            })
            ->orderBy('name')
            ->get()
            ->merge($selectedOption);
    }

    public function searchProduct(string $value = '')
    {
        $selectedOption = Product::where('id', $this->product_id)->get();

        $this->products = Product::query()
            ->when($value, function ($query, $value) {
                $query->where('code', 'like', "%{$value}%")
                    ->orWhere('name', 'like', "%{$value}%")
                    ->orWhereHas('category', function ($query) use ($value) {
                        $query->where('name', 'like', "%{$value}%");
                    });
            })
            ->orderBy('code')
            ->get()
            ->merge($selectedOption);
    }

    public function customerSelected($value)
    {
        $this->address = User::where('id', $value)->first()->address ?? '';
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
            'address' => ['required', 'string', 'max:255'],
            'customer_id' => ['required', 'exists:users,id'],
            'productSelected' => ['required', 'array', 'min:1'],
            'productSelected.*.product_id' => ['required', 'exists:products,id'],
            'productSelected.*.qty' => ['required', 'numeric', 'min:1'],
            'productSelected.*.subtotal' => ['required', 'numeric', 'min:1'],
            'total_price' => ['required', 'numeric', 'min:1']
        ]);


        try {
            DB::beginTransaction();

            $sales = Sales::create([
                'date' => $data['date'],
                'customer_id' => User::find($data['customer_id'])->customer_id,
                'address' => $this->address,
                'total_price' => $data['total_price'],
            ]);

            foreach ($data['productSelected'] as $prod) {
                SalesDetail::create([
                    'sales_id' => $sales->id,
                    'product_id' => $prod['product_id'],
                    'quantity' => $prod['qty'],
                    'subtotal' => $prod['subtotal'],
                ]);
            }

            DB::commit();

            $this->success('Sales created successfully.', position: 'toast-bottom', redirectTo: route('sales'));
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->error('Failed to create sales.', position: 'toast-bottom');
            $this->logError($th);
        }
    }

}; ?>

<div>
    <!-- HEADER -->
    <x-header title="Form Sales" separator>
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
                <div>
                    @role('customer')
                        <x-choices
                        label="Customer"
                        wire:model="customer_id"
                        :options="$customers"
                        placeholder="Search ..."
                        search-function="searchCustomer"
                        @change-selection="$wire.customerSelected($event.detail.value)"
                        {{-- debounce="300ms" --}}
                        {{-- min-chars="5" --}}
                        disabled
                        single
                        clearable
                        values-as-string
                        searchable >
                        {{-- Item slot --}}
                        @scope('item', $user)
                            <x-list-item :item="$user" value="customer_id" sub-value="name" />
                        @endscope

                        {{-- Selection slot--}}
                        @scope('selection', $user)
                            {{ $user->customer_id }} ({{ $user->name }})
                        @endscope
                        </x-choices>
                    @else
                        <x-choices
                        label="Customer"
                        wire:model="customer_id"
                        :options="$customers"
                        placeholder="Search ..."
                        search-function="searchCustomer"
                        @change-selection="$wire.customerSelected($event.detail.value)"
                        {{-- debounce="300ms" --}}
                        {{-- min-chars="5" --}}

                        single
                        clearable
                        values-as-string
                        searchable >
                        {{-- Item slot --}}
                        @scope('item', $user)
                            <x-list-item :item="$user" value="customer_id" sub-value="name" />
                        @endscope

                        {{-- Selection slot--}}
                        @scope('selection', $user)
                            {{ $user->customer_id }} ({{ $user->name }})
                        @endscope
                        </x-choices>
                    @endrole
                </div>
                <div>
                    <x-textarea label="Address" wire:model="address" rows="3" required />
                </div>
            </x-card>
            <x-card shadow class="w-full md:w-2/3">
                <div>
                    <x-choices
                    label="Product"
                    wire:model="product_id"
                    :options="$products"
                    placeholder="Search ..."
                    search-function="searchProduct"
                    @change-selection="$wire.selectProduct($event.detail.value)"
                    single
                    clearable
                    searchable
                    values-as-string
                    >
                    @scope('item', $product)
                        <x-list-item :item="$product" sub-value="code" />
                    @endscope


                    @scope('selection', $product)
                        {{ $product->name }} ({{ $product->code }})
                    @endscope
                    </x-choices>
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
                            <p>Rp {{ number_format($data['price'], 0, ',', '.') }}</p>
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
