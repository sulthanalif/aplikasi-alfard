<?php

use App\Models\Sales;
use Mary\Traits\Toast;
use App\Models\Payment;
use App\Traits\LogFormatter;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Title;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

new #[Title('Detail Sales')] class extends Component {
    use Toast, LogFormatter, WithFileUploads;

    public bool $modalPayment = false;
    public bool $bankInput = false;

    public array $productSelected = [];
    public array $paymentSelected = [];
    public Sales $sales;
    public bool $modal = false;
    public ?string $note = null;

    //varPayment
    public string $amount = '';
    public $method = null;
    public string $date = '';
    public $bank = null;
    public ?UploadedFile $image = null;

    public Collection $methods;
    public Collection $banks;

    public string $tabSelected = 'product-tab';

    public function mount(Sales $sales): void
    {
        $this->methods = collect([
            ['id' => 'cash', 'name' => 'cash'],
            ['id' => 'transfer', 'name' => 'transfer'],
        ]);
        $this->banks = collect([
            ['id' => 'BCA', 'name' => 'BCA'],
            ['id' => 'BNI', 'name' => 'BNI'],
            ['id' => 'BRI', 'name' => 'BRI'],
            ['id' => 'MANDIRI', 'name' => 'MANDIRI'],
            ['id' => 'OTHER', 'name' => 'OTHER'],
        ]);


        $this->sales = $sales;
        // dd($sales->payment->remaining);
        $this->amount = $sales->payment ? $sales->payment->remaining : $sales->total_price;
        $this->searchBank();
        $this->searchMethod();

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

        $this->funcPaymentSelected();
    }

    public function funcPaymentSelected(): void
    {
        $this->paymentSelected = $this->sales->payment ? $this->sales->payment?->details?->map(function ($payment) {
            return [
                'id' => $payment->id,
                'name' => $payment->method,
                'amount' => $payment->amount,
                'bank' => $payment->bank,
                'date' => $payment->date,
                'image' => $payment->image,
            ];
        })->toArray() : [];
    }

    public function searchMethod(string $value = '')
    {
        $selectedOption = $this->methods->where('id', $value)->first();

        $this->method = $this->methods
            ->where('id', 'like', "%{$value}%")
            ->values()
            ->when($selectedOption, function ($collection) use ($selectedOption) {
                return $collection->push($selectedOption);
            })
            ->unique('id')
            ->toArray();
    }

    public function searchBank(string $value = '')
    {
        $selectedOption = $this->banks->where('id', $value)->first();

        $this->bank = $this->banks
            ->where('id', 'like', "%{$value}%")
            ->values()
            ->when($selectedOption, function ($collection) use ($selectedOption) {
                return $collection->push($selectedOption);
            })
            ->unique('id')
            ->toArray();
    }

    public function selectMethod($method): void
    {
        $this->bankInput = $method == 'transfer' ? true : false;
    }

    public function back(): void
    {
        $this->redirect(route('sales'), navigate: true);
    }

    public function action(string $action = ''): void
    {

        try {
            DB::beginTransaction();

            if ($action == 'approved') {
                $this->note = null;
                foreach ($this->sales->details as $prod) {

                    $product = $prod->product;

                    if ($product) {
                        // Cek apakah stok mencukupi
                        if ($product->stock < $prod->quantity) {
                            DB::rollBack();
                            $this->logInfo('debug', 'Stok produk '.$product->name.' tidak mencukupi (tersisa: '.$product->stock.', dibutuhkan: '.$prod->quantity.')', null);
                            $this->error("Stok produk {$product->name} tidak mencukupi (tersisa: {$product->stock}, dibutuhkan: {$prod->quantity}). Penjualan tidak dapat disetujui.", position: 'toast-bottom');
                            return;
                        }

                        // Kurangi stok produk
                        $product->decrement('stock', $prod->quantity);
                    } else {
                        DB::rollBack();
                        $this->logInfo('debug', 'Product not found.', null);
                        $this->error('Product not found.', position: 'toast-bottom');
                        return;
                    }
                }
            }


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

    public function pay(): void
    {
        $rules = [
            'amount' => 'required|numeric',
            'method' => 'required',
            'date' => 'required|date',
        ];

        if ($this->method == 'transfer') {
            $rules['bank'] = 'required';
            $rules['image'] = 'required|image|max:2048';
        }

        $this->validate($rules);

        DB::beginTransaction();
        try {
            $payment = $this->sales->payment ?? new Payment();
            $payment->sales_id = $this->sales->id;
            $payment->customer_id = $this->sales->customer_id;
            $payment->save();

            $detail = $payment->details()->create([
                'amount' => $this->amount,
                'method' => $this->method,
                'date' => $this->date,
            ]);

            if($this->method == 'transfer') {
                $detail->update([
                    'bank' => $this->bank,
                    'image' => $this->image ? $this->image->store(path: 'images/sales', options: 'public') : null,
                ]);
            }

            if ($payment->details->sum('amount') == $this->sales->total_price) {
                $payment->update([
                    'status' => 1,
                ]);
            }

            DB::commit();
            $this->reset('paymentSelected');
            $this->paymentSelected = $this->sales->payment ? $this->sales->payment->details->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'name' => $payment->method,
                    'amount' => $payment->amount,
                    'bank' => $payment->bank,
                    'date' => $payment->date,
                    'image' => $payment->image,
                ];
            })->toArray() : [];
            $this->reset('amount', 'method', 'bank', 'image', 'date', 'modalPayment', 'bankInput');
            $this->amount = $this->sales->payment ? $this->sales->payment->remaining : $this->sales->total_price;
            $this->success('Payment saved successfully.', position: 'toast-bottom');
        } catch (\Exeption $th) {
            DB::rollBack();
            $this->error('Failed to save payment.', position: 'toast-bottom');
            $this->logError('debug', 'Failed to save payment.', $th);
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

                @if($sales->status == 'rejected')
                    <p class="font-bold">Note</p>
                    <p>{{ $this->sales->note ?? '-' }}</p>
                @else
                    <p class="font-bold">Payment Status</p>
                    @if($this->sales?->payment?->status)
                        <x-badge value="Paid" class="badge-success text-sm text-white" />
                    @else
                        <x-badge value="Pending" class="badge-soft text-sm text-white" />
                    @endif
                @endif

                <p class="font-bold">Delivery Status</p>
                <x-status :status="$this->sales->distribution?->status ?? 'pending'" />
            </div>
        </x-card>
    </div>
    <div class="mt-4">
        <x-tabs
            wire:model="tabSelected"
            active-class="bg-primary rounded !text-white"
            label-class="font-semibold"
            label-div-class="bg-primary/5 rounded w-fit p-2"
        >
            <x-tab name="product-tab" label="Product">
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
            </x-tab>
            <x-tab name="payment-tab" label="Payment">
                <x-card title="Payment" subtitle="Total Price: Rp.{{ number_format($sales->total_price, 0, ',', '.') }}">

                    <x-table :headers="[
                        [
                            'key' => 'date',
                            'label' => 'Date',
                        ],
                        [
                            'key' => 'name',
                            'label' => 'Method',
                        ],
                        [
                            'key' => 'amount',
                            'label' => 'Amount',
                        ],
                        [
                            'key' => 'bank',
                            'label' => 'Bank',
                        ],
                        [
                            'key' => 'image',
                            'label' => 'Image',
                        ],
                    ]" :rows="$paymentSelected" show-empty-text>
                    @scope('cell_date', $payment)
                        <p>{{ \Carbon\Carbon::parse($payment['date'])->locale('id')->translatedFormat('d F Y') }}</p>
                    @endscope
                    @scope('cell_amount', $payment)
                        <p>Rp {{ number_format($payment['amount'], 0, ',', '.') }}</p>
                    @endscope
                    @scope('cell_image', $payment)
                        @if($payment['image'] != '-')
                            <img src="{{ asset('storage/'.$payment['image']) }}" class="w-20 rounded-lg" style="width: 100px" >
                        @else
                            <p>-</p>
                        @endif
                    @endscope

                    @if($sales->payment)
                        <x-slot:footer>
                            <tr>
                                <td colspan="2">Total Paid</td>
                                <td class="text-left">Rp {{ number_format($sales->payment?->details()->sum('amount'), 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td colspan="2">Remaining</td>
                                <td class="text-left">Rp {{ number_format($sales->payment?->remaining ?? $sales->total_price - $sales->payment?->details()->sum('amount'), 0, ',', '.') }}</td>
                            </tr>
                        </x-slot:footer>
                    @endif
                    </x-table>
                    @if($this->sales->status == 'approved' && (!$this->sales->payment || $this->sales->payment?->remaining != 0))
                    <x-slot:actions>
                        <x-button label="Pay" class="btn-success text-white" @click="$wire.modalPayment = true" responsive />
                    </x-slot:actions>
                    @endif
                </x-card>
            </x-tab>
            <x-tab name="distribution-tab" label="Distribution">
                <div class="grid grid-cols-2 gap-2">
                    <x-card>
                        <div class="grid grid-cols-2 gap-2">
                            <p class="font-bold">Distribution Number</p>
                            <p>{{ $this->sales->distribution?->distribution?->number ?? '-' }}</p>

                            <p class="font-bold">Driver Name</p>
                            <p>{{ $this->sales->distribution?->distribution?->driver?->name ?? '-' }}</p>
                        </div>
                    </x-card>
                    <x-card>
                        <div class="ms-5">
                            <div>
                                <x-timeline-item title="Order placed"  first icon="o-map-pin" />

                                @if($sales->distribution?->status != 'pending')
                                    <x-timeline-item title="Shipped" subtitle="{{$sales->distribution?->shipment_at ?  'Shipped at ' . \Carbon\Carbon::parse($sales->distribution?->shipment_at)->locale('id')->translatedFormat('d F Y H:i') : 'Not Shipped' }}" icon="o-paper-airplane" />
                                @else
                                    <x-timeline-item title="Shipped" pending subtitle="{{$sales->distribution?->shipment_at ?  'Shipped at ' . \Carbon\Carbon::parse($sales->distribution?->shipment_at)->locale('id')->translatedFormat('d F Y H:i') : 'Not Shipped' }}" icon="o-paper-airplane" />
                                @endif

                                <x-timeline-item title="Delivered" pending="{{ $sales->distribution?->status == 'delivered' ? '' : true }}" subtitle="{{ $sales->distribution?->delivered_at ? 'Delivered at ' . \Carbon\Carbon::parse($sales->distribution?->delivered_at)->locale('id')->translatedFormat('d F Y H:i') : 'Not Delivered' }}" last icon="o-gift" />
                            </div>
                        </div>
                    </x-card>
                </div>
            </x-tab>
        </x-tabs>

    </div>


    <x-modal wire:model="modal" title="Reject Note" without-trap-focus>
        <x-form wire:submit="action('rejected')" no-separator>

            <x-textarea label="Note" wire:model="note" rows='5' required/>


            <x-slot:actions>
                <x-button label="Submit" type="submit" spinner="action('rejected')" class="btn-primary" />
            </x-slot:actions>
        </x-form>
    </x-modal>

    <x-modal wire:model="modalPayment" title="Payment" without-trap-focus box-class="w-full h-fit max-w-[700px]">
        <x-form wire:submit="pay" no-separator>

            <x-datepicker label="Date" wire:model="date" icon="o-calendar" />

            <x-input label="Amount" wire:model="amount" type="number" max='{{ $this->amount }}' required />
            {{-- <x-select label="Method" wire:model="method" :options="['cash', 'transfer']" required /> --}}

            <x-choices-offline
            label="Method"
            wire:model="method"
            :options="$methods"
            placeholder="Search ..."
            search-function="searchMethod"
            @change-selection="$wire.selectMethod($event.detail.value)"
            single
            clearable
            searchable
            >
            @scope('item', $method)
                <x-list-item :item="$method"/>
            @endscope

            {{-- Selection slot--}}
            @scope('selection', $method)
                {{ $method['id'] }}
            @endscope
            </x-choices-offline>

            <div wire:show="bankInput">
                <x-choices-offline
                label="Bank"
                wire:model="bank"
                :options="$banks"
                placeholder="Search ..."
                search-function="searchBank"
                single
                clearable
                searchable
                >
                @scope('item', $bank)
                    <x-list-item :item="$bank"/>
                @endscope

                {{-- Selection slot--}}
                @scope('selection', $bank)
                    {{ $bank['id'] }}
                @endscope
                </x-choices-offline>
            </div>

            <div wire:show='bankInput'>
                <x-file wire:model="image" label="Image" hint="Only Image" accept="image/png, image/jpeg, image/jpg" />
            </div>
            <x-slot:actions>
                <x-button label="Submit" type="submit" spinner="pay" class="btn-primary" />
            </x-slot:actions>
        </x-form>
    </x-modal>
</div>
