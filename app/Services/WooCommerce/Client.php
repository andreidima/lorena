<?php

namespace App\Services\WooCommerce;

use App\Services\WooCommerce\Exceptions\WooCommerceRequestException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;

class Client
{
    private string $baseUrl;
    private string $consumerKey;
    private string $consumerSecret;
    private string $version;
    private string $authMethod;

    public function __construct(
        ?string $baseUrl = null,
        ?string $consumerKey = null,
        ?string $consumerSecret = null,
        ?string $version = null,
        ?HttpFactory $http = null,
        ?string $authMethod = null
    ) {
        $this->baseUrl = rtrim((string) ($baseUrl ?? config('woocommerce.url')), '/');
        $this->consumerKey = (string) ($consumerKey ?? config('woocommerce.consumer_key'));
        $this->consumerSecret = (string) ($consumerSecret ?? config('woocommerce.consumer_secret'));
        $this->version = trim((string) ($version ?? config('woocommerce.version', 'wc/v3')), '/');
        $this->authMethod = trim((string) ($authMethod ?? config('woocommerce.auth_method', 'basic')));
        $this->http = $http ?? app(HttpFactory::class);
    }

    /**
     * @var HttpFactory
     */
    private HttpFactory $http;

    public function isConfigured(): bool
    {
        return $this->baseUrl !== ''
            && $this->consumerKey !== ''
            && $this->consumerSecret !== '';
    }

    public function updateProductStock(string $sku, int $quantity): bool
    {
        $this->ensureConfigured();

        $sku = trim($sku);

        if ($sku === '') {
            return false;
        }

        $product = $this->getProductBySku($sku);

        if ($product === null || empty($product['id'])) {
            return false;
        }

        $response = $this->request()->put('products/' . (int) $product['id'], [
            'manage_stock' => true,
            'stock_quantity' => max(0, $quantity),
            'stock_status' => max(0, $quantity) > 0 ? 'instock' : 'outofstock',
        ]);

        if ($response->failed()) {
            throw new WooCommerceRequestException($this->buildRequestFailureMessage($response));
        }

        return true;
    }

    /**
     * @param array<int, string> $skus
     * @return array<string, int>
     */
    public function mapProductIdsBySkus(array $skus): array
    {
        $this->ensureConfigured();

        $skus = array_values(array_unique(array_filter(array_map(
            static fn ($value): string => trim((string) $value),
            $skus
        ))));

        if ($skus === []) {
            return [];
        }

        $perPage = max(1, min(100, (int) config('woocommerce.per_page', 50)));
        $mapped = [];

        foreach (array_chunk($skus, $perPage) as $chunk) {
            $response = $this->request()->get('products', [
                'sku' => implode(',', $chunk),
                'per_page' => $perPage,
            ]);

            if ($response->failed()) {
                throw new WooCommerceRequestException($this->buildRequestFailureMessage($response));
            }

            $rows = $response->json();

            if (!is_array($rows)) {
                continue;
            }

            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $sku = trim((string) ($row['sku'] ?? ''));
                $id = (int) ($row['id'] ?? 0);

                if ($sku === '' || $id <= 0) {
                    continue;
                }

                $mapped[$sku] = $id;
            }
        }

        return $mapped;
    }

    /**
     * @param array<int, array{id:int, stock_quantity:int}> $updates
     */
    public function batchUpdateStocks(array $updates): void
    {
        $this->ensureConfigured();

        if ($updates === []) {
            return;
        }

        foreach (array_chunk($updates, 100) as $chunk) {
            $payload = array_map(static function (array $update): array {
                $quantity = max(0, (int) ($update['stock_quantity'] ?? 0));

                return [
                    'id' => (int) $update['id'],
                    'manage_stock' => true,
                    'stock_quantity' => $quantity,
                    'stock_status' => $quantity > 0 ? 'instock' : 'outofstock',
                ];
            }, $chunk);

            $response = $this->request()->post('products/batch', [
                'update' => $payload,
            ]);

            if ($response->failed()) {
                throw new WooCommerceRequestException($this->buildRequestFailureMessage($response));
            }
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getProductBySku(string $sku): ?array
    {
        $response = $this->request()->get('products', [
            'sku' => $sku,
        ]);

        if ($response->failed()) {
            throw new WooCommerceRequestException($this->buildRequestFailureMessage($response));
        }

        $products = $response->json();

        if (!is_array($products) || $products === [] || !is_array($products[0] ?? null)) {
            return null;
        }

        /** @var array<string, mixed> $first */
        $first = $products[0];

        return $first;
    }

    private function request(): PendingRequest
    {
        $request = $this->http
            ->baseUrl($this->apiBaseUrl())
            ->acceptJson()
            ->timeout((int) config('woocommerce.timeout', 30));

        if (strtolower($this->authMethod) === 'query') {
            return $request->withQueryParameters([
                'consumer_key' => $this->consumerKey,
                'consumer_secret' => $this->consumerSecret,
            ]);
        }

        return $request->withBasicAuth($this->consumerKey, $this->consumerSecret);
    }

    private function apiBaseUrl(): string
    {
        return $this->baseUrl . '/wp-json/' . $this->version;
    }

    private function ensureConfigured(): void
    {
        if ($this->isConfigured()) {
            return;
        }

        throw new WooCommerceRequestException('WooCommerce credentials are missing (WOOCOMMERCE_URL/CK/CS).');
    }

    private function buildRequestFailureMessage(Response $response): string
    {
        $body = trim((string) $response->body());

        if ($body === '') {
            return sprintf('WooCommerce request failed with HTTP %d.', $response->status());
        }

        $decoded = json_decode($body, true);

        if (is_array($decoded)) {
            $message = trim((string) ($decoded['message'] ?? $decoded['code'] ?? ''));

            if ($message !== '') {
                return sprintf('WooCommerce request failed with HTTP %d: %s', $response->status(), $message);
            }
        }

        return sprintf('WooCommerce request failed with HTTP %d.', $response->status());
    }
}
