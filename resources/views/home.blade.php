@extends('layouts.app')

@section('content')
<div class="container-fluid px-3 px-lg-4 home-page">
    <section class="home-hero p-4 p-lg-5">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <span class="home-hero-chip">
                    <i class="fa-solid fa-house"></i> Acasă
                </span>
                <h1 class="home-hero-title mb-2">Bine ai venit, {{ Auth::user()->name }}!</h1>
                <p class="mb-0">Accesează rapid panourile modulelor și urmărește indicatorii cheie dintr-o privire.</p>
            </div>
            <div class="col-lg-4">
                <div class="home-stat-grid">
                    <div class="home-stat-card">
                        <div class="small">Module active</div>
                        <div class="value">{{ number_format($home_totals['modules'], 0, ',', '.') }}</div>
                    </div>
                    <div class="home-stat-card">
                        <div class="small">Subcategorii active</div>
                        <div class="value">{{ number_format($home_totals['entities'], 0, ',', '.') }}</div>
                    </div>
                    <div class="home-stat-card">
                        <div class="small">Înregistrări totale</div>
                        <div class="value">{{ number_format($home_totals['records'], 0, ',', '.') }}</div>
                    </div>
                    <div class="home-stat-card">
                        <div class="small">Rol activ</div>
                        <div class="value">{{ Auth::user()->role ?? 'Utilizator' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section>
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
            <h2 class="h5 mb-0">Acces rapid către panouri</h2>
            <span class="text-muted small">Selectează un modul</span>
        </div>

        <div class="row g-3">
            @foreach ($moduleMenu as $moduleKey => $moduleConfig)
                <div class="col-xxl-2 col-xl-3 col-md-4 col-sm-6">
                    <a href="{{ route('modules.' . $moduleKey . '.dashboard') }}" class="home-module-card h-100">
                        <span class="home-module-icon home-module-{{ $moduleKey }}">
                            <i class="fa-solid {{ $moduleConfig['icon'] ?? 'fa-table' }}"></i>
                        </span>
                        <h3 class="h6 mb-1">{{ $module_display_labels[$moduleKey] ?? $moduleConfig['label'] }}</h3>
                        <div class="small text-muted">Deschide panoul modulului</div>
                    </a>
                </div>
            @endforeach

            @can('admin-action')
                <div class="col-xxl-2 col-xl-3 col-md-4 col-sm-6">
                    <a href="{{ route('users.index') }}" class="home-module-card h-100">
                        <span class="home-module-icon">
                            <i class="fa-solid fa-users"></i>
                        </span>
                        <h3 class="h6 mb-1">Utilizatori</h3>
                        <div class="small text-muted">Administrare conturi și roluri</div>
                    </a>
                </div>
            @endcan
        </div>
    </section>

    <section class="home-charts">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
            <h2 class="h5 mb-0">Analize statistice - Acasă</h2>
            <span class="text-muted small">10 grafice pe date reale</span>
        </div>
        <div class="row g-3">
            @foreach ($home_charts as $chart)
                <div class="col-xxl-4 col-lg-6">
                    <div class="card stats-chart-card h-100">
                        <div class="card-body">
                            <h3 class="h6 mb-1">{{ $chart['title'] }}</h3>
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
</div>
@endsection

@push('scripts')
    @include('partials.charts-init')
@endpush
