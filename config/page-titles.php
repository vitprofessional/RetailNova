<?php

/**
 * Mapping of route names / URL patterns to human-friendly page titles.
 *
 * Usage:
 *  - 'routes' keyed by exact route name (Route::currentRouteName())
 *  - 'patterns' keyed by URL path patterns (fnmatch style) matching request()->path()
 *
 * Example:
 *  'routes' => [ 'dashboard' => 'Dashboard' ],
 *  'patterns' => [ 'admin/*' => 'Admin Area' ],
 */

return [
    'routes' => [
        // Add route-name => title mappings here
        'dashboard' => 'Dashboard',
        'addPurchase' => 'Add Purchase',
        'purchaseList' => 'Purchase List',
        'newsale' => 'New Sale',
        'saleList' => 'Sales',
    ],

    'patterns' => [
        // fnmatch-style patterns against request()->path()
        // e.g. 'product/*' => 'Products'
    ],
];
