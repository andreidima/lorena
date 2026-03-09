<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Support\Modules\DashboardAnalytics;
use App\Support\Modules\ModuleRegistry;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModuleDashboardController extends Controller
{
    public function show(Request $request): View
    {
        $moduleKey = (string) $request->route('moduleKey');
        $module = ModuleRegistry::module($moduleKey);
        $analysis = DashboardAnalytics::analyzeModule($moduleKey, $module);
        $entityStats = $analysis['entity_stats'];
        $statusTotals = $analysis['status_totals'];
        $numericHighlights = $analysis['numeric_highlights'];
        $totalRecords = (int) $analysis['total_records'];
        $moduleCompleteRows = (int) $analysis['module_complete_rows'];
        $moduleIncompleteRows = max(0, $totalRecords - $moduleCompleteRows);

        $topEntitiesByVolume = array_slice($entityStats, 0, 10);

        $entitiesByFill = $entityStats;
        usort($entitiesByFill, fn ($a, $b) => $b['required_fill_rate'] <=> $a['required_fill_rate']);
        $topEntitiesByFill = array_slice($entitiesByFill, 0, 10);

        $entitiesByNumeric = $entityStats;
        usort($entitiesByNumeric, fn ($a, $b) => $b['numeric_total'] <=> $a['numeric_total']);
        $topEntitiesByNumeric = array_slice($entitiesByNumeric, 0, 10);

        $dashboardCharts = [
            $this->buildChart(
                'module-chart-volume',
                'Volum pe subcategorii',
                'Numărul de înregistrări pentru fiecare subcategorie',
                'bar',
                array_map(fn ($item) => $item['label'], $topEntitiesByVolume),
                [[
                    'label' => 'Înregistrări',
                    'data' => array_map(fn ($item) => (int) $item['count'], $topEntitiesByVolume),
                ]]
            ),
            $this->buildChart(
                'module-chart-fill',
                'Completarea câmpurilor obligatorii',
                'Calitatea datelor pe subcategorie',
                'radar',
                array_map(fn ($item) => $item['label'], $topEntitiesByFill),
                [[
                    'label' => 'Completare (%)',
                    'data' => array_map(fn ($item) => round((float) $item['required_fill_rate'], 2), $topEntitiesByFill),
                ]]
            ),
            $this->buildChart(
                'module-chart-numeric',
                'Valori numerice agregate',
                'Suma câmpurilor numerice pe subcategorie',
                'bar',
                array_map(fn ($item) => $item['label'], $topEntitiesByNumeric),
                [[
                    'label' => 'Valoare',
                    'data' => array_map(fn ($item) => round((float) $item['numeric_total'], 2), $topEntitiesByNumeric),
                ]]
            ),
            $this->buildChart(
                'module-chart-status',
                'Statusuri dominante',
                'Distribuția statusurilor principale',
                'doughnut',
                array_slice(array_keys($statusTotals), 0, 10),
                [[
                    'label' => 'Total',
                    'data' => array_map('intval', array_slice(array_values($statusTotals), 0, 10)),
                ]]
            ),
            $this->buildChart(
                'module-chart-structure',
                'Structura datelor',
                'Raport între date complete și incomplete',
                'pie',
                ['Date complete', 'Date incomplete'],
                [[
                    'label' => 'Structură',
                    'data' => [$moduleCompleteRows, $moduleIncompleteRows],
                ]]
            ),
            $this->buildChart(
                'module-chart-highlights',
                'Top indicatori numerici',
                'Cele mai mari totaluri din câmpurile numerice',
                'line',
                array_map(fn ($item) => $item['label'], array_slice($numericHighlights, 0, 10)),
                [[
                    'label' => 'Valoare',
                    'data' => array_map(fn ($item) => round((float) $item['value'], 2), array_slice($numericHighlights, 0, 10)),
                ]]
            ),
            $this->buildChart(
                'module-chart-entity-share',
                'Pondere subcategorii',
                'Distribuția înregistrărilor pe subcategorii',
                'polarArea',
                array_map(fn ($item) => $item['label'], $topEntitiesByVolume),
                [[
                    'label' => 'Pondere',
                    'data' => array_map(fn ($item) => (int) $item['count'], $topEntitiesByVolume),
                ]]
            ),
        ];

        return view('modules.dashboard.index', [
            'module_key' => $moduleKey,
            'module' => $module,
            'module_label' => $analysis['module_label'],
            'entity_stats' => $entityStats,
            'summary_cards' => $analysis['summary_cards'],
            'total_records' => $totalRecords,
            'table_count' => $analysis['table_count'],
            'most_loaded_entity' => $analysis['most_loaded_entity'],
            'module_fill_rate' => $analysis['module_fill_rate'],
            'numeric_total' => $analysis['numeric_total'],
            'avg_numeric_per_record' => $analysis['avg_numeric_per_record'],
            'dashboard_charts' => $dashboardCharts,
        ]);
    }

    /**
     * @param array<int, string> $labels
     * @param array<int, array<string, mixed>> $datasets
     */
    private function buildChart(string $id, string $title, string $subtitle, string $type, array $labels, array $datasets): array
    {
        $hasData = !empty($labels) && collect($datasets)->contains(function ($dataset) {
            $values = $dataset['data'] ?? [];

            if (!is_array($values) || $values === []) {
                return false;
            }

            return true;
        });

        return [
            'id' => $id,
            'title' => $title,
            'subtitle' => $subtitle,
            'type' => $type,
            'labels' => $labels,
            'datasets' => $datasets,
            'has_data' => $hasData,
        ];
    }
}
