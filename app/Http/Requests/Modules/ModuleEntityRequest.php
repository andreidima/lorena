<?php

namespace App\Http\Requests\Modules;

use App\Support\Modules\ModuleRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ModuleEntityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $moduleKey = (string) $this->route('moduleKey');
        $entityKey = (string) $this->route('entityKey');
        $columns = ModuleRegistry::columns($moduleKey, $entityKey);
        $normalized = [];

        foreach ($columns as $column) {
            $name = $column['name'] ?? null;
            $type = $column['type'] ?? 'string';

            if (!$name || !$this->exists($name)) {
                continue;
            }

            $value = $this->input($name);

            if (!is_string($value)) {
                continue;
            }

            $value = trim($value);

            if ($value === '') {
                $normalized[$name] = null;
                continue;
            }

            if ($type === 'decimal') {
                $normalized[$name] = $this->normalizeDecimal($value);
                continue;
            }

            if (in_array($type, ['integer', 'bigint'], true)) {
                $normalized[$name] = $this->normalizeInteger($value);
                continue;
            }
        }

        if (!empty($normalized)) {
            $this->merge($normalized);
        }
    }

    public function rules(): array
    {
        $moduleKey = (string) $this->route('moduleKey');
        $entityKey = (string) $this->route('entityKey');
        $columns = ModuleRegistry::columns($moduleKey, $entityKey);

        $rules = [];

        foreach ($columns as $column) {
            $name = $column['name'] ?? null;
            $type = $column['type'] ?? 'string';
            $required = (bool) ($column['required'] ?? false);

            if (!$name) {
                continue;
            }

            $columnRules = [$required ? 'required' : 'nullable'];

            switch ($type) {
                case 'integer':
                case 'bigint':
                    $columnRules[] = 'integer';
                    break;
                case 'decimal':
                    $columnRules[] = 'numeric';
                    break;
                case 'text':
                    $columnRules[] = 'string';
                    $columnRules[] = 'max:2000';
                    break;
                case 'select':
                    $columnRules[] = 'string';
                    $columnRules[] = Rule::in($column['options'] ?? []);
                    break;
                default:
                    $columnRules[] = 'string';
                    $columnRules[] = 'max:255';
                    break;
            }

            $rules[$name] = $columnRules;
        }

        return $rules;
    }

    public function attributes(): array
    {
        $moduleKey = (string) $this->route('moduleKey');
        $entityKey = (string) $this->route('entityKey');
        $columns = ModuleRegistry::columns($moduleKey, $entityKey);

        $attributes = [];

        foreach ($columns as $column) {
            if (!empty($column['name']) && !empty($column['label'])) {
                $attributes[$column['name']] = $column['label'];
            }
        }

        return $attributes;
    }

    private function normalizeDecimal(string $value): string
    {
        $normalized = str_replace(["\xc2\xa0", ' '], '', trim($value));

        if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
            $lastComma = strrpos($normalized, ',');
            $lastDot = strrpos($normalized, '.');

            if ($lastComma !== false && $lastDot !== false && $lastComma > $lastDot) {
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            } else {
                $normalized = str_replace(',', '', $normalized);
            }

            return $normalized;
        }

        if (str_contains($normalized, ',')) {
            return str_replace(',', '.', $normalized);
        }

        return $normalized;
    }

    private function normalizeInteger(string $value): string
    {
        return str_replace(["\xc2\xa0", ' ', '.', ','], '', trim($value));
    }
}
