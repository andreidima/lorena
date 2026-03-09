<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\ModuleEntityRequest;
use App\Support\Modules\ModuleRegistry;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ModuleEntityController extends Controller
{
    public function index(Request $request): View
    {
        $context = $this->buildContext($request);
        $request->session()->forget('returnUrl');

        $query = DB::table($context['table']);
        $filters = $this->collectFilters($request, $context['columns']);

        $this->applyFilters($query, $context['columns'], $filters);

        $rows = $query
            ->orderByDesc('id')
            ->paginate(25)
            ->appends($filters);

        return view('modules.entity.index', [
            ...$context,
            'rows' => $rows,
            'filters' => $filters,
        ]);
    }

    public function create(Request $request): View
    {
        $context = $this->buildContext($request);
        $request->session()->get('returnUrl') ?: $request->session()->put('returnUrl', url()->previous());

        return view('modules.entity.save', [
            ...$context,
            'row' => null,
        ]);
    }

    public function store(ModuleEntityRequest $request): RedirectResponse
    {
        $context = $this->buildContext($request);
        $payload = $this->preparePayload($request->validated(), $context['columns']);
        $payload = $this->applyDerivedValues($payload);

        DB::table($context['table'])->insert($payload + [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect($request->session()->get('returnUrl', route(ModuleRegistry::entityRouteName($context['module_key'], $context['entity_key']))))
            ->with('status', $context['entity']['label'] . ' a fost adăugat cu succes.');
    }

    public function edit(Request $request, int $record): View
    {
        $context = $this->buildContext($request);
        $request->session()->get('returnUrl') ?: $request->session()->put('returnUrl', url()->previous());

        $row = DB::table($context['table'])->where('id', $record)->first();

        if (!$row) {
            throw new NotFoundHttpException('Înregistrare inexistentă.');
        }

        return view('modules.entity.save', [
            ...$context,
            'row' => (array) $row,
        ]);
    }

    public function update(ModuleEntityRequest $request, int $record): RedirectResponse
    {
        $context = $this->buildContext($request);
        $existing = DB::table($context['table'])->where('id', $record)->first();

        if (!$existing) {
            throw new NotFoundHttpException('Înregistrare inexistentă.');
        }

        $payload = $this->preparePayload($request->validated(), $context['columns']);
        $payload = $this->applyDerivedValues($payload);
        $payload['updated_at'] = now();

        DB::table($context['table'])->where('id', $record)->update($payload);

        return redirect($request->session()->get('returnUrl', route(ModuleRegistry::entityRouteName($context['module_key'], $context['entity_key']))))
            ->with('status', $context['entity']['label'] . ' a fost modificat cu succes.');
    }

    public function destroy(Request $request, int $record): RedirectResponse
    {
        $context = $this->buildContext($request);
        DB::table($context['table'])->where('id', $record)->delete();

        return back()->with('status', 'Înregistrarea a fost ștearsă cu succes.');
    }

    public function exportPdf(Request $request)
    {
        $context = $this->buildContext($request);
        $query = DB::table($context['table']);
        $filters = $this->collectFilters($request, $context['columns']);
        $this->applyFilters($query, $context['columns'], $filters);

        $rows = $query
            ->orderByDesc('id')
            ->limit(500)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        $pdf = Pdf::loadView('modules.pdf.table', [
            ...$context,
            'rows' => $rows,
            'filters' => $filters,
        ]);

        $fileName = str($context['table'])->replace('_', '-')->append('.pdf')->toString();

        return $pdf->download($fileName);
    }

    public function exportInvoicePdf(Request $request, int $record)
    {
        $context = $this->buildContext($request);

        if ($context['table'] !== 'ctc_facturi') {
            throw new NotFoundHttpException('Export PDF factură indisponibil.');
        }

        $invoice = DB::table('ctc_facturi')->where('id', $record)->first();

        if (!$invoice) {
            throw new NotFoundHttpException('Factură inexistentă.');
        }

        $client = DB::table('ctc_clienti')->where('id', $invoice->client_id)->first();
        $contract = DB::table('ctc_contracte')->where('id', $invoice->contract_id)->first();
        $items = DB::table('ctc_pozitii_factura')->where('factura_id', $invoice->id)->get();
        $receipts = DB::table('ctc_incasari')->where('factura_id', $invoice->id)->get();

        $totalItems = $items->sum('valoare_linie');
        $totalReceipts = $receipts->sum('suma_incasata');
        $sold = (float) ($invoice->valoare_totala ?? 0) - (float) $totalReceipts;

        $pdf = Pdf::loadView('modules.pdf.invoice', [
            'invoice' => $invoice,
            'client' => $client,
            'contract' => $contract,
            'items' => $items,
            'receipts' => $receipts,
            'totalItems' => $totalItems,
            'totalReceipts' => $totalReceipts,
            'sold' => $sold,
        ]);

        $number = $invoice->cod_factura ?: 'factura-' . $invoice->id;

        return $pdf->download($number . '.pdf');
    }

    private function buildContext(Request $request): array
    {
        $moduleKey = (string) $request->route('moduleKey');
        $entityKey = (string) $request->route('entityKey');
        $module = ModuleRegistry::module($moduleKey);
        $entity = ModuleRegistry::entity($moduleKey, $entityKey);

        return [
            'module_key' => $moduleKey,
            'module' => $module,
            'entity_key' => $entityKey,
            'entity' => $entity,
            'columns' => $entity['columns'] ?? [],
            'table' => $entity['table'],
        ];
    }

    private function collectFilters(Request $request, array $columns): array
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
        ];

        foreach ($columns as $column) {
            $name = $column['name'] ?? null;
            $type = $column['type'] ?? 'string';

            if (!$name) {
                continue;
            }

            if (in_array($type, ['integer', 'bigint', 'decimal'], true)) {
                $filters[$name . '_min'] = trim((string) $request->query($name . '_min', ''));
                $filters[$name . '_max'] = trim((string) $request->query($name . '_max', ''));
                continue;
            }

            $filters[$name] = trim((string) $request->query($name, ''));
        }

        return $filters;
    }

    private function applyFilters($query, array $columns, array $filters): void
    {
        $global = trim((string) ($filters['q'] ?? ''));
        $globalNumeric = $this->normalizeNumericValue($global);

        if ($global !== '') {
            $query->where(function ($globalQuery) use ($columns, $global, $globalNumeric) {
                foreach ($columns as $column) {
                    $name = $column['name'] ?? null;
                    $type = $column['type'] ?? 'string';

                    if (!$name) {
                        continue;
                    }

                    if (in_array($type, ['string', 'text', 'select'], true)) {
                        $globalQuery->orWhere($name, 'like', '%' . $global . '%');
                        continue;
                    }

                    if ($globalNumeric !== null && in_array($type, ['integer', 'bigint', 'decimal'], true)) {
                        $globalQuery->orWhere($name, $globalNumeric);
                    }
                }
            });
        }

        foreach ($columns as $column) {
            $name = $column['name'] ?? null;
            $type = $column['type'] ?? 'string';

            if (!$name) {
                continue;
            }

            if (in_array($type, ['integer', 'bigint', 'decimal'], true)) {
                $min = $this->normalizeNumericValue($filters[$name . '_min'] ?? null);
                $max = $this->normalizeNumericValue($filters[$name . '_max'] ?? null);

                if ($min !== null) {
                    $query->where($name, '>=', $min);
                }

                if ($max !== null) {
                    $query->where($name, '<=', $max);
                }

                continue;
            }

            $value = $filters[$name] ?? null;

            if ($value === null || $value === '') {
                continue;
            }

            if ($type === 'select') {
                $query->where($name, $value);
                continue;
            }

            $query->where($name, 'like', '%' . $value . '%');
        }
    }

    private function normalizeNumericValue(?string $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $normalized = str_replace(["\xc2\xa0", ' '], '', trim($value));

        if ($normalized === '') {
            return null;
        }

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

    private function preparePayload(array $validated, array $columns): array
    {
        $payload = [];

        foreach ($columns as $column) {
            $name = $column['name'] ?? null;

            if (!$name) {
                continue;
            }

            $value = $validated[$name] ?? null;

            if ($value === '') {
                $value = null;
            }

            $payload[$name] = $value;
        }

        return $payload;
    }

    private function applyDerivedValues(array $payload): array
    {
        $toFloat = static fn ($value): float => (float) ($value ?? 0);

        if (isset($payload['cantitate'], $payload['pret_unitar'])) {
            $payload['valoare_linie'] = $toFloat($payload['cantitate']) * $toFloat($payload['pret_unitar']);
        }

        if (isset($payload['cantitate_alocata'], $payload['cost_unitar'])) {
            $payload['cost_total'] = $toFloat($payload['cantitate_alocata']) * $toFloat($payload['cost_unitar']);
        }

        if (isset($payload['cantitate_consumata'], $payload['cost_unitar'])) {
            $payload['cost_total'] = $toFloat($payload['cantitate_consumata']) * $toFloat($payload['cost_unitar']);
        }

        if (isset($payload['venit_total'], $payload['cost_total'])) {
            $profit = $toFloat($payload['venit_total']) - $toFloat($payload['cost_total']);
            $payload['profit_brut'] = $profit;
            $payload['marja_profit'] = $toFloat($payload['venit_total']) !== 0
                ? ($profit / $toFloat($payload['venit_total'])) * 100
                : null;
        }

        if (isset($payload['valoare_planificata'], $payload['valoare_consumata'])) {
            $payload['valoare_disponibila'] = $toFloat($payload['valoare_planificata']) - $toFloat($payload['valoare_consumata']);
        }

        if (isset($payload['suma_creanta'], $payload['suma_incasata'])) {
            $payload['sold_creanta'] = $toFloat($payload['suma_creanta']) - $toFloat($payload['suma_incasata']);
        }

        if (isset($payload['suma_datorie'], $payload['suma_platita'])) {
            $payload['sold_datorie'] = $toFloat($payload['suma_datorie']) - $toFloat($payload['suma_platita']);
        }

        if (isset($payload['valoare_totala']) && !isset($payload['sold_factura'])) {
            $payload['sold_factura'] = $toFloat($payload['valoare_totala']);
        }

        if (isset($payload['valoare_anterioara'], $payload['valoare_noua'])) {
            $payload['diferenta_valoare'] = $toFloat($payload['valoare_noua']) - $toFloat($payload['valoare_anterioara']);
        }

        if (isset($payload['valoare_kpi'], $payload['prag_alerta'])) {
            $payload['scor_performanta'] = $toFloat($payload['prag_alerta']) !== 0
                ? ($toFloat($payload['valoare_kpi']) / $toFloat($payload['prag_alerta'])) * 100
                : null;
        }

        return $payload;
    }
}
