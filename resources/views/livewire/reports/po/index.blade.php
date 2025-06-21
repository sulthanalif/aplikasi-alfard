<?php

use Mary\Traits\Toast;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\PurchaseOrder;
use Livewire\Attributes\Title;
use Illuminate\Pagination\LengthAwarePaginator;

new #[Title('Report PO')] class extends Component {
     use Toast, WithPagination;

    public string $start_date, $end_date;
    public string $search = '';
    public array $sortBy = ['column' => 'created_at', 'direction' => 'asc'];
    public int $perPage = 10;

    public function mount()
    {
        $this->start_date = now()->startOfMonth()->toDateString();
        $this->end_date = now()->endOfMonth()->toDateString();
    }

    public function detail(PurchaseOrder $po): void
    {
        $this->redirect(route('po.detail', $po), navigate: true);
    }

    public function stats(): array
    {
        $total_purchase = PurchaseOrder::whereBetween('created_at', [$this->start_date, $this->end_date])
            ->sum('total_price');

        return [
            [
                'title' => 'Total Purchase',
                'value' => 'Rp. '.number_format($total_purchase, 0, ',', '.'),
                'icon' => 'fas.money-bill-wave',
                'color' => 'success',
            ],
        ];
    }

    public function datas(): LengthAwarePaginator
    {
        return PurchaseOrder::query()
            ->whereBetween('created_at', [$this->start_date, $this->end_date])
            ->when($this->search, function ($query, $search) {
                $query->where('invoice', 'like', '%' . $search . '%');
            })
            ->orderBy($this->sortBy['column'], $this->sortBy['direction'])
            ->paginate($this->perPage);
    }

    public function headers(): array
    {
        return [
            ['key' => 'date', 'label' => 'Date'],
            ['key' => 'invoice', 'label' => 'Invoice'],
            // ['key' => 'supplier_id', 'label' => 'Supplier'],
            ['key' => 'total_price', 'label' => 'Total Price'],
        ];
    }

    public function with(): array
    {
        return [
            'datas' => $this->datas(),
            'stats' => $this->stats(),
            'headers' => $this->headers(),
        ];
    }

}; ?>

<div>
    <!-- HEADER -->
    <x-header title="Report Purchase Order" separator>
        //
    </x-header>

    <div class="flex justify-end items-center gap-5 mb-4">
        <x-datepicker label="Start Date" wire:model.live="start_date" icon="o-calendar" :config="[
            'altFormat' => 'd F Y'
        ]"  required inline />

        <x-datepicker label="End Date" wire:model.live="end_date" icon="o-calendar" :config="[
            'altFormat' => 'd F Y'
        ]"  required inline />

        <x-input label="Search" wire:model.live="search" placeholder='Search...'  inline/>
    </div>

    <x-statistic :stats="$stats" />

    <x-card class="mt-4" shadow>
        <x-table :headers="$headers" :rows="$datas" :sort-by="$sortBy" per-page="perPage" :per-page-values="[10, 25, 50, 100]"
            with-pagination show-empty-text @row-click="$wire.detail($event.detail)">
            @scope('cell_total_price', $data)
                <p>Rp. {{ number_format($data->total_price, 0, ',', '.') }}</p>
            @endscope
            @scope('cell_date', $data)
                <p>{{ \Carbon\Carbon::parse($data->date)->locale('id')->translatedFormat('d F Y') }}</p>
            @endscope
        </x-table>
    </x-card>
</div>
