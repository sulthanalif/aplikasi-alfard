<?php

use App\Models\User;
use App\Models\Sales;
use Mary\Traits\Toast;
use App\Models\Distribution;
use App\Traits\LogFormatter;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Title;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

new #[Title('Form Distribution')] class extends Component {
    use Toast, LogFormatter, WithPagination;

    public Collection $sales;
    public Collection $drivers;

    //varDistribution
    public string $number = '';
    public string $date = '';
    public ?int $user_id = null;
    public ?int $sales_id = null;

    public array $selectedSales = [];
    public array $products = [];

    public function mount(): void
    {
        $this->searchSales();
        $this->searchDrivers();
    }

    public function searchSales(string $value = '')
    {
        $selectedOption = Sales::where('id', $this->sales_id)->get();

        $this->sales = Sales::where('status', 'approved')
            ->whereDoesntHave('distribution')
            ->where('invoice', 'like', '%' . $value . '%')
            ->get()
            ->merge($selectedOption);
    }
    public function searchDrivers(string $value = '')
    {
        $selectedOption = User::where('id', $this->user_id)->get();

        $this->drivers = User::whereHas('roles', function ($query) {
                $query->where('name', 'driver');
            })
            ->where('name', 'like', '%' . $value . '%')
            ->get()
            ->merge($selectedOption);
    }

    public function selectSales($id): void
    {
        $sales = Sales::with('details', 'customer')->where('id', $id)->first();

        // Skip if same sales is selected
        if (collect($this->selectedSales)->where('sales_id', $sales->id)->isNotEmpty()) {
            return;
        }

        // Add new sales to selected sales array
        $this->selectedSales[] = [
            'sales_id' => $sales->id,
            'invoice' => $sales->invoice,
            'customer' => $sales->customer->name,
            'address' => $sales->address,
        ];

        $mappedProducts = $sales->details->map(function ($detail) {
            return [
                'sales_id' => $detail->sales_id,
                'product_id' => $detail->product_id,
                'name' => $detail->product->name,
                'quantity' => $detail->quantity,
            ];
        });

        // Merge new products with existing ones, summing quantities for same products
        $newProducts = $mappedProducts->groupBy('product_id')
            ->map(function ($group) {
                return [
                    'sales_id' => $group->first()['sales_id'],
                    'product_id' => $group->first()['product_id'],
                    'name' => $group->first()['name'],
                    'quantity' => $group->sum('quantity'),
                ];
            })->values();

        $existingProducts = collect($this->products);
        
        $mergedProducts = $newProducts->map(function ($newProduct) use ($existingProducts) {
            $existingProduct = $existingProducts->firstWhere('product_id', $newProduct['product_id']);
            
            if ($existingProduct) {
                return [
                    'sales_id' => $newProduct['sales_id'],
                    'product_id' => $newProduct['product_id'],
                    'name' => $newProduct['name'],
                    'quantity' => $newProduct['quantity'] + $existingProduct['quantity'],
                ];
            }
            
            return $newProduct;
        });

        $this->products = $mergedProducts->merge(
            $existingProducts->whereNotIn('product_id', $mergedProducts->pluck('product_id'))
        )->values()->toArray();
    }

    public function removeSales(int $sales_id): void
    {
        $this->selectedSales = collect($this->selectedSales)
            ->reject(fn($sale) => $sale['sales_id'] === $sales_id)
            ->values()
            ->toArray();

        // Get the sales details to subtract quantities
        $sales = Sales::with('details')->find($sales_id);
        $salesProducts = $sales->details->map(function ($detail) {
            return [
                'product_id' => $detail->product_id,
                'quantity' => $detail->quantity,
            ];
        });

        // Update product quantities and remove if zero
        $this->products = collect($this->products)
            ->map(function ($product) use ($salesProducts) {
                $salesProduct = $salesProducts->firstWhere('product_id', $product['product_id']);
                
                if ($salesProduct) {
                    $newQuantity = $product['quantity'] - $salesProduct['quantity'];
                    if ($newQuantity > 0) {
                        return array_merge($product, ['quantity' => $newQuantity]);
                    }
                    return null;
                }
                return $product;
            })
            ->filter()
            ->values()
            ->toArray();
    }

    public function save(): void
    {
        try {
            $rules = [
                'date' => 'required|date',
                'user_id' => 'required|integer',
                'selectedSales' => 'required|array',
                'selectedSales.*.sales_id' => 'required|integer',
            ];
    
            $this->validate($rules);
    
            DB::beginTransaction();
            $distribution = Distribution::create([
                'date' => $this->date,
                'user_id' => $this->user_id,
            ]);

            foreach ($this->selectedSales as $sales) {
                $distribution->details()->create([
                    'sales_id' => $sales['sales_id'],
                ]);
            }

            DB::commit();
            $this->success('Distribution saved successfully.', position: 'toast-bottom', redirectTo: route('distributions'));
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Failed to save distribution.', position: 'toast-bottom');
            $this->logError($e);
        }
    }

}; ?>

<div>
    <!-- HEADER -->
    <x-header title="Form Distribution" separator>
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
                <x-choices-offline
                    label="Driver"
                    wire:model="user_id"
                    :options="$drivers"
                    placeholder="Search ..."
                    search-function="searchDriver"
                    {{-- @change-selection="$wire.selectSales($event.detail.value)" --}}
                    single
                    clearable
                    searchable
                    required
                    >
                    @scope('item', $driver)
                        <x-list-item :item="$driver" />
                    @endscope

                    {{-- Selection slot--}}
                    @scope('selection', $driver)
                        {{ $driver->name }}
                    @endscope
                    </x-choices-offline>
                </div>
            </x-card>
            <x-card shadow class="w-full md:w-2/3">
                <div>
                    <x-choices-offline
                    label="Sales"
                    wire:model="sales_id"
                    :options="$sales"
                    placeholder="Search ..."
                    search-function="searchSales"
                    @change-selection="$wire.selectSales($event.detail.value)"
                    single
                    clearable
                    searchable
                    >
                    @scope('item', $sales)
                        <x-list-item :item="$sales" value="invoice" sub-value="customer.name" />
                    @endscope

                    {{-- Selection slot--}}
                    @scope('selection', $sales)
                        {{ $sales->invoice }}
                    @endscope
                    </x-choices-offline>
                </div>
                <div>
                    <x-table :headers="[
                        [
                            'key' => 'invoice',
                            'label' => 'Invoice',
                        ],
                        [
                            'key' => 'customer',
                            'label' => 'Customer',
                        ],
                        [
                            'key' => 'address',
                            'label' => 'Address',
                        ]
                    ]" :rows="$selectedSales" show-empty-text>
                    @scope('actions', $sales)
                        <x-button type="button" wire:click="removeSales({{ $sales['sales_id'] }})" responsive icon="fas.trash" spinner="removeSales({{ $sales['sales_id'] }})" />
                    @endscope
                    </x-table>
                </div>
            </x-card>
        </div>
        <div>
            <x-card title="Detail Product" shadow class='w-full'>
                <x-table :headers="[
                    [
                        'key' => 'name',
                        'label' => 'Name',
                    ],
                    [
                        'key' => 'quantity',
                        'label' => 'Quantity',
                    ]
                ]" :rows="$products" show-empty-text>

                </x-table>
            </x-card>
        </div>
        <x-slot:actions>
            <x-button label="Submit" type="submit" responsive icon="fas.check" spinner="save" />
        </x-slot:actions>
    </x-form>
</div>
