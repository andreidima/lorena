<?php

namespace App\Console\Commands\WooCommerce;

use App\Services\PartnerStock\Exceptions\PartnerStockRequestException;
use App\Services\PartnerStock\PartnerStockClient;
use App\Services\WooCommerce\Client as WooCommerceClient;
use App\Services\WooCommerce\Exceptions\WooCommerceRequestException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncPartnerStocks extends Command
{
    protected $signature = 'woocommerce:sync-partner-stocks
        {--dry-run : Validate partner rows and matching SKUs, but do not update WooCommerce}
        {--inspect : Print detected row keys from partner response and stop}
        {--limit=0 : Process only the first N valid stock rows}';

    protected $description = 'Sync stock quantities from partner API into WooCommerce by SKU.';

    public function __construct(
        private readonly PartnerStockClient $partnerStockClient,
        private readonly WooCommerceClient $wooCommerceClient
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $inspect = (bool) $this->option('inspect');
        $limit = max(0, (int) $this->option('limit'));

        try {
            $rows = $this->partnerStockClient->fetchStocks();
            $this->info(sprintf('Fetched %d stock rows from partner API.', count($rows)));

            if ($inspect) {
                $this->printInspection($rows);
                return self::SUCCESS;
            }

            $normalized = $this->normalizeRows($rows);

            if ($normalized['items'] === []) {
                $this->error('No valid SKU + quantity rows were detected in partner payload.');
                $this->warn('Run with --inspect and adjust PARTNER_STOCK_SKU_FIELDS / PARTNER_STOCK_QUANTITY_FIELDS.');
                return self::FAILURE;
            }

            if ($limit > 0) {
                $normalized['items'] = array_slice($normalized['items'], 0, $limit);
            }

            $items = $normalized['items'];
            $this->line(sprintf('Valid rows to process: %d', count($items)));

            if ($normalized['skipped'] > 0) {
                $this->warn(sprintf('Skipped rows (missing SKU/quantity): %d', $normalized['skipped']));
            }

            if (!$this->wooCommerceClient->isConfigured()) {
                $this->error('WooCommerce credentials are missing. Set WOOCOMMERCE_URL, WOOCOMMERCE_CK, WOOCOMMERCE_CS.');
                return self::FAILURE;
            }

            $skuToId = $this->wooCommerceClient->mapProductIdsBySkus(array_column($items, 'sku'));
            $updates = [];
            $missing = [];

            foreach ($items as $item) {
                $sku = $item['sku'];
                $quantity = $item['quantity'];
                $productId = $skuToId[$sku] ?? null;

                if (!$productId) {
                    $missing[] = $sku;
                    continue;
                }

                $updates[] = [
                    'id' => (int) $productId,
                    'stock_quantity' => (int) $quantity,
                ];
            }

            if ($dryRun) {
                $this->warn('Dry-run mode enabled. No WooCommerce updates were sent.');
            } else {
                $this->wooCommerceClient->batchUpdateStocks($updates);
            }

            $this->line(sprintf('Products matched in WooCommerce: %d', count($updates)));
            $this->line(sprintf('Products missing in WooCommerce: %d', count($missing)));

            if ($missing !== []) {
                $preview = implode(', ', array_slice($missing, 0, 20));
                $suffix = count($missing) > 20 ? ' ...' : '';
                $this->warn('Missing SKU sample: ' . $preview . $suffix);
            }

            Log::channel('woocommerce')->info('Partner stock sync finished.', [
                'dry_run' => $dryRun,
                'fetched_rows' => count($rows),
                'valid_rows' => count($items),
                'updated_products' => count($updates),
                'missing_products' => count($missing),
                'skipped_rows' => $normalized['skipped'],
            ]);

            return self::SUCCESS;
        } catch (PartnerStockRequestException|WooCommerceRequestException $exception) {
            $this->error($exception->getMessage());
            Log::channel('woocommerce')->error('Partner stock sync failed.', [
                'error' => $exception->getMessage(),
            ]);

            return self::FAILURE;
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Unexpected error during partner stock sync. See logs for details.');
            Log::channel('woocommerce')->error('Partner stock sync crashed.', [
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
            ]);

            return self::FAILURE;
        }
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array{items: array<int, array{sku: string, quantity: int}>, skipped: int}
     */
    private function normalizeRows(array $rows): array
    {
        $itemsBySku = [];
        $skipped = 0;

        foreach ($rows as $row) {
            $sku = $this->extractFirstString($row, $this->fieldCandidates('partner-stock.sku_fields'));
            $quantity = $this->extractQuantity($row, $this->fieldCandidates('partner-stock.quantity_fields'));

            if ($sku === null || $quantity === null) {
                $skipped++;
                continue;
            }

            $itemsBySku[$sku] = [
                'sku' => $sku,
                'quantity' => $quantity,
            ];
        }

        return [
            'items' => array_values($itemsBySku),
            'skipped' => $skipped,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function printInspection(array $rows): void
    {
        if ($rows === []) {
            $this->warn('No rows found.');
            return;
        }

        $first = $rows[0];
        $keys = array_keys($first);

        $this->line('First row keys:');
        $this->line($keys !== [] ? implode(', ', $keys) : '(none)');
        $this->line('');
        $this->line('First row sample:');
        $this->line(
            json_encode($first, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}'
        );
    }

    /**
     * @return array<int, string>
     */
    private function fieldCandidates(string $configKey): array
    {
        $fields = config($configKey, []);

        if (!is_array($fields)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn ($field): string => trim((string) $field),
            $fields
        )));
    }

    /**
     * @param array<string, mixed> $row
     * @param array<int, string> $fields
     */
    private function extractFirstString(array $row, array $fields): ?string
    {
        foreach ($fields as $field) {
            if (!array_key_exists($field, $row)) {
                continue;
            }

            $value = trim((string) $row[$field]);

            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $row
     * @param array<int, string> $fields
     */
    private function extractQuantity(array $row, array $fields): ?int
    {
        foreach ($fields as $field) {
            if (!array_key_exists($field, $row)) {
                continue;
            }

            $value = $this->normalizeNumber($row[$field]);

            if ($value !== null) {
                return max(0, (int) floor($value));
            }
        }

        return null;
    }

    private function normalizeNumber(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (!is_string($value)) {
            return null;
        }

        $normalized = trim($value);

        if ($normalized === '') {
            return null;
        }

        $normalized = str_replace(["\xc2\xa0", ' '], '', $normalized);

        if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
            $lastComma = strrpos($normalized, ',');
            $lastDot = strrpos($normalized, '.');

            if ($lastComma !== false && $lastDot !== false && $lastComma > $lastDot) {
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            } else {
                $normalized = str_replace(',', '', $normalized);
            }
        } elseif (str_contains($normalized, ',')) {
            $normalized = str_replace(',', '.', $normalized);
        }

        return is_numeric($normalized) ? (float) $normalized : null;
    }
}
