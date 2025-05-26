<?php

use Mary\Traits\Toast;
use App\Models\Category;
use Illuminate\Support\Str;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Traits\CreateOrUpdate;
use Livewire\Attributes\Title;
use Illuminate\Pagination\LengthAwarePaginator;

new #[Title('Categories')] class extends Component {
    use Toast, CreateOrUpdate, WithPagination;

    public bool $modal = false;

    public string $search = '';
    public array $sortBy = ['column' => 'created_at', 'direction' => 'asc'];
    public int $perPage = 10;

    public string $name = '';
    public string $description = '';
    public bool $status = true;

    public function mount(): void
    {
        $this->setModel(new Category());
    }

    public function save(): void
    {
        $this->saveOrUpdate(
            validationRules: [
                'name' => ['required', 'string', 'max:50'],
                'description' => ['nullable', 'string', 'max:255'],
            ],
            beforeSave: function ($record, $component) {
                $record->slug = Str::slug($component->name);
            }
        );
    }


    public function delete(): void
    {
        $this->deleteData();
    }

    public function datas(): LengthAwarePaginator
    {
        return Category::query()
            ->where('name', 'like', "%{$this->search}%")
            ->orderBy($this->sortBy['column'], $this->sortBy['direction'])
            ->paginate($this->perPage);
    }

    public function headers(): array
    {
        return [
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'description', 'label' => 'Description'],
            ['key' => 'status', 'label' => 'Status'],
            ['key' => 'created_at', 'label' => 'Created at']
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

@script
    <script>
        $js('create', () => {
            $wire.modal = true;
            $wire.recordId = null;
            $wire.name = '';
            $wire.description = '';
            $wire.status = true;
            $wire.$refresh();
        });

        $js('edit', (category) => {
            $wire.modal = true;
            console.log(category);

            $wire.recordId = category.id;
            $wire.name = category.name;
            $wire.description = category.description;
            $wire.status = category.status;
            $wire.$refresh();
        });
    </script>
@endscript

<div>
    <!-- HEADER -->
    <x-header title="Categories" separator>
        <x-slot:actions>
            <x-button label="Create" @click="$js.create" responsive icon="fas.plus" />
        </x-slot:actions>
    </x-header>

    <div class="flex justify-end items-center gap-5">
        <x-input placeholder="Search..." wire:model.live="search" clearable icon="o-magnifying-glass" />
    </div>

    <!-- TABLE  -->
    <x-card class="mt-4" shadow>
        <x-table :headers="$headers" :rows="$datas" :sort-by="$sortBy" per-page="perPage" :per-page-values="[10, 25, 50, 100]"
            with-pagination show-empty-text @row-click="$js.edit($event.detail)">
            @scope('cell_status', $data)
                <p>{{ $data->status ? 'Active' : 'Inactive' }}</p>
            @endscope
            @scope('cell_created_at', $data)
                <p>{{ \Carbon\Carbon::parse($data->created_at)->locale('id')->translatedFormat('d F Y') }}</p>
            @endscope
        </x-table>
    </x-card>

    @include('livewire.categories.form')
</div>
