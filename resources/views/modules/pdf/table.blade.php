@php
    $moduleLabel = \App\Support\Modules\DashboardAnalytics::displayModuleLabel($module_key, $module['label'] ?? $module_key);
    $entityLabel = \App\Support\Modules\DashboardAnalytics::displayEntityLabel($entity['label']);
@endphp
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>{{ $entityLabel }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
        h1 { font-size: 18px; margin-bottom: 6px; }
        .meta { margin-bottom: 10px; color: #666; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; vertical-align: top; }
        th { background: #efefef; }
    </style>
</head>
<body>
    <h1>{{ $moduleLabel }} - {{ $entityLabel }}</h1>
    <div class="meta">
        Export generat la {{ now()->format('d.m.Y H:i') }}
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                @foreach ($columns as $column)
                    <th>{{ $column['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $index => $row)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    @foreach ($columns as $column)
                        @php
                            $name = $column['name'];
                            $type = $column['type'] ?? 'string';
                            $value = $row[$name] ?? null;
                        @endphp
                        <td>
                            @if ($type === 'decimal' && $value !== null && $value !== '')
                                {{ number_format((float) $value, 2, ',', '.') }}
                            @else
                                {{ $value ?? '-' }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) + 1 }}">Nu există date pentru export.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
