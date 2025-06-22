<?php

use App\Models\Unit;
use Mary\Traits\Toast;
use App\Exports\ExportDatas;
use App\Imports\ImportDatas;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Traits\CreateOrUpdate;
use App\Exports\ExportDatasTemplate;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Title;

new #[Title('Units')] class extends Component {
    use Toast, CreateOrUpdate, WithPagination;

    public bool $modal = false;
    public bool $modalImport = false;

    public string $search = '';
    public array $sortBy = ['column' => 'created_at', 'direction' => 'asc'];
    public int $perPage = 10;

    public string $name = '';
    public string $description = '';
    public bool $status = true;

    public function mount(): void
    {
        $this->setModel(new Unit());
    }

    public function downloadTemplate()
    {
        return Excel::download(new ExportDatasTemplate($this->model, 'Units Template', ['status']), 'template-units.xlsx');
    }

    public function import()
    {
        $this->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        try {
            Excel::import(new ImportDatas($this->model, ['status']), $this->file);

            $this->success('Data imported successfully!', position: 'toast-bottom');
        } catch (\Exception $e) {
            $this->logError($e);
            $this->error('Failed to import data.', position: 'toast-bottom');
        }
    }

    public function export()
    {
        $datas = $this->model->all();

        if (empty($datas)) return $this->error('No data found!', position: 'toast-bottom');

        try {
            $datas = $datas->map(function ($data) {
               return [
                    'name' => $data->name,
                    'description' => $data->description,
                    'status' => $data->status ? 'Active' : 'Inactive',
                    'created_at' => $data->created_at,
                ];
            });

            $headers = [
                'NAME', 'DESCRIPTION', 'STATUS', 'CREATED_AT'
            ];

            $this->success('Data exported successfully!', position: 'toast-bottom');
            return Excel::download(new ExportDatas($datas, 'Units', $headers), 'units.xlsx');

        } catch (\Exception $e) {
            $this->logError($e);
            $this->error('Failed to export data!', position: 'toast-bottom');
        }
    }

    public function save(): void
    {
        $this->saveOrUpdate(
            validationRules: [
                'name' => ['required', 'string', 'max:50'],
                'description' => ['nullable', 'string', 'max:255'],
                'status' => ['required', 'boolean'],
            ]
        );
    }

    public function delete(): void
    {
        $this->deleteData();
    }

    public function datas(): LengthAwarePaginator
    {
        return Unit::query()
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

        $js('edit', (unit) => {
            $wire.modal = true;
            $wire.recordId = unit.id;
            $wire.name = unit.name;
            $wire.description = unit.description;
            $wire.status = unit.status;
            $wire.$refresh();
        });
    </script>
@endscript

<div>
<!-- HEADER -->
    <x-header title="Units" separator>
        <x-slot:actions>
            <x-button label="Import" @click="$wire.modalImport = true" responsive icon="fas.file-import" spinner="import" />
            <x-button label="Export" @click="$wire.export" responsive icon="fas.file-export" spinner="export" />
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

    @include('livewire.masters.units.form')
    <x-modalimport wire:model="modalImport" />
</div>
