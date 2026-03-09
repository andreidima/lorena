<?php

namespace App\Services\PartnerStock;

use App\Services\PartnerStock\Exceptions\PartnerStockRequestException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;

class PartnerStockClient
{
    public function __construct(private readonly HttpFactory $http)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchStocks(): array
    {
        $payload = $this->fetchPayload();
        $rows = $this->extractRows($payload);

        if ($rows === []) {
            throw new PartnerStockRequestException('Partner stock API returned an empty stock list.');
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>|array<int, mixed>
     */
    public function fetchPayload(): array
    {
        $url = trim((string) config('partner-stock.url'));
        $token = (string) config('partner-stock.token');
        $tokenHeader = trim((string) config('partner-stock.token_header', 'x-api-token'));

        if ($url === '') {
            throw new PartnerStockRequestException('PARTNER_STOCK_URL is missing.');
        }

        if ($token === '') {
            throw new PartnerStockRequestException('PARTNER_STOCK_TOKEN is missing.');
        }

        if ($tokenHeader === '') {
            throw new PartnerStockRequestException('PARTNER_STOCK_TOKEN_HEADER is missing.');
        }

        $response = $this->request($tokenHeader, $token)->get($url);

        if ($response->failed()) {
            throw new PartnerStockRequestException($this->buildRequestFailureMessage($response));
        }

        $payload = $response->json();

        if (!is_array($payload)) {
            throw new PartnerStockRequestException('Partner stock API response is not valid JSON object/array.');
        }

        if (($payload['success'] ?? null) === false) {
            $message = trim((string) ($payload['error'] ?? 'Partner stock API returned success=false.'));
            throw new PartnerStockRequestException($message !== '' ? $message : 'Partner stock API returned success=false.');
        }

        return $payload;
    }

    private function request(string $tokenHeader, string $token): PendingRequest
    {
        return $this->http
            ->acceptJson()
            ->timeout((int) config('partner-stock.timeout', 30))
            ->withHeaders([
                $tokenHeader => $token,
            ]);
    }

    /**
     * @param array<string, mixed>|array<int, mixed> $payload
     * @return array<int, array<string, mixed>>
     */
    private function extractRows(array $payload): array
    {
        if (array_is_list($payload)) {
            return $this->normalizeRows($payload);
        }

        foreach (['data', 'stocuri', 'stocks', 'rows', 'items', 'result'] as $key) {
            if (isset($payload[$key]) && is_array($payload[$key])) {
                return $this->normalizeRows($payload[$key]);
            }
        }

        foreach ($payload as $value) {
            if (is_array($value) && array_is_list($value)) {
                return $this->normalizeRows($value);
            }
        }

        throw new PartnerStockRequestException(
            'Could not detect stock rows in partner payload. Use --inspect and set PARTNER_STOCK_*_FIELDS.'
        );
    }

    /**
     * @param array<int, mixed> $rows
     * @return array<int, array<string, mixed>>
     */
    private function normalizeRows(array $rows): array
    {
        $normalized = [];

        foreach ($rows as $row) {
            if (is_array($row)) {
                $normalized[] = $row;
                continue;
            }

            if (is_object($row)) {
                $normalized[] = (array) $row;
            }
        }

        return $normalized;
    }

    private function buildRequestFailureMessage(Response $response): string
    {
        $body = trim((string) $response->body());

        if ($body === '') {
            return sprintf('Partner stock API request failed with HTTP %d.', $response->status());
        }

        $decoded = json_decode($body, true);

        if (is_array($decoded)) {
            $error = trim((string) ($decoded['error'] ?? $decoded['message'] ?? ''));

            if ($error !== '') {
                return sprintf('Partner stock API request failed with HTTP %d: %s', $response->status(), $error);
            }
        }

        return sprintf('Partner stock API request failed with HTTP %d.', $response->status());
    }
}
