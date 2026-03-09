<?php

namespace Database\Seeders\Modules;

use App\Support\Modules\ModuleRegistry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ModuleDataSeeder extends Seeder
{
    private array $idPool = [];

    private array $localitati = [
        'Bucuresti', 'Cluj-Napoca', 'Iasi', 'Timisoara', 'Constanta', 'Brasov', 'Oradea', 'Craiova',
        'Ploiesti', 'Arad', 'Sibiu', 'Galati', 'Bacau', 'Targu Mures', 'Baia Mare',
    ];

    private array $produse = [
        'Motocoasa profesionala',
        'Drujba benzina',
        'Trimmer electric',
        'Freza agricola compacta',
        'Motopompa irigatii',
        'Pompa stropit livezi',
        'Alternator auto',
        'Filtru ulei',
        'Set ambreiaj',
        'Lant drujba',
        'Cap trimmy',
        'Anvelopa utilitara',
        'Curea transmisie',
    ];

    private array $persoane = [
        'Andrei Popescu', 'Mihai Ionescu', 'Raluca Stan', 'Ioana Matei', 'George Dumitru',
        'Roxana Ene', 'Alexandru Vasile', 'Diana Sandu', 'Cristina Florea', 'Stefan Radu',
    ];

    public function run(): void
    {
        $seedCount = ModuleRegistry::seedCount();
        $modules = ModuleRegistry::modules();

        foreach ($modules as $moduleKey => $module) {
            foreach (($module['entities'] ?? []) as $entityKey => $entity) {
                $table = $entity['table'];
                $columns = $entity['columns'] ?? [];
                $records = [];

                for ($i = 1; $i <= $seedCount; $i++) {
                    $row = [];

                    foreach ($columns as $column) {
                        $name = $column['name'];
                        $type = $column['type'] ?? 'string';
                        $row[$name] = $this->fakeValue($table, $name, $type, $column, $i);
                    }

                    $row = $this->applyDerivedValues($row);
                    $row['created_at'] = now();
                    $row['updated_at'] = now();
                    $records[] = $row;
                }

                DB::table($table)->insert($records);
                $this->idPool[$table] = DB::table($table)->pluck('id')->all();
            }
        }
    }

    private function fakeValue(string $table, string $name, string $type, array $column, int $index)
    {
        if ($type === 'select') {
            $options = $column['options'] ?? [];
            return $options[array_rand($options)] ?? null;
        }

        if ($type === 'bigint') {
            $referenceTable = $column['reference_table'] ?? null;
            if ($referenceTable && !empty($this->idPool[$referenceTable])) {
                return $this->idPool[$referenceTable][array_rand($this->idPool[$referenceTable])];
            }
            return null;
        }

        if ($type === 'integer') {
            if (str_contains($name, 'luni')) {
                return random_int(6, 120);
            }
            return random_int(1, 300);
        }

        if ($type === 'decimal') {
            if (str_contains($name, 'procent') || str_contains($name, 'marja') || str_contains($name, 'cota') || str_contains($name, 'nivel') || str_contains($name, 'scor')) {
                return random_int(5, 100);
            }
            if (str_contains($name, 'cantitate') || str_contains($name, 'ore') || str_contains($name, 'stoc')) {
                return random_int(1, 500);
            }

            return random_int(500, 120000);
        }

        if (str_contains($name, 'telefon')) {
            return '07' . random_int(10000000, 99999999);
        }

        if (str_contains($name, 'localitate')) {
            return $this->localitati[array_rand($this->localitati)];
        }

        if (str_contains($name, 'persoana_contact') || str_contains($name, 'responsabil') || str_contains($name, 'manager') || str_contains($name, 'auditor')) {
            return $this->persoane[array_rand($this->persoane)];
        }

        if (str_starts_with($name, 'cod_') || str_contains($name, 'cod_')) {
            $prefix = strtoupper(Str::substr(str_replace(['_', '-'], '', $table), 0, 3));
            return $prefix . '-' . str_pad((string) $index, 4, '0', STR_PAD_LEFT);
        }

        if (str_contains($name, 'denumire') || str_contains($name, 'articol') || str_contains($name, 'obiect') || str_contains($name, 'indicator')) {
            return $this->produse[array_rand($this->produse)] . ' ' . random_int(10, 99);
        }

        if (str_contains($name, 'descriere') || str_contains($name, 'explicatie')) {
            return 'Operatiune pentru fluxul comercial si operational din companie.';
        }

        if (str_contains($name, 'formula_calcul')) {
            return '(valoare realizata / valoare tinta) * 100';
        }

        if (str_contains($name, 'termen_livrare')) {
            return random_int(2, 20) . ' zile lucratoare';
        }

        if (str_contains($name, 'termen_plata')) {
            return random_int(5, 45) . ' zile';
        }

        if ($type === 'text') {
            return 'Date operationale inregistrate pentru compania de scule motorizate, echipamente agricole si piese auto.';
        }

        if (str_contains($name, 'moneda')) {
            return 'RON';
        }

        if (str_contains($name, 'unitate_masura')) {
            return 'buc';
        }

        if (str_contains($name, 'referinta')) {
            return 'REF-' . random_int(10000, 99999);
        }

        if (str_contains($name, 'tip_')) {
            return 'Operational';
        }

        if (str_contains($name, 'status_')) {
            return 'Activ';
        }

        return 'Valoare ' . random_int(100, 999);
    }

    private function applyDerivedValues(array $row): array
    {
        $toFloat = static fn ($value): float => (float) ($value ?? 0);

        if (isset($row['cantitate'], $row['pret_unitar'])) {
            $row['valoare_linie'] = $toFloat($row['cantitate']) * $toFloat($row['pret_unitar']);
        }

        if (isset($row['cantitate_alocata'], $row['cost_unitar'])) {
            $row['cost_total'] = $toFloat($row['cantitate_alocata']) * $toFloat($row['cost_unitar']);
        }

        if (isset($row['cantitate_consumata'], $row['cost_unitar'])) {
            $row['cost_total'] = $toFloat($row['cantitate_consumata']) * $toFloat($row['cost_unitar']);
        }

        if (isset($row['venit_total'], $row['cost_total'])) {
            $profit = $toFloat($row['venit_total']) - $toFloat($row['cost_total']);
            $row['profit_brut'] = $profit;
            $row['marja_profit'] = $toFloat($row['venit_total']) !== 0
                ? ($profit / $toFloat($row['venit_total'])) * 100
                : 0;
        }

        if (isset($row['valoare_planificata'], $row['valoare_consumata'])) {
            $row['valoare_disponibila'] = $toFloat($row['valoare_planificata']) - $toFloat($row['valoare_consumata']);
        }

        if (isset($row['suma_creanta'], $row['suma_incasata'])) {
            $row['sold_creanta'] = $toFloat($row['suma_creanta']) - $toFloat($row['suma_incasata']);
        }

        if (isset($row['suma_datorie'], $row['suma_platita'])) {
            $row['sold_datorie'] = $toFloat($row['suma_datorie']) - $toFloat($row['suma_platita']);
        }

        if (isset($row['valoare_totala']) && array_key_exists('sold_factura', $row)) {
            $row['sold_factura'] = $toFloat($row['valoare_totala']) * 0.35;
        }

        if (isset($row['valoare_anterioara'], $row['valoare_noua'])) {
            $row['diferenta_valoare'] = $toFloat($row['valoare_noua']) - $toFloat($row['valoare_anterioara']);
        }

        if (isset($row['valoare_kpi'], $row['prag_alerta'])) {
            $row['scor_performanta'] = $toFloat($row['prag_alerta']) !== 0
                ? ($toFloat($row['valoare_kpi']) / $toFloat($row['prag_alerta'])) * 100
                : 0;
        }

        return $row;
    }
}
