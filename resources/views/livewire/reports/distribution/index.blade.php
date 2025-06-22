<?php

use App\Models\Sales;
use Mary\Traits\Toast;
use App\Models\Distribution;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Exports\DynamicExport;
use Livewire\Attributes\Title;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Pagination\LengthAwarePaginator;

new #[Title('Report Distribution')] class extends Component {
    use Toast, WithPagination;

    public string $start_date, $end_date;
    public string $search = '';
    // public array $sortBy = ['column' => 'c', 'direction' => 'desc'];
    public int $perPage = 10;

    public function mount()
    {
        $this->start_date = now()->startOfMonth()->toDateString();
        $this->end_date = now()->endOfMonth()->toDateString();
    }

    public function detail(Sales $sales): void
    {
        if (!$sales->distribution) {
            $this->error('No distribution data available', position: 'toast-bottom');
            return;
        }

        $this->redirect(route('distributions.detail', $sales->distribution->distribution), navigate: true);
    }

    public function export()
    {
        $title = 'Report Distribution '. $this->start_date . ' to ' . $this->end_date;

        $stats = array_map(function($item) {
            return [$item['title'] => $item['value']];
        }, $this->stats());

        $data = $this->datas()->getCollection()->map(function($sale) {
            return [
                'date' => $sale->date,
                'invoice' => $sale->invoice,
                'customer' => $sale->customer->name,
                'distribution_number' => $sale->distribution?->distribution->number,
                'driver' => $sale->distribution?->distribution->driver->name,
                'ship_at' => $sale->distribution?->shipment_at,
                'delivered_at' => $sale->distribution?->delivered_at,
            ];
        });

        $headers = [
            'Date Sales',
            'Invoice',
            'Customer',
            'Distribution Number',
            'Driver',
            'Ship At',
            'Delivered At',
        ];
        // dd($data);

        return Excel::download(new DynamicExport($title, $stats, $data, $headers), $title . '.xlsx');
    }

    public function stats(): array
    {
        $total_waiting_distribution = Sales::where('status', 'approved')
            ->whereDoesntHave('distribution')
            ->whereBetween('date', [$this->start_date, $this->end_date])
            ->count();
        $total_pending = Sales::whereHas('distribution', fn ($query) => $query->where('status', 'pending'))
            ->whereBetween('date', [$this->start_date, $this->end_date])
            ->count();
        $total_shipping = Sales::whereHas('distribution', fn ($query) => $query->where('status', 'shipped'))
            ->whereBetween('date', [$this->start_date, $this->end_date])
            ->count();
        $total_delivered = Sales::whereHas('distribution', fn ($query) => $query->where('status', 'delivered'))
            ->whereBetween('date', [$this->start_date, $this->end_date])
            ->count();

        return [
            [
                'title' => 'Total Waiting',
                'value' => $total_waiting_distribution,
                'icon' => 'fas.triangle-exclamation',
                'color' => 'text-yellow-500',
            ],
            [
                'title' => 'Total Pending',
                'value' => $total_pending,
                'icon' => 'fas.clock',
                'color' => 'text-info',
            ],
            [
                'title' => 'Total Shipping',
                'value' => $total_shipping,
                'icon' => 'fas.truck-fast',
                'color' => 'text-yellow-500',
            ],
            [
                'title' => 'Total Delivered',
                'value' => $total_delivered,
                'icon' => 'fas.check',
                'color' => 'text-success',
            ],
        ];
    }

    public function datas(): LengthAwarePaginator
    {
        return Sales::query()
            ->withAggregate('customer', 'name')
            // ->withAggregate('distribution.driver', 'name')
            ->with('distribution')
            ->whereBetween('date', [$this->start_date, $this->end_date])
            ->where(function($q) {
                $q->where('invoice', 'like', "%{$this->search}%")
                    // ->orWhere('date', 'like', "%{$this->search}%")
                    ->orWhereHas('customer', function($q) {
                        $q->where('name', 'like', "%{$this->search}%");
                    });
            })
            ->orderBy('date', 'desc')
            ->paginate($this->perPage);
    }

    public function headers(): array
    {
        return [
            ['key' => 'date', 'label' => 'Sales Date'],
            ['key' => 'invoice', 'label' => 'Invoice'],
            ['key' => 'customer_name', 'label' => 'Customer'],
            ['key' => 'distribution.distribution.number', 'label' => 'Distribution Number'],
            ['key' => 'distribution.distribution.driver.name', 'label' => 'Driver Name'],
            ['key' => 'shipped', 'label' => 'Shipped'],
            ['key' => 'delivered', 'label' => 'Delivered'],
        ];
    }

    public function with(): array
    {
        return [
            'stats' => $this->stats(),
            'datas' => $this->datas(),
            'headers' => $this->headers(),
        ];
    }
}; ?>

<div>
    <!-- HEADER -->
    <x-header title="Report Distribution" separator>
        <x-slot:actions>
            <x-button wire:click="export" label="Export" icon="fas.download" spinner="export" />
        </x-slot:actions>
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
        <x-table :headers="$headers" :rows="$datas"  per-page="perPage" :per-page-values="[10, 25, 50, 100]"
            with-pagination show-empty-text @row-click="$wire.detail($event.detail)">
            @scope('cell_date', $data)
                <p>{{ \Carbon\Carbon::parse($data->date)->locale('id')->translatedFormat('d F Y') }}</p>
            @endscope
            @scope('cell_distribution.distribution.number', $data)
                <p>{{ $data->distribution?->distribution?->number ?? '-' }}</p>
            @endscope
            @scope('cell_distribution.distribution.driver.name', $data)
                <p>{{ $data->distribution?->distribution?->driver?->name ?? '-' }}</p>
            @endscope
            @scope('cell_shipped', $data)
                <p>{{ $data->distribution?->shipment_at ? \Carbon\Carbon::parse($data->distribution?->shipment_at)->locale('id')->translatedFormat('d F Y H:i') : '-' }}</p>
            @endscope
            @scope('cell_delivered', $data)
                <p>{{ $data->distribution?->delivered_at ? \Carbon\Carbon::parse($data->distribution?->delivered_at)->locale('id')->translatedFormat('d F Y H:i') : '-' }}</p>
            @endscope
        </x-table>
    </x-card>
</div>
