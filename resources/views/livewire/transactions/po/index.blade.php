<?php

use Mary\Traits\Toast;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\PurchaseOrder;
use Livewire\Attributes\Title;
use Illuminate\Pagination\LengthAwarePaginator;

new #[Title('Purchase Order')] class extends Component {
    use Toast, WithPagination;

    public string $search = '';
    public array $sortBy = ['column' => 'date', 'direction' => 'desc'];
    public int $perPage = 10;

    public function create(): void
    {
        $this->redirect(route('po.form'), navigate: true);
    }

    public function detail(PurchaseOrder $po): void
    {
        $this->redirect(route('po.detail', $po), navigate: true);
    }

    public function datas(): LengthAwarePaginator
    {
        return PurchaseOrder::query()
            ->where('invoice', 'like', "%{$this->search}%")
            ->orWhere('date', 'like', "%{$this->search}%")
            ->orderBy($this->sortBy['column'], $this->sortBy['direction'])
            ->paginate($this->perPage);
    }

    public function headers(): array
    {
        return [
            ['key' => 'date', 'label' => 'Date'],
            ['key' => 'invoice', 'label' => 'Invoice'],
            ['key' => 'total_price', 'label' => 'Total Price'],
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
    <x-header title="Purchase Order" separator>
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
            @scope('cell_created_at', $data)
                <p>{{ \Carbon\Carbon::parse($data->created_at)->locale('id')->translatedFormat('d F Y') }}</p>
            @endscope
        </x-table>
    </x-card>
</div>
