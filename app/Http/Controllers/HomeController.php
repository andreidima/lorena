<?php

namespace App\Http\Controllers;

use App\Support\Modules\DashboardAnalytics;
use App\Support\Modules\ModuleRegistry;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(): View
    {
        $moduleMenu = ModuleRegistry::modules();
        $analysis = DashboardAnalytics::analyzeAllModules($moduleMenu);

        $moduleStats = $analysis['module_stats'];
        $entityStats = $analysis['entity_stats'];
        $statusTotals = $analysis['status_totals'];

        $moduleLabels = array_map(fn ($item) => $item['module_label'], $moduleStats);
        $moduleRecords = array_map(fn ($item) => (int) $item['total_records'], $moduleStats);
        $moduleEntityCounts = array_map(fn ($item) => (int) $item['entity_count'], $moduleStats);
        $moduleFillRates = array_map(fn ($item) => round((float) $item['fill_rate'], 2), $moduleStats);
        $moduleNumericTotals = array_map(fn ($item) => round((float) $item['numeric_total'], 2), $moduleStats);
        $moduleNumericDensity = array_map(fn ($item) => round((float) $item['avg_numeric_per_record'], 2), $moduleStats);

        $topEntitiesByVolume = array_slice($entityStats, 0, 12);

        $entitiesByFill = $entityStats;
        usort($entitiesByFill, fn ($a, $b) => $b['required_fill_rate'] <=> $a['required_fill_rate']);
        $topEntitiesByFill = array_slice($entitiesByFill, 0, 12);

        $entitiesByNumeric = $entityStats;
        usort($entitiesByNumeric, fn ($a, $b) => $b['numeric_total'] <=> $a['numeric_total']);
        $topEntitiesByNumeric = array_slice($entitiesByNumeric, 0, 12);

        $homeCharts = [
            $this->buildChart(
                'home-chart-records',
                'Înregistrări pe module',
                'Volum total de date per modul',
                'bar',
                $moduleLabels,
                [[
                    'label' => 'Înregistrări',
                    'data' => $moduleRecords,
                ]]
            ),
            $this->buildChart(
                'home-chart-share',
                'Ponderea înregistrărilor',
                'Distribuție procentuală pe module',
                'doughnut',
                $moduleLabels,
                [[
                    'label' => 'Pondere',
                    'data' => $moduleRecords,
                ]]
            ),
            $this->buildChart(
                'home-chart-entities',
                'Subcategorii pe module',
                'Numărul de tabele active în fiecare modul',
                'polarArea',
                $moduleLabels,
                [[
                    'label' => 'Subcategorii',
                    'data' => $moduleEntityCounts,
                ]]
            ),
            $this->buildChart(
                'home-chart-fill',
                'Completare câmpuri obligatorii',
                'Rată medie de completare per modul',
                'radar',
                $moduleLabels,
                [[
                    'label' => 'Completare (%)',
                    'data' => $moduleFillRates,
                ]]
            ),
            $this->buildChart(
                'home-chart-numeric',
                'Valoare numerică totală',
                'Suma câmpurilor numerice per modul',
                'bar',
                $moduleLabels,
                [[
                    'label' => 'Valoare',
                    'data' => $moduleNumericTotals,
                ]]
            ),
            $this->buildChart(
                'home-chart-top-entities',
                'Top subcategorii după volum',
                'Cele mai încărcate 12 subcategorii',
                'bar',
                array_map(fn ($item) => $item['label'], $topEntitiesByVolume),
                [[
                    'label' => 'Înregistrări',
                    'data' => array_map(fn ($item) => (int) $item['count'], $topEntitiesByVolume),
                ]]
            ),
            $this->buildChart(
                'home-chart-top-fill',
                'Top subcategorii după completare',
                'Calitatea datelor în subcategorii',
                'line',
                array_map(fn ($item) => $item['label'], $topEntitiesByFill),
                [[
                    'label' => 'Completare (%)',
                    'data' => array_map(fn ($item) => round((float) $item['required_fill_rate'], 2), $topEntitiesByFill),
                ]]
            ),
            $this->buildChart(
                'home-chart-top-numeric',
                'Top subcategorii după valoare numerică',
                'Contribuția numerică pe subcategorii',
                'bar',
                array_map(fn ($item) => $item['label'], $topEntitiesByNumeric),
                [[
                    'label' => 'Valoare',
                    'data' => array_map(fn ($item) => round((float) $item['numeric_total'], 2), $topEntitiesByNumeric),
                ]]
            ),
            $this->buildChart(
                'home-chart-status',
                'Statusuri agregate în platformă',
                'Distribuția principalelor statusuri',
                'pie',
                array_slice(array_keys($statusTotals), 0, 12),
                [[
                    'label' => 'Total',
                    'data' => array_map('intval', array_slice(array_values($statusTotals), 0, 12)),
                ]]
            ),
            $this->buildChart(
                'home-chart-density',
                'Intensitate numerică pe modul',
                'Valoare numerică medie pe înregistrare',
                'bar',
                $moduleLabels,
                [[
                    'label' => 'Valoare medie/înregistrare',
                    'data' => $moduleNumericDensity,
                ]]
            ),
        ];

        return view('home', [
            'moduleMenu' => $moduleMenu,
            'module_display_labels' => collect($moduleMenu)
                ->keys()
                ->mapWithKeys(fn ($moduleKey) => [$moduleKey => DashboardAnalytics::displayModuleLabel((string) $moduleKey, $moduleMenu[$moduleKey]['label'] ?? (string) $moduleKey)])
                ->all(),
            'home_charts' => $homeCharts,
            'home_totals' => [
                'records' => $analysis['total_records'],
                'modules' => count($moduleMenu),
                'entities' => $analysis['total_entities'],
            ],
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
