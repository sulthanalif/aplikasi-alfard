<?php

use App\Models\User;
use App\Models\Sales;
use Mary\Traits\Toast;
use App\Traits\LogFormatter;
use Livewire\Volt\Component;
use Livewire\Attributes\Title;
use Illuminate\Support\Collection;

new #[Title('Form Distribution')] class extends Component {
    use Toast, LogFormatter;

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

        $this->selectedSales[] = [
            'sales_id' => $sales->id,
            'invoice' => $sales->invoice,
            'customer' => $sales->customer->name,
            'address' => $sales->address,
            'details' => $sales->details->toArray()
        ];

        // dd($this->selectedSales);
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

                    </x-table>
                </div>
            </x-card>

            <x-slot:actions>
                <x-button label="Submit" type="submit" responsive icon="fas.check" spinner="save" />
            </x-slot:actions>
        </div>
    </x-form>
</div>
