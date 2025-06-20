<?php

use App\Models\Sales;
use Mary\Traits\Toast;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Title;
use Illuminate\Pagination\LengthAwarePaginator;

new #[Title('Sales')] class extends Component {
    use Toast, WithPagination;

    public string $search = '';
    public array $sortBy = ['column' => 'date', 'direction' => 'desc'];
    public int $perPage = 10;

    public string $roleUser = '';
    public string $route = '';

    public function mount(): void
    {
        $this->roleUser = Auth::user()->roles->first()->name;
        $this->route = $this->roleUser == 'customer' ? 'order' : 'sales';
    }

    public function create(): void
    {
        $this->redirect(route($this->route.'.form'), navigate: true);
    }

    public function detail(Sales $sales): void
    {
        $this->redirect(route($this->route.'.detail', $sales), navigate: true);
    }

    public function datas(): LengthAwarePaginator
    {
        $role = auth()->user()->roles->first()->name;
        $query = Sales::query()
            ->withAggregate('customer', 'name')
            ->withAggregate('actionBy', 'name')
            ->withAggregate('payment', 'status')
            ->withAggregate('distribution', 'status');

        if ($role === 'customer') {
            $query->where('customer_id', auth()->user()->customer_id);
        }

        return $query->where(function($q) {
                $q->where('invoice', 'like', "%{$this->search}%")
                    ->orWhere('date', 'like', "%{$this->search}%")
                    ->orWhereHas('customer', function($q) {
                        $q->where('name', 'like', "%{$this->search}%");
                    })
                    ->orWhereHas('actionBy', function($q) {
                        $q->where('name', 'like', "%{$this->search}%");
                    });
            })
            ->orderBy($this->sortBy['column'], $this->sortBy['direction'])
            ->paginate($this->perPage);
    }

    public function headers(): array
    {
        $array = [
            ['key' => 'date', 'label' => 'Date'],
            ['key' => 'invoice', 'label' => 'Invoice'],
            ['key' => 'total_price', 'label' => 'Total Price'],
            ['key' => 'status', 'label' => 'Status'],
            ['key' => 'payment_status', 'label' => 'Payment Status'], 
            ['key' => 'distribution_status', 'label' => 'Distribution Status'],
            ['key' => 'created_at', 'label' => 'Created at'],
        ];

        if ($this->roleUser !== 'customer') {
            array_splice($array, 2, 0, [['key' => 'customer_name', 'label' => 'Customer']]);
            array_splice($array, -1, 0, [['key' => 'action_by_name', 'label' => 'Action by']]);
        }

        return $array;
    }

    public function with(): array
    {
        return [
            'datas' => $this->datas(),
            'headers' => $this->headers(),
        ];
    }

}; ?>

<div>
    <!-- HEADER -->
    <x-header title="{{ $roleUser == 'customer' ? 'Order' : 'Sales' }}" separator>
        <x-slot:actions>
            <x-button label="Create" @click="$wire.create" responsive icon="fas.plus" spinner="create" />
        </x-slot:actions>
    </x-header>

    <div class="flex justify-end items-center gap-5">
        <x-input placeholder="Search..." wire:model.live="search" clearable icon="o-magnifying-glass" />
    </div>

    <!-- TABLE  -->
    <x-card class="mt-4" shadow>
        <x-table :headers="$headers" :rows="$datas" :sort-by="$sortBy" per-page="perPage" :per-page-values="[10, 25, 50, 100]"
            with-pagination show-empty-text @row-click="$wire.detail($event.detail)">
            @scope('cell_date', $data)
                <p>{{ \Carbon\Carbon::parse($data->date)->locale('id')->translatedFormat('d F Y') }}</p>
            @endscope
            @scope('cell_total_price', $data)
                <p>Rp {{ number_format($data->total_price, 0, ',', '.') }}</p>
            @endscope
            @scope('cell_status', $data)
                <x-status :status="$data->status" />
            @endscope
            @scope('cell_payment_status', $data)
                @if($data->payment_status)
                    <x-badge value="Paid" class="badge-success text-sm text-white" />
                @else
                    <x-badge value="Pending" class="badge-soft text-sm text-white" />
                @endif
            @endscope
            @scope('cell_distribution_status', $data)
                @if ($data->distribution_status)
                    <x-status :status="$data->distribution_status" />
                @else 
                    <x-status status="pending" />
                @endif
            @endscope
            @scope('cell_action_by_name', $data)
                <p>{{ $data->actionBy ? $data->actionBy->name : '-' }}</p>
            @endscope
            @scope('cell_created_at', $data)
                <p>{{ \Carbon\Carbon::parse($data->created_at)->locale('id')->translatedFormat('d F Y') }}</p>
            @endscope
        </x-table>
    </x-card>
</div>
