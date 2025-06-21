<?php

use App\Models\Sales;
use Mary\Traits\Toast;
use App\Models\Distribution;
use App\Traits\LogFormatter;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Title;
use App\Models\DistributionDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

new #[Title('Detail Distribution')] class extends Component {
    use Toast, LogFormatter, WithPagination;

    public string $search = '';
    public array $sortBy = ['column' => 'date', 'direction' => 'desc'];
    public int $perPage = 10;
    public bool $modal = false;

    public Distribution $distribution;

    public ?int $id = null;
    public string $invoice = '';
    public string $customer = '';
    public bool $isDelivered = false;
    public array $products = [];

    public function mount(Distribution $distribution): void
    {
        $this->distribution = $distribution;
    }

    public function back(): void
    {
        $this->redirect(route('distributions'), navigate: true);
    }

    public function detail(DistributionDetail $distribution): void
    {
        $this->products = $distribution->sales->details->map(function ($detail) {
            return [
                'product' => $detail->product->name,
                'quantity' => $detail->quantity,
            ];
        })->toArray();
        $this->id = $distribution->id;
        $this->invoice = $distribution->sales->invoice;
        $this->customer = $distribution->sales->customer->name;
        $this->isDelivered = $distribution->status === 'delivered';
        $this->modal = true;
    }

    public function startShipment(): void
    {
        try {
            DB::beginTransaction();
            foreach ($this->distribution->details as $sales) {
                $sales->update([
                    'status' => 'shipped',
                    'shipment_at' => now(),
                ]);
            }

            $this->distribution->update([
                'status' => true,
            ]);

            DB::commit();
            $this->success('Shipment started!', position: 'toast-bottom');
        } catch (\Exception $th) {
            DB::rollBack();
            $this->error('Shipment failed!', position: 'toast-bottom');
            $this->logError($th);
        }
    }

    public function delivered(DistributionDetail $detail): void
    {
        $detail->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);

        $this->success('Delivered!', position: 'toast-bottom');
        $this->modal = false;
    }

    public function dataSales(): LengthAwarePaginator
    {
        return Sales::query()
            ->withAggregate('customer', 'name')
            ->withAggregate('actionBy', 'name')
            ->withAggregate('distribution', 'status')
            ->whereHas('distribution', function($query) {
                $query->where('distribution_id', $this->distribution->id);
            })
            ->when($this->search, function($query) {
                $query->where(function($q) {
                    $q->where('invoice', 'like', "%{$this->search}%")
                      ->orWhere('date', 'like', "%{$this->search}%")
                      ->orWhereHas('customer', function($sq) {
                          $sq->where('name', 'like', "%{$this->search}%");
                      });
                });
            })
            ->orderBy($this->sortBy['column'], $this->sortBy['direction'])
            ->paginate($this->perPage);
    }

    public function with(): array
    {
        return [
            'dataSales' => $this->dataSales(),
        ];
    }
}; ?>

<div>
    <!-- HEADER -->
    <x-header title="Detail Distribution" separator>
        <x-slot:actions>
            <x-button label="Back" @click="$wire.back" responsive icon="fas.arrow-left" spinner="back" />
        </x-slot:actions>
    </x-header>

    <div>
        <x-card>
            <div class="grid grid-cols-2 gap-2">
                <p class="font-bold">Distribution Number</p>
                <p>{{ $this->distribution->number ?? '-' }}</p>

                <p class="font-bold">Distribution Date</p>
                <p>{{ \Carbon\Carbon::parse($this->distribution->date)->locale('id')->translatedFormat('d F Y') }}</p>

                <p class="font-bold">Driver</p>
                <p>{{ $this->distribution->driver->name ?? '-' }}</p>

                <p class="font-bold">Status</p>
                <x-status :status="$this->distribution->status_text" />
            </div>
            <x-slot:actions>
                @if (!$this->distribution->status)
                    <x-button label="Start Shipment" @click="$wire.startShipment" spinner="startShipment" class="btn-primary" />
                @endif
            </x-slot:actions>
        </x-card>
    </div>
    <div class="flex justify-end items-center gap-5 mt-4">
        <x-input placeholder="Search..." wire:model.live="search" clearable icon="o-magnifying-glass" />
    </div>
    <div class="mt-4">
        <x-card title="Data Sales">
            <x-table :headers="[
                [
                    'key' => 'invoice',
                    'label' => 'Invoice',
                ],
                [
                    'key' => 'customer_name',
                    'label' => 'Customer',
                ],
                [
                    'key' => 'address',
                    'label' => 'Address',
                ],
                [
                    'key' => 'distribution_status',
                    'label' => 'Distribution Status'
                ],
            ]" :rows="$dataSales" show-empty-text @row-click="$wire.detail($event.detail.id)">
                @scope('cell_distribution_status', $data)
                @if ($data->distribution_status)
                    <x-status :status="$data->distribution_status" />
                @else
                    <x-status status="pending" />
                @endif
            @endscope
            </x-table>
        </x-card>
    </div>
    <x-modal wire:model="modal" title="{{$this->invoice}}" subtitle="{{$this->customer}}"  without-trap-focus>
        <x-card>
            <x-table :headers="[
                [
                    'key' => 'product',
                    'label' => 'Product',
                ],
                [
                    'key' => 'quantity',
                    'label' => 'Qty',
                ],
            ]" :rows="$products" show-empty-text>
            </x-table>
        </x-card>
        <x-slot:actions>
            @if (!$this->isDelivered)
                <x-button label="Delivered" wire:click="delivered({{ $this->id }})" spinner="delivered({{$this->id}})" class="btn-primary" />
            @endif
        </x-slot:actions>
    </x-modal>
</div>
