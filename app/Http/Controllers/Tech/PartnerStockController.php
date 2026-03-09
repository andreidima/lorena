<?php

namespace App\Http\Controllers\Tech;

use App\Http\Controllers\Controller;
use App\Services\PartnerStock\Exceptions\PartnerStockRequestException;
use App\Services\PartnerStock\PartnerStockClient;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Throwable;

class PartnerStockController extends Controller
{
    public function __construct(private readonly PartnerStockClient $partnerStockClient)
    {
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(25, min(200, (int) $request->query('per_page', 50)));
        $refresh = $request->boolean('refresh');
        $cacheKey = 'partner-stock.preview.rows';

        if ($refresh) {
            Cache::forget($cacheKey);
        }

        $rows = [];
        $fetchError = null;

        try {
            $rows = Cache::remember($cacheKey, now()->addMinutes(2), function (): array {
                return $this->partnerStockClient->fetchStocks();
            });
        } catch (PartnerStockRequestException $exception) {
            $fetchError = $exception->getMessage();
        } catch (Throwable $exception) {
            report($exception);
            $fetchError = 'Nu am putut prelua stocurile de la partener.';
        }

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $rows = array_values(array_filter($rows, static function (array $row) use ($needle): bool {
                $code = mb_strtolower(trim((string) ($row['cod'] ?? '')));
                $name = mb_strtolower(trim((string) ($row['denumire'] ?? '')));

                return str_contains($code, $needle) || str_contains($name, $needle);
            }));
        }

        $total = count($rows);
        $offset = ($page - 1) * $perPage;
        $items = array_slice($rows, $offset, $perPage);
        $columns = array_keys($rows[0] ?? []);

        $paginator = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('tech.partner-stock.index', [
            'rows' => $paginator,
            'columns' => $columns,
            'search' => $search,
            'perPage' => $perPage,
            'fetchError' => $fetchError,
            'totalRows' => $total,
        ]);
    }
}
