<?php

namespace Tests\Feature\WooCommerce;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncPartnerStocksCommandTest extends TestCase
{
    public function test_it_can_inspect_partner_payload(): void
    {
        config([
            'partner-stock.url' => 'https://partner.test/api/stoc',
            'partner-stock.token' => 'token',
            'partner-stock.sku_fields' => ['cod_articol'],
            'partner-stock.quantity_fields' => ['stoc'],
        ]);

        Http::fake([
            'https://partner.test/api/stoc' => Http::response([
                'success' => true,
                'data' => [
                    [
                        'cod_articol' => 'SKU-1',
                        'stoc' => 7,
                        'denumire' => 'Demo',
                    ],
                ],
            ], 200),
        ]);

        $result = Artisan::call('woocommerce:sync-partner-stocks', ['--inspect' => true]);

        $this->assertSame(0, $result);
        Http::assertSentCount(1);
    }

    public function test_it_resolves_matching_products_in_dry_run_mode(): void
    {
        config([
            'partner-stock.url' => 'https://partner.test/api/stoc',
            'partner-stock.token' => 'token',
            'partner-stock.sku_fields' => ['cod_articol'],
            'partner-stock.quantity_fields' => ['stoc'],
            'woocommerce.url' => 'https://store.test',
            'woocommerce.consumer_key' => 'ck',
            'woocommerce.consumer_secret' => 'cs',
            'woocommerce.version' => 'wc/v3',
        ]);

        Http::fake([
            'https://partner.test/api/stoc' => Http::response([
                [
                    'cod_articol' => 'SKU-1',
                    'stoc' => 9,
                ],
                [
                    'cod_articol' => 'SKU-404',
                    'stoc' => 3,
                ],
            ], 200),
            'https://store.test/wp-json/wc/v3/products*' => Http::response([
                [
                    'id' => 1001,
                    'sku' => 'SKU-1',
                ],
            ], 200),
        ]);

        $result = Artisan::call('woocommerce:sync-partner-stocks', ['--dry-run' => true]);

        $this->assertSame(0, $result);

        Http::assertSent(function ($request) {
            return $request->method() === 'GET'
                && str_starts_with($request->url(), 'https://store.test/wp-json/wc/v3/products?');
        });

        Http::assertNotSent(function ($request) {
            return $request->method() === 'POST'
                && $request->url() === 'https://store.test/wp-json/wc/v3/products/batch';
        });
    }
}
