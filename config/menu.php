<?php

return [
    [
        'type' => 'item',
        'title' => 'Dashboard',
        'icon' => 'fas.gauge',
        'link' => 'dashboard',
        // 'icon-classes' => 'text-primary',
    ],
    [
        'type' => 'sub',
        'title' => 'Master Data',
        'icon' => 'fas.database',
        'submenu' => [
            [
                'type' => 'item',
                'title' => 'Categories',
                'icon' => 'fas.tags',
                'link' => 'categories',
                'can'  => 'manage-products',
            ],
            [
                'type' => 'item',
                'title' => 'Units',
                'icon' => 'fas.ruler-combined',
                'link' => 'units',
                'can'  => 'manage-units',
            ],
            [
                'type' => 'item',
                'title' => 'Products',
                'icon' => 'fas.box',
                'link' => 'products',
                'can'  => 'manage-products',
            ],
            [
                'type' => 'item',
                'title' => 'Users',
                'icon' => 'fas.users',
                'link' => 'users',
                'can'  => 'manage-users',
            ],
        ]
    ],
    [
        'type' => 'sub',
        'title' => 'Transactions',
        'icon' => 'fas.cart-shopping',
        'can'  => 'transactions',
        'submenu' => [
            [
                'title' => 'Sales',
                'icon' => 'fas.cash-register',
                'link' => 'sales',
                'can'  => 'manage-sales',
            ],
            [
                'title' => 'Purchase Order',
                'icon' => 'fas.bag-shopping',
                'link' => 'po',
                'can'  => 'manage-po',
            ],
        ]
    ],
    [
        'type' => 'item',
        'title' => 'Distributions',
        'icon' => 'fas.truck-fast',
        'link' => 'distributions',
        'can'  => 'manage-distribution',
    ],
    [
        'type' => 'item',
        'title' => 'Order',
        'icon' => 'fas.cart-shopping',
        'link' => 'order',
        'can'  => 'manage-order',
    ],
    [
        'type' => 'item',
        'title' => 'Customers',
        'icon' => 'fas.users',
        'link' => 'customers',
        'can'  => 'manage-customers',
    ],
    [
        'type' => 'sub',
        'title' => 'Reports',
        'icon' => 'fas.file-lines',
        // 'link' => 'reports',
        'can'  => 'reports',
        'submenu' => [
            [
                'title' => 'Sales',
                'icon' => 'fas.file-lines',
                'link' => 'sales-report',
                'can'  => 'sales-report',
            ],
            [
                'title' => 'Expenditure',
                'icon' => 'fas.file-lines',
                'link' => 'po-report',
                'can'  => 'po-report',
            ],
            [
                'title' => 'Distribution',
                'icon' => 'fas.file-lines',
                'link' => 'distribution-report',
                'can'  => 'distribution-report',
            ],
        ]
    ],
    [
        'type' => 'sub',
        'title' => 'Settings',
        'icon' => 'fas.gear',
        'can'  => 'settings',
        'submenu' => [
            [
                'title' => 'Roles',
                'icon' => 'fas.user-tie',
                'link' => 'roles',
                'can'  => 'manage-roles',
            ],
            [
                'title' => 'Permissions',
                'icon' => 'fas.users-line',
                'link' => 'permissions',
                'can'  => 'manage-permissions',
            ],
        ]
    ],
];
