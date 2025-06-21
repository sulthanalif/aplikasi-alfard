<?php

use Mary\Traits\Toast;
use App\Models\Payment;
use App\Traits\LogFormatter;
use Livewire\Volt\Component;
use App\Models\PurchaseOrder;
use Livewire\WithFileUploads;
use Livewire\Attributes\Title;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

new #[Title('Detail Purchase Order')] class extends Component {
    use Toast, LogFormatter;

    public array $productSelected = [];
    public PurchaseOrder $po;
    public bool $modal = false;
    public ?string $note = null;

    public function mount(PurchaseOrder $po): void
    {
        $this->po = $po;

        $this->productSelected = $po->details->map(function ($detail) {
            return [
                'product_id' => $detail->product_id,
                'name' => $detail->product->name.' ('.$detail->product->code.')',
                'unit' => $detail->product->unit->name,
                'price' => $detail->product->purchase_price,
                'qty' => $detail->quantity,
                'subtotal' => $detail->subtotal,
            ];
        })->toArray();
    }

    public function back(): void
    {
        $this->redirect(route('po'), navigate: true);
    }

}; ?>


<div>
    <!-- HEADER -->
    <x-header title="Detail Purchase Order" separator>
        <x-slot:actions>
            <x-button label="Back" @click="$wire.back" responsive icon="fas.arrow-left" spinner="back" />
        </x-slot:actions>
    </x-header>

    <div>
        <x-card>
            <div class="grid grid-cols-2 gap-2">
                <p class="font-bold">Invoice</p>
                <p>{{ $this->po->invoice ?? '-' }}</p>

                <p class="font-bold">Order Date</p>
                <p>{{ \Carbon\Carbon::parse($this->po->date)->locale('id')->translatedFormat('d F Y') }}</p>

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
                            <td class="text-left">Rp {{ number_format($po->total_price, 0, ',', '.') }}</td>
                        </tr>
                    </x-slot:footer>
                @endif
            </x-table>
            @if ($this->po->status == 'pending')
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
