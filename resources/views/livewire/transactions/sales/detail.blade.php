<?php

use App\Models\Sales;
use Mary\Traits\Toast;
use App\Traits\LogFormatter;
use Livewire\Volt\Component;
use Livewire\Attributes\Title;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

new #[Title('Detail Sales')] class extends Component {
    use Toast, LogFormatter;

    public array $productSelected = [];
    public Sales $sales;
    public bool $modal = false;
    public ?string $note = null;

    public function mount(Sales $sales): void
    {
        $this->sales = $sales;

        $this->productSelected = $sales->details->map(function ($detail) {
            return [
                'product_id' => $detail->product_id,
                'name' => $detail->product->name.' ('.$detail->product->code.')',
                'unit' => $detail->product->unit->name,
                'price' => $detail->product->price,
                'qty' => $detail->quantity,
                'subtotal' => $detail->subtotal,
            ];
        })->toArray();
    }

    public function back(): void
    {
        $this->redirect(route('sales'), navigate: true);
    }

    public function action(string $action = ''): void
    {
        if ($action == 'approved') $this->note = null;

        try {
            DB::beginTransaction();

            $this->sales->update([
                'status' => $action,
                'action_by' => auth()->user()->id,
                'note' => $this->note ?? null,
            ]);

            DB::commit();

            $this->success('Sales '.$action.' successfully.', position: 'toast-bottom');
            $this->modal = false;
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->error('Failed to update sales.', position: 'toast-bottom');
            $this->logError('debug', 'Failed to update sales.', $th);
        }
    }
}; ?>

@script
    <script>
        $js('rejected', () => {
            $wire.modal = true;
        })
    </script>
@endscript

<div>
    <!-- HEADER -->
    <x-header title="Detail Sales" separator>
        <x-slot:actions>
            <x-button label="Back" @click="$wire.back" responsive icon="fas.arrow-left" spinner="back" />
        </x-slot:actions>
    </x-header>

    <div class="grid grid-cols-2 gap-4">
        <x-card title="Data Customer" >
            <div class="grid grid-cols-2 gap-2">
                <p class="font-bold">Customer ID</p>
                <p>{{ $this->sales->customer_id ?? '-' }}</p>

                <p class="font-bold">Name</p>
                <p>{{ $this->sales->customer->name ?? '-' }}</p>

                <p class="font-bold">Phone</p>
                <p>{{ $this->sales->customer->phone ?? '-' }}</p>

                <p class="font-bold">Address</p>
                <p>{{ $this->sales->customer->address ?? '-' }}</p>
            </div>
        </x-card>
        <x-card title="Data Sales">
            <div class="grid grid-cols-2 gap-2">
                <p class="font-bold">Invoice</p>
                <p>{{ $this->sales->invoice ?? '-' }}</p>

                <p class="font-bold">Order Date</p>
                <p>{{ \Carbon\Carbon::parse($this->sales->date)->locale('id')->translatedFormat('d F Y') }}</p>

                <p class="font-bold">Status</p>
                <x-status :status="$this->sales->status" />

                <p class="font-bold">Note</p>
                <p>{{ $this->sales->note ?? '-' }}</p>
            </div>
        </x-card>
    </div>
    <div class="mt-4">
        <x-card title="Data Product">
            <x-table :headers="[
                [
                    'key' => 'name',
                    'label' => 'Product',
                ],
                [
                    'key' => 'price',
                    'label' => 'Price',
                ],
                [
                    'key' => 'qty',
                    'label' => 'Qty',
                    'class' => 'w-12',
                ],
                [
                    'key' => 'unit',
                    'label' => 'Unit',
                ],
                [
                    'key' => 'subtotal',
                    'label' => 'Subtotal',
                ],
            ]" :rows="$productSelected" show-empty-text>

                @scope('cell_price', $data)
                    <p>Rp {{ number_format($data['price'], 0, ',', '.') }}</p>
                @endscope
                @scope('cell_subtotal', $data)
                    <p>Rp {{ number_format($data['subtotal'], 0, ',', '.') }}</p>
                @endscope
                @if (count($productSelected) > 0)
                    <x-slot:footer class="bg-base-200 text-center">
                        <tr>
                            <td colspan="4">Total Price</td>
                            <td class="text-left">Rp {{ number_format($sales->total_price, 0, ',', '.') }}</td>
                        </tr>
                    </x-slot:footer>
                @endif
            </x-table>
            @if ($this->sales->status == 'pending')
            <x-slot:actions>
                <x-button label="Reject" class="btn-error text-white" @click="$js.rejected" responsive spinner="action('rejected')" />
                <x-button label="Approve" class="btn-success text-white" @click="$wire.action('approved')" responsive spinner="action('approved')" />
            </x-slot:actions>
            @endif
        </x-card>
    </div>
    <x-modal wire:model="modal" title="Reject Note" without-trap-focus>
        <x-form wire:submit="action('rejected')" no-separator>

            <x-textarea label="Note" wire:model="note" rows='5' required/>


            <x-slot:actions>
                <x-button label="Submit" type="submit" spinner="action('rejected')" class="btn-primary" />
            </x-slot:actions>
        </x-form>
    </x-modal>
</div>
