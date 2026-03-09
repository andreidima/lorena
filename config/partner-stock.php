<?php

return [
    'url' => env('PARTNER_STOCK_URL', 'http://contliv.eu:3030/api/stoc'),
    'token' => env('PARTNER_STOCK_TOKEN'),
    'token_header' => env('PARTNER_STOCK_TOKEN_HEADER', 'x-api-token'),
    'timeout' => (int) env('PARTNER_STOCK_TIMEOUT', 30),

    // Comma-separated list of candidate field names from partner payload.
    'sku_fields' => explode(',', (string) env('PARTNER_STOCK_SKU_FIELDS', 'sku,cod_articol,cod_produs,cod')),
    'quantity_fields' => explode(',', (string) env('PARTNER_STOCK_QUANTITY_FIELDS', 'stoc,stoc_curent,stock,qty,cantitate')),
    'name_fields' => explode(',', (string) env('PARTNER_STOCK_NAME_FIELDS', 'denumire_articol,denumire,name,nume')),
];
