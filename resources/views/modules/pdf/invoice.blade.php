<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Factura {{ $invoice->cod_factura ?? $invoice->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
        h1 { margin: 0 0 10px 0; }
        .section { margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        th { background: #f1f1f1; }
        .totals td { font-weight: bold; }
    </style>
</head>
<body>
    <div class="section">
        <h1>Factura {{ $invoice->cod_factura ?? ('F-' . $invoice->id) }}</h1>
        <div>Companie: {{ config('app.name') }}</div>
        <div>Client: {{ $client->denumire_client ?? 'Necunoscut' }}</div>
        <div>Contract: {{ $contract->cod_contract ?? '-' }}</div>
        <div>Status factură: {{ $invoice->status_factura ?? '-' }}</div>
        <div>Moneda: {{ $invoice->moneda ?? 'RON' }}</div>
    </div>

    <div class="section">
        <h3>Poziții factură</h3>
        <table>
            <thead>
                <tr>
                    <th>Cod articol</th>
                    <th>Denumire articol</th>
                    <th>Cantitate</th>
                    <th>Preț unitar</th>
                    <th>Valoare linie</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                    <tr>
                        <td>{{ $item->cod_articol }}</td>
                        <td>{{ $item->denumire_articol }}</td>
                        <td>{{ number_format((float) $item->cantitate, 2, ',', '.') }}</td>
                        <td>{{ number_format((float) $item->pret_unitar, 2, ',', '.') }}</td>
                        <td>{{ number_format((float) $item->valoare_linie, 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">Nu există poziții.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <h3>Încasări</h3>
        <table>
            <thead>
                <tr>
                    <th>Cod încasare</th>
                    <th>Modalitate</th>
                    <th>Status</th>
                    <th>Suma</th>
                </tr>
            </thead>
            <tbody>
                @forelse($receipts as $receipt)
                    <tr>
                        <td>{{ $receipt->cod_incasare }}</td>
                        <td>{{ $receipt->modalitate_incasare }}</td>
                        <td>{{ $receipt->status_incasare }}</td>
                        <td>{{ number_format((float) $receipt->suma_incasata, 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">Nu există încasări înregistrate.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <table class="totals">
            <tr>
                <td>Total poziții</td>
                <td>{{ number_format((float) $totalItems, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Total încasat</td>
                <td>{{ number_format((float) $totalReceipts, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Sold curent</td>
                <td>{{ number_format((float) $sold, 2, ',', '.') }}</td>
            </tr>
        </table>
    </div>
</body>
</html>
