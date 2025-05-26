<?php

use App\Models\User;
use Livewire\Volt\Component;
use Livewire\Attributes\Title;
use Illuminate\Support\Collection;

new #[Title('Form Sales')] class extends Component {

    // public bool $is_customer = false;

    public Collection $customers;
    public Collection $products;

    public string $date = '';
    public ?string $customer_id = null;
    public string $address = '';
    public array $productSelected = [];
    public float $total_price = 0;

    public function mount(): void
    {
        $this->date = now()->format('Y-m-d');
        if (auth()->user()->hasRole('customer')) {
            $this->customer_id = auth()->user()->customer_id;
            $this->address = auth()->user()->address;
        } else {
            $this->searchCustomer();
        }
    }

    public function searchCustomer(string $value = '')
    {
        $selectedOption = User::role('customer')->where('customer_id', $this->customer_id)->get();

        $this->customers = User::role('customer')
            ->where('status', true)
            ->where('customer_id', 'like', "%{$value}%")
            ->orderBy('name')
            ->get()
            ->merge($selectedOption);
    }

    public function customerSelected($value)
    {
        $this->address = User::where('id', $value)->first()->address ?? '';
    }

}; ?>

<div>
    <!-- HEADER -->
    <x-header title="Form Sales" separator>
        <x-slot:actions>
            <x-button label="Back" @click="$wire.back" responsive icon="fas.arrow-left" />
        </x-slot:actions>
    </x-header>

    <x-form>
        <div class="flex flex-col md:flex-row gap-4">
            <x-card shadow class="w-full md:w-1/2">
                <div>
                    <x-datepicker label="Date" wire:model="date" icon="o-calendar" :config="[
                        'altFormat' => 'd F Y'
                    ]"  required />
                </div>
                <div>
                    @role('customer')
                        <x-input label="Customer" wire:model='customer_id' disabled />
                    @else
                        <x-choices-offline
                        label="Customer"
                        wire:model="customer_id"
                        :options="$customers"
                        placeholder="Search ..."
                        search-function="searchCustomer"
                        @change-selection="$wire.customerSelected($event.detail.value)"
                        debounce="300ms"
                        min-chars="5"
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
                        </x-choices-offline>
                    @endrole
                </div>
                <div>
                    <x-textarea label="Address" wire:model="address" rows="3" required />
                </div>
            </x-card>
            <x-card>

            </x-card>
        </div>
    </x-form>
</div>
