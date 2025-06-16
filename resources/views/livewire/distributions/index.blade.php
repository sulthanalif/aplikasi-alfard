<?php

use Mary\Traits\Toast;
use App\Models\Distribution;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Title;
use Illuminate\Pagination\LengthAwarePaginator;

new #[Title('Distributions')] class extends Component {
    use Toast, WithPagination;

    public string $search = '';
    public array $sortBy = ['column' => 'date', 'direction' => 'desc'];
    public int $perPage = 10;

    public function datas(): LengthAwarePaginator
    {
       return Distribution::query()
            ->withAggregate('driver', 'name')
            ->where('number', 'like', "%{$this->search}%")
            ->orWhere('date', 'like', "%{$this->search}%")
            ->orWhereHas('driver', function ($query) {
                $query->where('name', 'like', "%{$this->search}%")
                    ->whereHas('roles', function($q) {
                        $q->where('name', 'driver');
                    });
            })
            ->orderBy($this->sortBy['column'], $this->sortBy['direction'])
            ->paginate($this->perPage);
    }

    public function headers(): array
    {
        return [
            [
                'key' => 'date',
                'label' => 'Date',
            ],
            [
                'key' => 'number',
                'label' => 'Number',
            ],
            [
                'key' => 'user_name',
                'label' => 'Driver',
            ],
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
    <x-header title="Distributions" separator>
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
        </x-table>
    </x-card>
</div>

