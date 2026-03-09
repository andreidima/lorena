@extends('layouts.app')

@section('content')
@php
    $indexRoute = \App\Support\Modules\ModuleRegistry::entityRouteName($module_key, $entity_key, 'index');
    $createRoute = \App\Support\Modules\ModuleRegistry::entityRouteName($module_key, $entity_key, 'create');
    $exportRoute = \App\Support\Modules\ModuleRegistry::entityRouteName($module_key, $entity_key, 'export-pdf');
    $isInvoiceTable = !empty($entity['invoice_pdf']);
    $hasAdvancedFilters = collect($filters)->except('q')->contains(fn ($value) => $value !== null && $value !== '');
    $entityLabel = \App\Support\Modules\DashboardAnalytics::displayEntityLabel($entity['label']);
    $moduleLabel = \App\Support\Modules\DashboardAnalytics::displayModuleLabel($module_key, $module['label'] ?? $module_key);
@endphp

<div class="mx-3 px-3 card" style="border-radius: 40px;">
    <div class="row card-header module-hero-header align-items-start g-3" style="border-radius: 40px 40px 0 0;">
        <div class="col-xl-4">
            <span class="badge culoare1 fs-5">
                <i class="fa-solid {{ $entity['icon'] ?? 'fa-table' }}"></i>
                {{ $entityLabel }}
            </span>
            <div class="small text-white mt-2">Modul: {{ $moduleLabel }}</div>
        </div>

        <div class="col-xl-5">
            <form method="GET" action="{{ route($indexRoute) }}">
                <div class="input-group input-group-sm mb-2">
                    <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input
                        type="text"
                        class="form-control"
                        name="q"
                        value="{{ $filters['q'] ?? '' }}"
                        placeholder="Caută rapid în {{ $entityLabel }}"
                    >
                    <button class="btn btn-primary text-white border border-dark" type="submit">Caută</button>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-sm btn-light border border-dark" data-bs-toggle="collapse" href="#advancedFilters" role="button" aria-expanded="{{ $hasAdvancedFilters ? 'true' : 'false' }}" aria-controls="advancedFilters">
                        <i class="fa-solid fa-sliders me-1"></i> Filtre avansate
                    </a>
                    @if (($filters['q'] ?? '') !== '' || $hasAdvancedFilters)
                        <a class="btn btn-sm btn-secondary text-white border border-dark" href="{{ route($indexRoute) }}">
                            <i class="fa-solid fa-rotate-left me-1"></i> Resetează
                        </a>
                    @endif
                </div>

                <div class="collapse mt-3 {{ $hasAdvancedFilters ? 'show' : '' }}" id="advancedFilters">
                    <div class="p-2 rounded" style="background-color: rgba(255, 255, 255, 0.18);">
                        <div class="row g-2">
                            @foreach ($columns as $column)
                                @php
                                    $name = $column['name'];
                                    $type = $column['type'] ?? 'string';
                                @endphp
                                <div class="col-lg-6">
                                    @if ($type === 'select')
                                        <label class="form-label form-label-sm text-white mb-1" for="filter_{{ $name }}">{{ $column['label'] }}</label>
                                        <select class="form-select form-select-sm rounded-3" id="filter_{{ $name }}" name="{{ $name }}">
                                            <option value="">Toate</option>
                                            @foreach (($column['options'] ?? []) as $option)
                                                <option value="{{ $option }}" @selected(($filters[$name] ?? '') === $option)>{{ $option }}</option>
                                            @endforeach
                                        </select>
                                    @elseif (in_array($type, ['integer', 'decimal', 'bigint'], true))
                                        <label class="form-label form-label-sm text-white mb-1">{{ $column['label'] }} (interval)</label>
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <input
                                                    type="text"
                                                    inputmode="decimal"
                                                    class="form-control form-control-sm rounded-3"
                                                    name="{{ $name }}_min"
                                                    placeholder="Min"
                                                    value="{{ $filters[$name . '_min'] ?? '' }}"
                                                >
                                            </div>
                                            <div class="col-6">
                                                <input
                                                    type="text"
                                                    inputmode="decimal"
                                                    class="form-control form-control-sm rounded-3"
                                                    name="{{ $name }}_max"
                                                    placeholder="Max"
                                                    value="{{ $filters[$name . '_max'] ?? '' }}"
                                                >
                                            </div>
                                        </div>
                                    @else
                                        <label class="form-label form-label-sm text-white mb-1" for="filter_{{ $name }}">{{ $column['label'] }}</label>
                                        <input
                                            type="text"
                                            class="form-control form-control-sm rounded-3"
                                            id="filter_{{ $name }}"
                                            name="{{ $name }}"
                                            value="{{ $filters[$name] ?? '' }}"
                                        >
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        <div class="d-flex gap-2 mt-3">
                            <button class="btn btn-sm btn-primary text-white border border-dark" type="submit">
                                <i class="fa-solid fa-filter me-1"></i> Aplică filtre
                            </button>
                            <a class="btn btn-sm btn-secondary text-white border border-dark" href="{{ route($indexRoute) }}">
                                Resetează
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-xl-3 text-end">
            <a class="btn btn-sm btn-success text-white border border-dark rounded-3 mb-2 w-100" href="{{ route($createRoute) }}">
                <i class="fa-solid fa-plus me-1"></i> Adaugă resursă
            </a>
            <a class="btn btn-sm btn-warning text-dark border border-dark rounded-3 w-100" href="{{ route($exportRoute, request()->query()) }}">
                <i class="fa-solid fa-file-pdf me-1"></i> Export PDF
            </a>
        </div>
    </div>

    <div class="card-body px-0 py-3">
        @include('errors.errors')

        <div class="px-3 pb-2 small text-muted">
            Total rezultate: {{ number_format($rows->total(), 0, ',', '.') }}
        </div>

        <div class="table-responsive rounded">
            <table class="table table-striped table-hover rounded" aria-label="Tabela {{ $entityLabel }}">
                <thead class="text-white rounded">
                    <tr>
                        <th scope="col" class="text-white culoare2" width="5%">#</th>
                        @foreach ($columns as $column)
                            <th scope="col" class="text-white culoare2">{{ $column['label'] }}</th>
                        @endforeach
                        <th scope="col" class="text-white culoare2 text-end" width="18%">Acțiuni</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td>{{ ($rows->currentPage() - 1) * $rows->perPage() + $loop->index + 1 }}</td>
                            @foreach ($columns as $column)
                                @php
                                    $name = $column['name'];
                                    $type = $column['type'] ?? 'string';
                                    $value = $row->$name;
                                @endphp
                                <td>
                                    @if ($type === 'decimal' && $value !== null && $value !== '')
                                        {{ number_format((float) $value, 2, ',', '.') }}
                                    @else
                                        {{ $value ?? '-' }}
                                    @endif
                                </td>
                            @endforeach
                            <td class="text-end">
                                <div class="d-inline-flex flex-wrap justify-content-end gap-1">
                                    @if ($isInvoiceTable)
                                        <a
                                            href="{{ route(\App\Support\Modules\ModuleRegistry::entityRouteName($module_key, $entity_key, 'invoice-pdf'), $row->id) }}"
                                            class="btn btn-sm btn-outline-warning"
                                            title="Exportă factura PDF"
                                            aria-label="Exportă factura PDF"
                                        >
                                            <i class="fa-solid fa-file-pdf"></i>
                                            <span class="d-none d-xl-inline ms-1">PDF</span>
                                        </a>
                                    @endif
                                    <a
                                        href="{{ route(\App\Support\Modules\ModuleRegistry::entityRouteName($module_key, $entity_key, 'edit'), $row->id) }}"
                                        class="btn btn-sm btn-outline-primary"
                                        title="Modifică înregistrarea"
                                        aria-label="Modifică înregistrarea"
                                    >
                                        <i class="fa-solid fa-pen"></i>
                                        <span class="d-none d-xl-inline ms-1">Modifică</span>
                                    </a>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        data-bs-toggle="modal"
                                        data-bs-target="#confirmDeleteModal"
                                        data-delete-url="{{ route(\App\Support\Modules\ModuleRegistry::entityRouteName($module_key, $entity_key, 'destroy'), $row->id) }}"
                                        data-delete-label="#{{ $row->id }}"
                                        title="Șterge înregistrarea"
                                        aria-label="Șterge înregistrarea"
                                    >
                                        <i class="fa-solid fa-trash"></i>
                                        <span class="d-none d-xl-inline ms-1">Șterge</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($columns) + 2 }}" class="text-center text-muted py-5">
                                Nu există înregistrări pentru această subcategorie.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <nav>
            <ul class="pagination justify-content-center">
                {{ $rows->links() }}
            </ul>
        </nav>
    </div>
</div>

<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="confirmDeleteModalLabel">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i> Confirmare ștergere
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Ești sigur că vrei să ștergi înregistrarea <strong id="deleteRecordLabel"></strong>? Acțiunea nu poate fi anulată.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Renunță</button>
                <form method="POST" id="deleteRecordForm" action="">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Șterge definitiv</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var deleteModal = document.getElementById('confirmDeleteModal');

    if (!deleteModal) {
        return;
    }

    deleteModal.addEventListener('show.bs.modal', function (event) {
        var trigger = event.relatedTarget;

        if (!trigger) {
            return;
        }

        var action = trigger.getAttribute('data-delete-url') || '';
        var label = trigger.getAttribute('data-delete-label') || '';

        var form = deleteModal.querySelector('#deleteRecordForm');
        var labelElement = deleteModal.querySelector('#deleteRecordLabel');

        if (form) {
            form.setAttribute('action', action);
        }

        if (labelElement) {
            labelElement.textContent = label;
        }
    });
});
</script>
@endsection
