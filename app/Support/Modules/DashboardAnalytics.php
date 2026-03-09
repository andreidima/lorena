<?php

namespace App\Support\Modules;

use Illuminate\Support\Facades\DB;

class DashboardAnalytics
{
    /**
     * @var array<string, string>
     */
    private const MODULE_LABELS = [
        'proiecte' => 'Proiecte',
        'contracte' => 'Contracte',
        'aprovizionare' => 'Aprovizionare',
        'mijloace-fixe' => 'Mijloace fixe',
        'contabilitate' => 'Contabilitate',
        'raportare' => 'Raportare și control',
    ];

    public static function displayModuleLabel(string $moduleKey, ?string $fallback = null): string
    {
        return self::MODULE_LABELS[$moduleKey] ?? (string) ($fallback ?? $moduleKey);
    }

    public static function analyzeAllModules(array $modules): array
    {
        $moduleStats = [];
        $entityStats = [];
        $statusTotals = [];
        $totalRecords = 0;
        $totalEntities = 0;

        foreach ($modules as $moduleKey => $module) {
            $analysis = self::analyzeModule($moduleKey, is_array($module) ? $module : []);
            $moduleStats[] = [
                'module_key' => $moduleKey,
                'module_label' => $analysis['module_label'],
                'total_records' => $analysis['total_records'],
                'entity_count' => $analysis['table_count'],
                'numeric_total' => $analysis['numeric_total'],
                'fill_rate' => $analysis['module_fill_rate'],
                'avg_numeric_per_record' => $analysis['avg_numeric_per_record'],
            ];

            $entityStats = array_merge($entityStats, $analysis['entity_stats']);
            $totalRecords += $analysis['total_records'];
            $totalEntities += $analysis['table_count'];

            foreach ($analysis['status_totals'] as $status => $value) {
                $statusTotals[$status] = ($statusTotals[$status] ?? 0) + (int) $value;
            }
        }

        usort($moduleStats, fn ($a, $b) => $b['total_records'] <=> $a['total_records']);
        usort($entityStats, fn ($a, $b) => $b['count'] <=> $a['count']);
        arsort($statusTotals);

        return [
            'module_stats' => $moduleStats,
            'entity_stats' => $entityStats,
            'status_totals' => $statusTotals,
            'total_records' => $totalRecords,
            'total_entities' => $totalEntities,
        ];
    }

