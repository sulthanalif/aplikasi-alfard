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

    public function create(): void
    {
        $this->redirect(route('distributions.form'), navigate: true);
    }

    public function detail(Distribution $distribution): void
    {
        $this->redirect(route('distributions.detail', $distribution), navigate: true);
    }

    public function datas(): LengthAwarePaginator
    {
        $user = auth()->user();

       return Distribution::query()
            ->when($user->hasRole('driver'), function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->withAggregate('driver', 'name')
            ->where(function($query) use ($user) {
                if ($user->hasRole('driver')) {
                    $query->where('user_id', $user->id);
                }

                $query->where(function($q) {
                    $q->where('number', 'like', "%{$this->search}%")
                      ->orWhere('date', 'like', "%{$this->search}%")
                      ->orWhereHas('driver', function ($subQ) {
                          $subQ->where('name', 'like', "%{$this->search}%")
                               ->whereHas('roles', function($roleQ) {
                                   $roleQ->where('name', 'driver');
                               });
                      });
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
                'key' => 'driver_name',
                'label' => 'Driver',
            ],
            [
                'key' => 'status_text',
                'label' => 'Status',
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
            @can('approve-distribution')
                <x-button label="Create" @click="$wire.create" responsive icon="fas.plus" spinner="create" />
            @endcan
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
            @scope('cell_status_text', $data)
                <x-status :status="$data->status_text" />
            @endscope
        </x-table>
    </x-card>
</div>

