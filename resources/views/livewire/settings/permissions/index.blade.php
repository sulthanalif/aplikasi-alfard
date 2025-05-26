<?php

use Mary\Traits\Toast;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Illuminate\Pagination\LengthAwarePaginator;

new #[Title('Permissions')] class extends Component {
    use Toast, WithPagination;

    public bool $modal = false;

    public string $search = '';
    public array $sortBy = ['column' => 'name', 'direction' => 'asc'];
    public int $perPage = 10;
    public array $selected = [];

    public ?int $id = null;
    public string $name;

    public function save(): void
    {
        $input = $this->validate([
            'name' => 'required|string|max:50',
        ]);

        try {
            DB::beginTransaction();

            Permission::updateOrCreate(['id' => $this->id], $input);

            DB::commit();
            $this->success('Permission saved successfully.', position: 'toast-bottom');
            $this->modal = false;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Failed to save permission.', position: 'toast-bottom');
        }
    }

    public function delete(): void
    {
        try {
            DB::beginTransaction();

            Permission::destroy($this->selected);

            DB::commit();
            $this->success('Permissions deleted successfully.', position: 'toast-bottom');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Failed to delete permissions.', position: 'toast-bottom');
        }
    }

    public function datas(): LengthAwarePaginator
    {
        return Permission::query()
            ->where('name', 'like', "%{$this->search}%")
            ->orderBy($this->sortBy['column'], $this->sortBy['direction'])
            ->paginate($this->perPage);
    }

    public function headers(): array
    {
        return [['key' => 'name', 'label' => 'Name', 'class' => 'w-64'], ['key' => 'created_at', 'label' => 'Created at', 'class' => 'w-64']];
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
            $wire.id = null;
            $wire.name = '';
        });

        $js('edit', (permission) => {
            $wire.modal = true;
            $wire.id = permission.id;
            $wire.name = permission.name;
        });
    </script>
@endscript

<div>
    <!-- HEADER -->
    <x-header title="Permissions" separator>
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
            wire:model.live="selected" selectable with-pagination>
            @scope('actions', $data)
                <div class="flex gap-2">
                    <x-button icon="fas.pencil" @click="$js.edit({{ $data }})"
                        class="btn-ghost btn-sm text-primary" />
                </div>
            @endscope
        </x-table>

        @if ($selected)
            <div class="flex justify-end gap-2 mx-4 my-2">
                <x-button label="Delete" wire:click="delete" wire:confirm="Are you sure?"
                    spinner class=" btn-sm text-error" />
            </div>
        @endif
    </x-card>

    <x-modal wire:model="modal" title="Form Permission">
        <x-form wire:submit="save" no-separator>

            <x-input label="Name" wire:model="name"  />


            <x-slot:actions>
                <x-button label="save" type="submit" spinner="save" class="btn-primary" />
            </x-slot:actions>
        </x-form>
    </x-modal>
</div>