    public static function analyzeModule(string $moduleKey, array $module): array
    {
        $entities = is_array($module['entities'] ?? null) ? $module['entities'] : [];

        $entityStats = [];
        $summaryCards = [];
        $statusTotals = [];
        $numericHighlights = [];

        $totalRecords = 0;
        $tableCount = count($entities);
        $moduleCompleteRows = 0;
        $numericTotal = 0.0;
        $mostLoadedEntity = null;

        foreach ($entities as $entityKey => $entity) {
            if (!is_array($entity)) {
                continue;
            }

            $table = (string) ($entity['table'] ?? '');
            $columns = is_array($entity['columns'] ?? null) ? $entity['columns'] : [];

            if ($table === '') {
                continue;
            }

            $count = (int) DB::table($table)->count();
            $totalRecords += $count;

            if (!$mostLoadedEntity || $count > $mostLoadedEntity['count']) {
                $mostLoadedEntity = [
                    'key' => (string) $entityKey,
                    'label' => self::displayEntityLabel((string) ($entity['label'] ?? $entityKey)),
                    'count' => $count,
                ];
            }

            $requiredColumns = array_values(array_filter(
                $columns,
                fn ($column) => (bool) (($column['required'] ?? false) && !empty($column['name']))
            ));

            $completion = self::calculateRequiredCompletion($table, $requiredColumns, $count);
            $requiredFillRate = $completion['fill_rate'];
            $completeRows = $completion['complete_rows'];
            $moduleCompleteRows += $completeRows;

            $numericTotals = [];
            $entityNumericTotal = 0.0;

            foreach ($columns as $column) {
                if (!is_array($column)) {
                    continue;
                }

                $columnName = (string) ($column['name'] ?? '');
                $columnType = (string) ($column['type'] ?? '');

                if ($columnName === '' || !in_array($columnType, ['integer', 'bigint', 'decimal'], true)) {
                    continue;
                }

                $value = (float) DB::table($table)->sum($columnName);
                $label = self::displayEntityLabel((string) ($column['label'] ?? $columnName));

                $numericTotals[] = [
                    'label' => $label,
                    'value' => $value,
                ];

                $numericHighlights[] = [
                    'label' => self::displayEntityLabel((string) ($entity['label'] ?? $entityKey)) . ': ' . $label,
                    'value' => $value,
                ];

                $entityNumericTotal += $value;
            }

            $numericTotal += $entityNumericTotal;

            $statusBreakdown = [];
            $statusColumn = self::resolveStatusColumn($columns);

            if ($statusColumn !== null) {
                $statusRows = DB::table($table)
                    ->select($statusColumn . ' as status', DB::raw('count(*) as total'))
                    ->whereNotNull($statusColumn)
                    ->where($statusColumn, '!=', '')
                    ->groupBy($statusColumn)
                    ->orderByDesc('total')
                    ->limit(10)
                    ->get();

                foreach ($statusRows as $statusRow) {
                    $statusName = self::displayEntityLabel((string) $statusRow->status);
                    $statusCount = (int) $statusRow->total;

                    $statusBreakdown[] = [
                        'status' => $statusName,
                        'total' => $statusCount,
                    ];

                    $statusTotals[$statusName] = ($statusTotals[$statusName] ?? 0) + $statusCount;
                }
            }

            $entityLabel = self::displayEntityLabel((string) ($entity['label'] ?? $entityKey));

            $entityStats[] = [
                'entity_key' => (string) $entityKey,
                'label' => $entityLabel,
                'icon' => (string) ($entity['icon'] ?? 'fa-table'),
                'count' => $count,
                'required_fill_rate' => $requiredFillRate,
                'complete_rows' => $completeRows,
                'numeric_total' => $entityNumericTotal,
                'numeric_totals' => array_slice($numericTotals, 0, 4),
                'status_breakdown' => $statusBreakdown,
            ];

            $summaryCards[] = [
                'title' => 'Total ' . $entityLabel,
                'value' => number_format($count, 0, ',', '.'),
                'hint' => 'înregistrări',
            ];

            $summaryCards[] = [
                'title' => 'Completare ' . $entityLabel,
                'value' => number_format($requiredFillRate, 2, ',', '.') . '%',
                'hint' => 'câmpuri obligatorii',
            ];
        }

        usort($entityStats, fn ($a, $b) => $b['count'] <=> $a['count']);
        usort($numericHighlights, fn ($a, $b) => $b['value'] <=> $a['value']);
        arsort($statusTotals);

        $moduleFillRate = $totalRecords > 0 ? ($moduleCompleteRows / $totalRecords) * 100 : 0.0;
        $avgNumericPerRecord = $totalRecords > 0 ? $numericTotal / $totalRecords : 0.0;

        return [
            'module_key' => $moduleKey,
            'module_label' => self::displayModuleLabel($moduleKey, (string) ($module['label'] ?? $moduleKey)),
            'entity_stats' => $entityStats,
            'summary_cards' => array_slice($summaryCards, 0, 18),
            'total_records' => $totalRecords,
            'table_count' => $tableCount,
            'module_fill_rate' => $moduleFillRate,
            'module_complete_rows' => $moduleCompleteRows,
            'numeric_total' => $numericTotal,
            'avg_numeric_per_record' => $avgNumericPerRecord,
            'most_loaded_entity' => $mostLoadedEntity,
            'status_totals' => $statusTotals,
            'numeric_highlights' => array_slice($numericHighlights, 0, 12),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $requiredColumns
     */
    private static function calculateRequiredCompletion(string $table, array $requiredColumns, int $totalRows): array
    {
        if ($totalRows === 0 || empty($requiredColumns)) {
            return [
                'fill_rate' => 0.0,
                'complete_rows' => 0,
            ];
        }

        $query = DB::table($table);

        foreach ($requiredColumns as $column) {
            $name = (string) ($column['name'] ?? '');
            $type = (string) ($column['type'] ?? '');

            if ($name === '') {
                continue;
            }

            $query->whereNotNull($name);

            if (in_array($type, ['string', 'text', 'select'], true)) {
                $query->where($name, '!=', '');
            }
        }

        $completeRows = (int) $query->count();

        return [
            'fill_rate' => ($completeRows / $totalRows) * 100,
            'complete_rows' => $completeRows,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $columns
     */
    private static function resolveStatusColumn(array $columns): ?string
    {
        foreach ($columns as $column) {
            if (!is_array($column)) {
                continue;
            }

            $name = (string) ($column['name'] ?? '');
            $type = (string) ($column['type'] ?? '');

            if ($type === 'select' && str_contains($name, 'status')) {
                return $name;
            }
        }

        foreach ($columns as $column) {
            if (!is_array($column)) {
                continue;
            }

            if ((string) ($column['type'] ?? '') === 'select') {
                return (string) ($column['name'] ?? '');
            }
        }

        return null;
    }

    public static function displayEntityLabel(string $label): string
    {
        $map = [
            ' si ' => ' și ',
            'Actiuni' => 'Acțiuni',
            'actiuni' => 'acțiuni',
            'Pozitii' => 'Poziții',
            'pozitii' => 'poziții',
            'Incasari' => 'Încasări',
            'incasari' => 'încasări',
            'Esalonari' => 'Eșalonări',
            'esalonari' => 'eșalonări',
            'Mijloace Fixe' => 'Mijloace fixe',
        ];

        return strtr($label, $map);
    }
}
