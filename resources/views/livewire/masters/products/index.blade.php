<?php

use App\Models\Unit;
use Mary\Traits\Toast;
use App\Models\Product;
use App\Models\Category;
use App\Exports\ExportDatas;
use App\Imports\ImportDatas;
use App\Traits\LogFormatter;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Traits\CreateOrUpdate;
use Livewire\Attributes\Title;
use Illuminate\Support\Collection;
use App\Exports\ExportDatasTemplate;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Pagination\LengthAwarePaginator;

new #[Title('Products')] class extends Component {
    use Toast, CreateOrUpdate, WithPagination, LogFormatter;

    public bool $modal = false;
    public bool $modalImport = false;

    public string $search = '';
    public array $sortBy = ['column' => 'created_at', 'direction' => 'asc'];
    public int $perPage = 10;

    public Collection $categoriesSearchable;
    public Collection $unitsSearchable;

    public string $code = '';
    public string $name = '';
    public string $description = '';
    public ?int $category_id = null;
    public ?int $unit_id = null;
    public int $price = 0;
    public int $purchase_price = 0;
    public int $stock = 0;
    public bool $status = true;

    public function mount(): void
    {
        $this->setModel(new Product());
        $this->searchCategory();
        $this->searchUnit();
    }

    public function downloadTemplate()
    {
        return Excel::download(new ExportDatasTemplate($this->model, 'Products Template', ['status', 'image']), 'template-products.xlsx');
    }

    public function import()
    {
        $this->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        try {
            Excel::import(new ImportDatas($this->model, ['status', 'image']), $this->file);

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
                    'code' => $data->code,
                    'name' => $data->name,
                    'description' => $data->description,
                    'category' => $data->category->name,
                    'unit' => $data->unit->name,
                    'price' => $data->price,
                    'purchase_price' => $data->purchase_price,
                    'stock' => $data->stock,
                    'status' => $data->status ? 'Active' : 'Inactive',
                    'created_at' => $data->created_at,
                ];
            });

            $headers = [
                'CODE', 'NAME', 'DESCRIPTION', 'CATEGORY', 'UNIT', 'PRICE', 'PURCHASE PRICE', 'STOCK', 'STATUS', 'CREATED_AT'
            ];

            $this->success('Data exported successfully!', position: 'toast-bottom');
            return Excel::download(new ExportDatas($datas, 'Products', $headers), 'products.xlsx');

        } catch (\Exception $e) {
            $this->logError($e);
            $this->error('Failed to export data!', position: 'toast-bottom');
        }
    }

    public function searchCategory(string $value = '')
    {
        $selectedOption = Category::where('id', $this->category_id)->get();

        $this->categoriesSearchable = Category::query()
            ->where('name', 'like', "%{$value}%")
            ->orderBy('name')
            ->get()
            ->merge($selectedOption);
    }

    public function searchUnit(string $value = '')
    {
        $selectedOption = Unit::where('id', $this->unit_id)->get();

        $this->unitsSearchable = Unit::query()
            ->where('name', 'like', "%{$value}%")
            ->orderBy('name')
            ->get()
            ->merge($selectedOption);
    }

    public function save(): void
    {
        $this->saveOrUpdate(
            validationRules: [
                'code' => ['required', 'string', 'max:50', $this->recordId ? Rule::unique('products', 'code')->ignore($this->recordId) : 'unique:products,code'],
                'name' => ['required', 'string', 'max:50'],
                'description' => ['nullable', 'string', 'max:255'],
                'category_id' => ['required'],
                'unit_id' => ['required'],
                'price' => ['required', 'numeric'],
                'purchase_price' => ['required', 'numeric'],
                'stock' => ['required', 'numeric'],
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
        return Product::query()
            ->withAggregate('category', 'name')
            ->withAggregate('unit', 'name')
            ->where('name', 'like', "%{$this->search}%")
            ->orWhere('code', 'like', "%{$this->search}%")
            ->orWhereHas('category', function($query) {
                $query->where('name', 'like', "%{$this->search}%");
            })
            ->orderBy($this->sortBy['column'], $this->sortBy['direction'])
            ->paginate($this->perPage);
    }

    public function headers(): array
    {
        return [
            ['key' => 'code', 'label' => 'Code'],
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'category_name', 'label' => 'Category'],
            ['key' => 'price', 'label' => 'Price'],
            ['key' => 'purchase_price', 'label' => 'Purchase Price'],
            ['key' => 'stock', 'label' => 'Stock'],
            ['key' => 'unit_name', 'label' => 'Unit'],
            ['key' => 'status', 'label' => 'Status'],
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

@script
    <script>
        $js('create', () => {
            $wire.modal = true;
            $wire.recordId = null;
            $wire.code = '';
            $wire.name = '';
            $wire.description = '';
            $wire.category_id = null;
            $wire.unit_id = null;
            $wire.price = 0;
            $wire.purchase_price = 0;
            $wire.stock = 0;
            $wire.status = true;
            $wire.$refresh();
        });
        $js('edit', (product) => {
            $wire.modal = true;
            $wire.recordId = product.id;
            $wire.code = product.code;
            $wire.name = product.name;
            $wire.description = product.description;
            $wire.category_id = product.category_id;
            $wire.unit_id = product.unit_id;
            $wire.price = product.price;
            $wire.purchase_price = product.purchase_price;
            $wire.stock = product.stock;
            $wire.status = product.status;
            $wire.$refresh();
        });
    </script>
@endscript

<div>
    <!-- HEADER -->
    <x-header title="Products" separator>
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
            @scope('cell_price', $data)
                <p>Rp {{ number_format($data->price, 0, ',', '.') }}</p>
            @endscope
            @scope('cell_purchase_price', $data)
                <p>Rp {{ number_format($data->purchase_price, 0, ',', '.') }}</p>
            @endscope
            @scope('cell_status', $data)
                <p>{{ $data->status ? 'Active' : 'Inactive' }}</p>
            @endscope
            @scope('cell_created_at', $data)
                <p>{{ \Carbon\Carbon::parse($data->created_at)->locale('id')->translatedFormat('d F Y') }}</p>
            @endscope
        </x-table>
    </x-card>

    @include('livewire.masters.products.form')
    <x-modalimport wire:model="modalImport" />
</div>
