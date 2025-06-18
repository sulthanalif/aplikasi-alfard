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

    public function create(): void
    {
        $this->redirect(route('sales.form'), navigate: true);
    }

    public function detail(Sales $sales): void
    {
        $this->redirect(route('sales.detail', $sales), navigate: true);
    }

    public function datas(): LengthAwarePaginator
    {
        return Sales::query()
            ->withAggregate('customer', 'name')
            ->withAggregate('actionBy', 'name')
            ->withAggregate('payment', 'status')
            ->withAggregate('distribution', 'status')
            ->where('invoice', 'like', "%{$this->search}%")
            ->orWhere('date', 'like', "%{$this->search}%")
            ->orWhereHas('customer', function($query) {
                $query->where('name', 'like', "%{$this->search}%");
            })
            ->orWhereHas('actionBy', function($query) {
                $query->where('name', 'like', "%{$this->search}%");
            })
            ->orderBy($this->sortBy['column'], $this->sortBy['direction'])
            ->paginate($this->perPage);
    }

    public function headers(): array
    {
        return [
            ['key' => 'date', 'label' => 'Date'],
            ['key' => 'invoice', 'label' => 'Invoice'],
            ['key' => 'customer_name', 'label' => 'Customer'],
            ['key' => 'total_price', 'label' => 'Total Price'],
            ['key' => 'status', 'label' => 'Status'],
            ['key' => 'payment_status', 'label' => 'Payment Status'],
            ['key' => 'distribution_status', 'label' => 'Distribution Status'],
            ['key' => 'action_by_name', 'label' => 'Action by'],
            ['key' => 'created_at', 'label' => 'Created at'],
        ];
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
    <x-header title="Sales" separator>
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
