<?php

use App\Models\Sales;
// use App\Models\Distribution;
use Livewire\Volt\Component;
use App\Models\PurchaseOrder;
use Livewire\Attributes\Title;
use App\Models\DistributionDetail;

new #[Title('Dashboard')] class extends Component {
    public function stats(): array
    {
        $month = date('m');
        $year = date('Y');

        $query = Sales::query()
            ->whereMonth('date', $month)
            ->whereYear('date', $year);

        // If logged in user is customer, filter by their ID
        if (auth()->user()->hasRole('customer')) {
            $query->where('customer_id', auth()->user()->customer_id);
        }

        $salesApproved = (clone $query)
            ->where('status', 'approved')
            ->count();

        $salesRejected = (clone $query)
            ->where('status', 'rejected')
            ->count();

        $salesPending = (clone $query)
            ->where('status', 'pending')
            ->count();

        $totalSales = (clone $query)->count();

        $totalPurchaseOrder = PurchaseOrder::whereMonth('date', $month)
            ->whereYear('date', $year)
            ->count();

        if (auth()->user()->hasRole('driver')) {
            $totalDistribution = DistributionDetail::whereMonth('created_at', $month)
                ->whereHas('distribution', function ($query) {
                    $query->where('user_id', auth()->id());
                })
                ->whereYear('created_at', $year)
                ->count();
            $total_shipping = Sales::whereHas('distribution', fn ($query) => $query->where('status', 'shipped'))
                ->whereHas('distribution', function ($query) {
                    $query->whereHas('distribution', function ($query) {
                        $query->where('user_id', auth()->id());
                    });
                })
                ->whereMonth('date', $month)
                ->whereYear('date', $year)
                ->count();
            $total_delivered = Sales::whereHas('distribution', fn ($query) => $query->where('status', 'delivered'))
                ->whereHas('distribution', function ($query) {
                    $query->whereHas('distribution', function ($query) {
                        $query->where('user_id', auth()->id());
                    });
                })
                ->whereMonth('date', $month)
                ->whereYear('date', $year)
                ->count();
        } else {
            $totalDistribution = DistributionDetail::whereMonth('created_at', $month)
                ->whereYear('created_at', $year)
                ->count();
            $total_shipping = Sales::whereHas('distribution', fn ($query) => $query->where('status', 'shipped'))
                ->whereMonth('date', $month)
                ->whereYear('date', $year)
                ->count();
            $total_delivered = Sales::whereHas('distribution', fn ($query) => $query->where('status', 'delivered'))
                ->whereMonth('date', $month)
                ->whereYear('date', $year)
                ->count();
        }



        return [
            [
                'title' => auth()->user()->hasRole('customer') ? 'Total Order' : 'Total Sales',
                'value' => $totalSales,
                'icon' => 'fas.shop',
                'color' => 'text-blue-500',
                'can' => 'super-admin|admin|manager|customer'
            ],
            [
                'title' => auth()->user()->hasRole('customer') ? 'Order Pending' : 'Sales Pending',
                'value' => $salesPending,
                'icon' => 'fas.clock',
                'color' => 'text-yellow-500',
                'can' => 'super-admin|admin|manager|customer'
            ],
            [
                'title' => auth()->user()->hasRole('customer') ? 'Order Rejected' : 'Sales Rejected',
                'value' => $salesRejected,
                'icon' => 'fas.xmark',
                'color' => 'text-red-500',
                'can' => 'super-admin|admin|manager|customer'
            ],
            [
                'title' => auth()->user()->hasRole('customer') ? 'Order Approved' : 'Sales Approved',
                'value' => $salesApproved,
                'icon' => 'fas.check',
                'color' => 'text-green-500',
                'can' => 'super-admin|admin|manager|customer'
            ],
            [
                'title' => 'Total Purchase Order',
                'value' => $totalPurchaseOrder,
                'icon' => 'fas.shopping-cart',
                'color' => 'text-blue-500',
                'can' => 'super-admin|admin|manager'
            ],
            [
                'title' => 'Total Distribution',
                'value' => $totalDistribution,
                'icon' => 'fas.truck',
                'color' => 'text-blue-500',
                'can' => 'super-admin|admin|manager|driver'
            ],
            [
                'title' => 'Total Shipping',
                'value' => $total_shipping,
                'icon' => 'fas.truck-fast',
                'color' => 'text-yellow-500',
                'can' => 'super-admin|admin|manager|driver'
            ],
            [
                'title' => 'Total Delivered',
                'value' => $total_delivered,
                'icon' => 'fas.check',
                'color' => 'text-green-500',
                'can' => 'super-admin|admin|manager|driver'
            ],
        ];
    }

    public function with(): array
    {
        return [
            'stats' => $this->stats(),
        ];
    }

}; ?>

<div>
    <!-- HEADER -->
    <x-header title="Dashboard" separator progress-indicator>

    </x-header>

    <x-statistic :stats="$stats" />

    <!-- TABLE  -->
    <x-card shadow class="mt-4">
        <div class="flex justify-center">
            Hallo {{ auth()->user()->name }}
        </div>
    </x-card>
</div>
