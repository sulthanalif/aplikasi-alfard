<?php

return [
    [
        'type' => 'item',
        'title' => 'Dashboard',
        'icon' => 'fas.gauge',
        'link' => 'dashboard',
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
                'link' => '#',
                'can'  => 'manage-sales',
            ],
            [
                'title' => 'Purchase Order',
                'icon' => 'fas.bag-shopping',
                'link' => '#',
                'can'  => 'manage-po',
            ],
        ]
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
