<?php

namespace App\Support\Modules;

use Illuminate\Support\Arr;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ModuleRegistry
{
    public static function modules(): array
    {
        return config('modules.modules', []);
    }

    public static function module(string $moduleKey): array
    {
        $module = Arr::get(self::modules(), $moduleKey);

        if (!is_array($module)) {
            throw new NotFoundHttpException('Modul inexistent.');
        }

        return $module;
    }

    public static function entities(string $moduleKey): array
    {
        return self::module($moduleKey)['entities'] ?? [];
    }

    public static function entity(string $moduleKey, string $entityKey): array
    {
        $entity = Arr::get(self::entities($moduleKey), $entityKey);

        if (!is_array($entity)) {
            throw new NotFoundHttpException('Subcategorie inexistenta.');
        }

        return $entity;
    }

    public static function moduleTitle(string $moduleKey): string
    {
        return self::module($moduleKey)['label'] ?? $moduleKey;
    }

    public static function entityTitle(string $moduleKey, string $entityKey): string
    {
        return self::entity($moduleKey, $entityKey)['label'] ?? $entityKey;
    }

    public static function dashboardRouteName(string $moduleKey): string
    {
        return "modules.$moduleKey.dashboard";
    }

    public static function entityRouteName(string $moduleKey, string $entityKey, string $action = 'index'): string
    {
        return "modules.$moduleKey.$entityKey.$action";
    }

    public static function columns(string $moduleKey, string $entityKey): array
    {
        return self::entity($moduleKey, $entityKey)['columns'] ?? [];
    }

    public static function table(string $moduleKey, string $entityKey): string
    {
        return (string) (self::entity($moduleKey, $entityKey)['table'] ?? '');
    }

    public static function allEntitySchemas(): array
    {
        $schemas = [];

        foreach (self::modules() as $moduleKey => $module) {
            foreach (($module['entities'] ?? []) as $entityKey => $entity) {
                $table = $entity['table'] ?? null;

                if (!$table) {
                    continue;
                }

                $schemas[$table] = [
                    'module_key' => $moduleKey,
                    'entity_key' => $entityKey,
                    'columns' => $entity['columns'] ?? [],
                ];
            }
        }

        return $schemas;
    }

    public static function seedCount(): int
    {
        return (int) config('modules.seed_count', 60);
    }
}
