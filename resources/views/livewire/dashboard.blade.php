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

        $salesApproved = Sales::where('status', 'approved')
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->count();
        $salesRejected = Sales::where('status', 'rejected')
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->count();
        $salesPending = Sales::where('status', 'pending')
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->count();
        $totalSales = Sales::whereMonth('date', $month)
            ->whereYear('date', $year)
            ->count();

        $totalPurchaseOrder = PurchaseOrder::whereMonth('date', $month)
            ->whereYear('date', $year)
            ->count();

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


        return [
            [
                'title' => 'Total Sales',
                'value' => $totalSales,
                'icon' => 'fas.shop',
                'color' => 'text-blue-500',
                'can' => 'super-admin|admin|manager'
            ],
            [
                'title' => 'Sales Approved',
                'value' => $salesApproved,
                'icon' => 'fas.check',
                'color' => 'text-green-500',
                'can' => 'super-admin|admin|manager'
            ],
            [
                'title' => 'Sales Rejected',
                'value' => $salesRejected,
                'icon' => 'fas.xmark',
                'color' => 'text-red-500',
                'can' => 'super-admin|admin|manager'
            ],
            [
                'title' => 'Sales Pending',
                'value' => $salesPending,
                'icon' => 'fas.clock',
                'color' => 'text-yellow-500',
                'can' => 'super-admin|admin|manager'
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
                'can' => 'super-admin|admin|manager'
            ],
            [
                'title' => 'Total Shipping',
                'value' => $total_shipping,
                'icon' => 'fas.truck-fast',
                'color' => 'text-yellow-500',
                'can' => 'super-admin|admin|manager'
            ],
            [
                'title' => 'Total Delivered',
                'value' => $total_delivered,
                'icon' => 'fas.check',
                'color' => 'text-green-500',
                'can' => 'super-admin|admin|manager'
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
            Hallo
        </div>
    </x-card>
</div>
