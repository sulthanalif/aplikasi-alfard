<?php

use Mary\Traits\Toast;
use App\Models\Product;
use App\Models\Category;
use App\Traits\LogFormatter;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Traits\CreateOrUpdate;
use Livewire\Attributes\Title;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

new #[Title('Products')] class extends Component {
    use Toast, CreateOrUpdate, WithPagination;

    public bool $modal = false;

    public string $search = '';
    public array $sortBy = ['column' => 'created_at', 'direction' => 'asc'];
    public int $perPage = 10;

    public Collection $categoriesSearchable;

    public string $code = '';
    public string $name = '';
    public string $description = '';
    public ?int $category_id = null;
    public int $price = 0;
    public int $stock = 0;
    public bool $status = true;

    public function mount(): void
    {
        $this->setModel(new Product());
        $this->searchCategory();
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

    public function save(): void
    {
        $this->saveOrUpdate(
            validationRules: [
                'code' => ['required', 'string', 'max:50', $this->recordId ? Rule::unique('products', 'code')->ignore($this->recordId) : 'unique:products,code'],
                'name' => ['required', 'string', 'max:50'],
                'description' => ['nullable', 'string', 'max:255'],
                'category_id' => ['required'],
                'price' => ['required', 'numeric'],
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
            ['key' => 'stock', 'label' => 'Stock'],
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
            $wire.price = 0;
            $wire.stock = 0;
            $wire.status = true;
            $wire.$refresh();
        });
        $js('edit', (product) => {
            $wire.modal = true;
            console.log(product);

            $wire.recordId = product.id;
            $wire.code = product.code;
            $wire.name = product.name;
            $wire.description = product.description;
            $wire.category_id = product.category_id;
            $wire.price = product.price;
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
            @scope('cell_status', $data)
                <p>{{ $data->status ? 'Active' : 'Inactive' }}</p>
            @endscope
            @scope('cell_created_at', $data)
                <p>{{ \Carbon\Carbon::parse($data->created_at)->locale('id')->translatedFormat('d F Y') }}</p>
            @endscope
        </x-table>
    </x-card>

    @include('livewire.products.form')
</div>
