<?php

use App\Models\User;
use Mary\Traits\Toast;
use App\Traits\LogFormatter;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

new #[Title('Customers')] class extends Component {
    use Toast, WithPagination, LogFormatter;

    public bool $modal = false;

    public string $search = '';
    public array $sortBy = ['column' => 'status', 'direction' => 'asc'];
    public int $perPage = 10;

    public ?int $id = null;
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $address = '';
    public bool $status = true;

    public function save(): void
    {
        try {
            DB::beginTransaction();

            $user = User::find($this->id);

            if ($user->is_new) {
                $user->is_new = 0;
            }

            $user->status = !$user->status;
            $user->save();

            DB::commit();

            $this->modal = false;
            $this->success('Status customer updated successfully', position: 'toast-bottom');
        } catch (\Exception $th) {
            DB::rollBack();
            $this->logError($th);
            $this->error('Status customer updated failed', position: 'toast-bottom');
        }
    }

    public function datas(): LengthAwarePaginator
    {
        return User::query()
            ->whereHas('roles', function($query) {
                $query->where('name', 'customer');
            })
            ->when($this->search, function($query, $search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%');
            })
            ->orderBy($this->sortBy['column'], $this->sortBy['direction'])
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);
    }

    public function headers(): array
    {
        return [
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'email', 'label' => 'Email'],
            ['key' => 'phone', 'label' => 'Phone'],
            ['key' => 'status', 'label' => 'Status'],
            ['key' => 'created_at', 'label' => 'Created At'],
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
        $js('detail', (user) => {
            $wire.modal = true;
            $wire.id = user.id;
            $wire.name = user.name;
            $wire.email = user.email;
            $wire.status = user.status;
            $wire.address = user.address;
            $wire.phone = user.phone;
            // $wire.password = '';
            $wire.$refresh();
        });
    </script>
@endscript

<div>
    <!-- HEADER -->
    <x-header title="Customers" separator>
        <x-slot:actions>

        </x-slot:actions>
    </x-header>

    <div class="flex justify-end items-center gap-5">
        <x-input placeholder="Search..." wire:model.live="search" clearable icon="o-magnifying-glass" />
    </div>

    <!-- TABLE  -->
    <x-card class="mt-4" shadow>
        <x-table :headers="$headers" :rows="$datas" :sort-by="$sortBy" per-page="perPage" :per-page-values="[10, 25, 50, 100]"
            with-pagination show-empty-text @row-click="$js.detail($event.detail)">
            @scope('cell_status', $data)
                <x-status :status="$data->status ? 'active' : 'inactive'" />
            @endscope
        </x-table>
    </x-card>

    <x-modal wire:model="modal" title="Data Customer" box-class="w-full h-fit max-w-[600px]" without-trap-focus>

            <div>
                <x-input label="Name" wire:model="name"  readonly/>
            </div>

            <div>
                <x-input label="E-mail" wire:model="email" type="email"  readonly/>
            </div>

            <div>
                <x-textarea label="Address" wire:model="address" readonly/>
            </div>

            <div>
                <x-input label="Phone" wire:model="phone" type="number" readonly/>
            </div>

            <x-slot:actions>
                <x-button label="{{ $this->status ? 'Inactive' : 'Active' }}" @click="$wire.save" type="submit" spinner="save" class="{{ $this->status ? 'btn-error' : 'btn-success' }}" />
            </x-slot:actions>
    </x-modal>
</div>
