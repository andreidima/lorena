@extends('layouts.app')

@section('content')
<div class="mx-3 px-3 card" style="border-radius: 40px;">
    <div class="row card-header align-items-center" style="border-radius: 40px 40px 0 0;">
        <div class="col-lg-4">
            <span class="badge culoare1 fs-5">
                <i class="fa-solid fa-boxes-stacked"></i> Stocuri partener API
            </span>
        </div>
        <div class="col-lg-8">
            <form method="GET" action="{{ route('tech.partner-stock.index') }}">
                <div class="row mb-1 custom-search-form justify-content-end g-2">
                    <div class="col-lg-5">
                        <input type="text" class="form-control rounded-3" id="q" name="q" placeholder="Caută după cod sau denumire" value="{{ $search }}">
                    </div>
                    <div class="col-lg-3">
                        <select class="form-select rounded-3" id="per_page" name="per_page">
                            @foreach ([25, 50, 100, 200] as $size)
                                <option value="{{ $size }}" @selected($size === $perPage)>{{ $size }} / pagină</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 d-grid">
                        <button class="btn btn-sm btn-primary text-white border border-dark rounded-3" type="submit">
                            <i class="fas fa-search text-white me-1"></i> Caută
                        </button>
                    </div>
                    <div class="col-lg-2 d-grid">
                        <a class="btn btn-sm btn-secondary text-white border border-dark rounded-3" href="{{ route('tech.partner-stock.index', ['refresh' => 1, 'per_page' => $perPage]) }}" role="button">
                            <i class="fa-solid fa-rotate-right text-white me-1"></i> Refresh API
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card-body px-0 py-3">
        @include('errors.errors')

        @if ($fetchError)
            <div class="alert alert-danger mx-3 mb-3">
                {{ $fetchError }}
            </div>
        @else
            <div class="d-flex justify-content-between align-items-center px-3 mb-2">
                <span class="small text-muted">Total rânduri afișabile: {{ number_format($totalRows, 0, ',', '.') }}</span>
                <span class="small text-muted">Pagina {{ $rows->currentPage() }} din {{ $rows->lastPage() }}</span>
            </div>

            <div class="table-responsive rounded">
                <table class="table table-striped table-hover rounded" aria-label="Tabela stocuri partener">
                    <thead class="text-white rounded">
                        <tr>
                            @forelse ($columns as $column)
                                <th scope="col" class="text-white culoare2 text-nowrap">{{ $column }}</th>
                            @empty
                                <th scope="col" class="text-white culoare2">Date</th>
                            @endforelse
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                @foreach ($columns as $column)
                                    @php
                                        $value = $row[$column] ?? '';
                                        $display = is_string($value) ? trim($value) : $value;
                                    @endphp
                                    <td class="text-nowrap">{{ $display }}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ max(count($columns), 1) }}" class="text-center text-muted py-5">
                                    Nu există rezultate pentru filtrele alese.
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
        @endif
    </div>
</div>
@endsection
