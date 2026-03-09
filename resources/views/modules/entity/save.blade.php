@extends('layouts.app')

@section('content')
@php
    $isEdit = !empty($row);
    $indexRoute = \App\Support\Modules\ModuleRegistry::entityRouteName($module_key, $entity_key, 'index');
    $storeRoute = \App\Support\Modules\ModuleRegistry::entityRouteName($module_key, $entity_key, 'store');
    $updateRoute = \App\Support\Modules\ModuleRegistry::entityRouteName($module_key, $entity_key, 'update');
    $orderedColumns = collect($columns)->sortByDesc(fn ($column) => (bool) ($column['required'] ?? false))->values();
    $entityLabel = \App\Support\Modules\DashboardAnalytics::displayEntityLabel($entity['label']);
    $moduleLabel = \App\Support\Modules\DashboardAnalytics::displayModuleLabel($module_key, $module['label'] ?? $module_key);
@endphp

<div class="container">
    <div class="card border-0 shadow">
        <div class="module-hero-header text-white p-3" style="border-radius: 20px 20px 0 0;">
            <span class="badge culoare1 fs-5">
                <i class="fa-solid {{ $entity['icon'] ?? 'fa-table' }} me-1"></i>
                {{ $isEdit ? 'Modifică' : 'Adaugă' }} {{ $entityLabel }}
            </span>
            <div class="small mt-2">Modul {{ $moduleLabel }}</div>
        </div>

        <div class="card-body">
            @include('errors.errors')

            <p class="small text-muted mb-3">
                Câmpurile marcate cu <span class="text-danger">*</span> sunt obligatorii.
                Pentru valorile zecimale poți folosi punct sau virgulă.
            </p>

            <form method="POST" action="{{ $isEdit ? route($updateRoute, $row['id']) : route($storeRoute) }}">
                @csrf
                @if($isEdit)
                    @method('PUT')
                @endif

                <div class="row mb-4 pt-2 rounded-3 module-form-wrap">
                    @foreach ($orderedColumns as $column)
                        @php
                            $name = $column['name'];
                            $type = $column['type'] ?? 'string';
                            $required = (bool) ($column['required'] ?? false);
                            $fieldValue = old($name, $row[$name] ?? '');
                        @endphp
                        <div class="col-lg-6 mb-3">
                            <label for="{{ $name }}" class="mb-1 ps-2 d-block">
                                {{ $column['label'] }} {!! $required ? '<span class="text-danger">*</span>' : '' !!}
                            </label>

                            @if ($type === 'select')
                                <select name="{{ $name }}" id="{{ $name }}" class="form-select rounded-3 {{ $errors->has($name) ? 'is-invalid' : '' }}" {{ $required ? 'required' : '' }}>
                                    <option value="">Selectează...</option>
                                    @foreach (($column['options'] ?? []) as $option)
                                        <option value="{{ $option }}" @selected($fieldValue === $option)>{{ $option }}</option>
                                    @endforeach
                                </select>
                            @elseif ($type === 'text')
                                <textarea name="{{ $name }}" id="{{ $name }}" rows="2" class="form-control rounded-3 {{ $errors->has($name) ? 'is-invalid' : '' }}" {{ $required ? 'required' : '' }}>{{ $fieldValue }}</textarea>
                            @elseif ($type === 'decimal')
                                <input
                                    type="text"
                                    inputmode="decimal"
                                    name="{{ $name }}"
                                    id="{{ $name }}"
                                    value="{{ $fieldValue }}"
                                    class="form-control rounded-3 {{ $errors->has($name) ? 'is-invalid' : '' }}"
                                    {{ $required ? 'required' : '' }}
                                >
                            @elseif (in_array($type, ['integer', 'bigint'], true))
                                <input
                                    type="number"
                                    step="1"
                                    name="{{ $name }}"
                                    id="{{ $name }}"
                                    value="{{ $fieldValue }}"
                                    class="form-control rounded-3 {{ $errors->has($name) ? 'is-invalid' : '' }}"
                                    {{ $required ? 'required' : '' }}
                                >
                            @else
                                <input
                                    type="text"
                                    name="{{ $name }}"
                                    id="{{ $name }}"
                                    value="{{ $fieldValue }}"
                                    class="form-control rounded-3 {{ $errors->has($name) ? 'is-invalid' : '' }}"
                                    {{ $required ? 'required' : '' }}
                                >
                            @endif

                            @error($name)
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    @endforeach
                </div>

                <div class="row">
                    <div class="col-lg-12 mb-2 d-flex justify-content-center">
                        <button type="submit" class="btn btn-primary text-white me-3 rounded-3">
                            <i class="fa-solid fa-save me-1"></i> {{ $isEdit ? 'Actualizează' : 'Salvează' }}
                        </button>
                        <a class="btn btn-secondary rounded-3" href="{{ Session::get('returnUrl', route($indexRoute)) }}">
                            Renunță
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
