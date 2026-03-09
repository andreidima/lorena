@extends('layouts.app')

@section('content')
<div class="container-fluid px-3 module-dashboard-page module-{{ $module_key }}">
    <div class="module-dashboard-hero mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2 class="text-white mb-1">
                    <i class="fa-solid {{ $module['icon'] ?? 'fa-chart-pie' }} me-2"></i>
                    Panou {{ $module_label }}
                </h2>
                <p class="text-white mb-0">Indicatori agregați din toate subcategoriile modulului.</p>
            </div>
            <span class="module-pill">Tabele: {{ $table_count }}</span>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="card module-core-card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1"><i class="fa-solid fa-database me-1"></i> Total înregistrări</p>
                    <h3 class="mb-0">{{ number_format($total_records, 0, ',', '.') }}</h3>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card module-core-card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1"><i class="fa-solid fa-table-list me-1"></i> Subcategorii active</p>
                    <h3 class="mb-0">{{ number_format($table_count, 0, ',', '.') }}</h3>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card module-core-card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1"><i class="fa-solid fa-list-check me-1"></i> Completare medie</p>
                    <h3 class="mb-0">{{ number_format($module_fill_rate, 2, ',', '.') }}%</h3>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card module-core-card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1"><i class="fa-solid fa-ranking-star me-1"></i> Cea mai încărcată subcategorie</p>
                    <h5 class="mb-0">{{ $most_loaded_entity['label'] ?? '-' }}</h5>
                    <small class="text-muted">{{ number_format($most_loaded_entity['count'] ?? 0, 0, ',', '.') }} înregistrări</small>
                </div>
            </div>
        </div>
    </div>

    <section class="module-chart-section mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <h3 class="h5 mb-0">Grafice statistice</h3>
            <span class="small text-muted">Date reale din tabelele modulului</span>
        </div>
        <div class="row g-3">
            @foreach ($dashboard_charts as $chart)
                <div class="col-xxl-4 col-lg-6">
                    <div class="card stats-chart-card h-100">
                        <div class="card-body">
                            <h4 class="h6 mb-1">{{ $chart['title'] }}</h4>
                            <div class="small text-muted mb-3">{{ $chart['subtitle'] }}</div>

                            @if ($chart['has_data'])
                                <div class="stats-chart-wrap">
                                    <canvas data-chart='@json($chart)'></canvas>
                                </div>
                            @else
                                <div class="stats-chart-empty">
                                    <i class="fa-solid fa-chart-simple me-1"></i>
                                    Nu există suficiente date pentru acest grafic.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <div class="row g-3 mb-4">
        @foreach ($summary_cards as $card)
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="card module-stat-card h-100">
                    <div class="card-body">
                        <div class="text-muted small">{{ $card['title'] }}</div>
                        <div class="fs-5 fw-bold">{{ $card['value'] }}</div>
                        <div class="small text-secondary">{{ $card['hint'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3">
        @foreach ($entity_stats as $item)
            <div class="col-lg-6">
                <div class="card module-entity-card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">
                            <i class="fa-solid {{ $item['icon'] }} me-1"></i>
                            {{ $item['label'] }}
                        </span>
                        <a href="{{ route(\App\Support\Modules\ModuleRegistry::entityRouteName($module_key, $item['entity_key'], 'index')) }}" class="btn btn-sm btn-outline-primary module-open-btn">
                            Deschide
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-6">
                                <div class="small text-muted">Total înregistrări</div>
                                <div class="fw-bold">{{ number_format($item['count'], 0, ',', '.') }}</div>
                            </div>
                            <div class="col-6">
                                <div class="small text-muted">Completare câmpuri obligatorii</div>
                                <div class="fw-bold">{{ number_format($item['required_fill_rate'], 2, ',', '.') }}%</div>
                            </div>
                        </div>

                        @if (!empty($item['numeric_totals']))
                            <div class="mb-3">
                                <div class="small text-muted mb-2">Totaluri numerice</div>
                                <div class="row g-2">
                                    @foreach ($item['numeric_totals'] as $num)
                                        <div class="col-6">
                                            <div class="module-number-box h-100">
                                                <div class="small">{{ $num['label'] }}</div>
                                                <div class="fw-semibold">{{ number_format((float) $num['value'], 2, ',', '.') }}</div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if (!empty($item['status_breakdown']))
                            <div>
                                <div class="small text-muted mb-2">Distribuție pe status</div>
                                @foreach ($item['status_breakdown'] as $status)
                                    <div class="d-flex justify-content-between py-1 module-status-row">
                                        <span>{{ $status['status'] }}</span>
                                        <span class="fw-semibold">{{ $status['total'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection

@push('scripts')
    @include('partials.charts-init')
@endpush
