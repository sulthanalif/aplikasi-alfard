<?php

use App\Models\Sales;
use Mary\Traits\Toast;
use App\Models\SalesDetail;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Exports\DynamicExport;
use Livewire\Attributes\Title;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Pagination\LengthAwarePaginator;

new #[Title('Report Sales')] class extends Component {
    use Toast, WithPagination;

    public string $start_date, $end_date;
    public string $search = '';
    public array $sortBy = ['column' => 'date', 'direction' => 'desc'];
    public int $perPage = 10;

    public function mount()
    {
        // dd(SalesDetail::sum('net_subtotal'));
        $this->start_date = now()->startOfMonth()->toDateString();
        $this->end_date = now()->endOfMonth()->toDateString();

        // dd($this->start_date);
        // $this->export();
    }

    public function detail(Sales $sales): void
    {
        $this->redirect(route('sales.detail', $sales), navigate: true);
    }

    public function export()
    {
        $title = 'Report Sales '. $this->start_date . ' to ' . $this->end_date;

        $stats = array_map(function($item) {
            return [$item['title'] => $item['value']];
        }, $this->stats());

        $data = $this->datas()->getCollection()->map(function($sale) {
            return [
                'date' => $sale->date,
                'invoice' => $sale->invoice,
                'customer' => $sale->customer_name,
                'total_price' => $sale->total_price,
                'total_net_income' => $sale->details->sum('net_subtotal'),
                'payment' => $sale->payment?->details->sum('amount') ?? 0,
                'payment_remaining' => $sale->payment?->remaining ?? $sale->total_price
            ];
        });

        $headers = [
            'Date',
            'Invoice',
            'Customer',
            'Total Price',
            'Total Net Income',
            'Payment',
            'Payment Remaining',
        ];
        // dd($data);

        return Excel::download(new DynamicExport($title, $stats, $data, $headers), $title . '.xlsx');
    }

    public function stats(): array
    {
        $total_income = Sales::where('status', 'approved')
            ->whereBetween('created_at', [$this->start_date, $this->end_date])
            ->sum('total_price');
        $total_paid = Sales::where('status', 'approved')
            ->whereBetween('created_at', [$this->start_date, $this->end_date])
            ->with('payment')
            ->get()
            ->sum(function ($sale) {
                return $sale->payment?->details->sum('amount') ?? 0;
            });
        $total_unpaid = Sales::where('status', 'approved')
            ->whereBetween('created_at', [$this->start_date, $this->end_date])
            ->with('payment')
            ->get()
            ->sum(function ($sale) {
                return $sale->payment?->remaining ?? $sale->total_price;
            });
        $total_net_income = Sales::where('status', 'approved')
            ->whereBetween('created_at', [$this->start_date, $this->end_date])
            ->with('details')
            ->get()
            ->sum(function ($sale) {
                return $sale->details->sum('net_subtotal');
            });

        return [
            [
                'title' => 'Total Income',
                'value' => 'Rp. ' . number_format($total_income, 0, ',', '.'),
                'icon' => 'fas.money-bill-wave',
                'color' => 'text-success',
            ],
            [
                'title' => 'Total Payment',
                'value' => 'Rp. ' . number_format($total_paid, 0, ',', '.'),
                'icon' => 'fas.money-bill-wave',
                'color' => 'text-success',
            ],
            [
                'title' => 'Total Remaining Payment',
                'value' => 'Rp. ' . number_format($total_unpaid, 0, ',', '.'),
                'icon' => 'fas.money-bill-wave',
                'color' => 'text-error',
            ],
            [
                'title' => 'Total Net Income',
                'value' => 'Rp. ' . number_format($total_net_income, 0, ',', '.'),
                'icon' => 'fas.money-bill-wave',
                'color' => 'text-success',
            ],
        ];
    }

    public function datas(): LengthAwarePaginator
    {
        return Sales::withAggregate('customer', 'name')
        // ->withAggregate('payment', 'remaining')
        ->with('details')
        ->where('status', 'approved')
        ->whereBetween('created_at', [$this->start_date, $this->end_date])
            ->when($this->search, function ($query) {
                $query->where('invoice', 'like', "%{$this->search}%")
                    ->orWhereHas('customer', function ($query) {
                        $query->where('name', 'like', "%{$this->search}%");
                    });
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
            ['key' => 'total_net_income', 'label' => 'Total Net Income'],
            ['key' => 'payment', 'label' => 'Payment'],
            ['key' => 'payment_remaining', 'label' => 'Remaining Payment'],
        ];
    }

    public function with(): array
    {
        return [
            'datas' => $this->datas(),
            'headers' => $this->headers(),
            'stats' => $this->stats(),
        ];
    }
}; ?>

<div>
    <!-- HEADER -->
    <x-header title="Report Sales" separator>
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
        <x-table :headers="$headers" :rows="$datas" :sort-by="$sortBy" per-page="perPage" :per-page-values="[10, 25, 50, 100]"
            with-pagination show-empty-text @row-click="$wire.detail($event.detail)">
            @scope('cell_total_price', $data)
                <p>Rp. {{ number_format($data->total_price, 0, ',', '.') }}</p>
            @endscope
            @scope('cell_date', $data)
                <p>{{ \Carbon\Carbon::parse($data->date)->locale('id')->translatedFormat('d F Y') }}</p>
            @endscope
            @scope('cell_total_net_income', $data)
                <p>Rp. {{ number_format($data->details?->sum('net_subtotal') ?? 0, 0, ',', '.') }}</p>
            @endscope
            @scope('cell_payment', $data)
                <p>Rp. {{ number_format($data->payment?->details?->sum('amount') ?? 0, 0, ',', '.') }}</p>
            @endscope
            @scope('cell_payment_remaining', $data)
                <p>Rp. {{ number_format($data->payment?->remaining ?? $data->total_price, 0, ',', '.') }}</p>
            @endscope
        </x-table>
    </x-card>
</div>
