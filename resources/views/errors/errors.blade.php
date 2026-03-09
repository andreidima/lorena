@php
    $successMessage = session('success') ?? session('status') ?? session('succes');
    $errorMessage = session('error') ?? session('eroare');
    $warningMessage = session('warning') ?? session('atentionare');
@endphp

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Verifică datele introduse:</strong>
        <ul class="mb-0 mt-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if ($successMessage)
    <div class="alert alert-success alert-dismissible fade show" role="status">
        {{ strip_tags((string) $successMessage) }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@elseif ($errorMessage)
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ strip_tags((string) $errorMessage) }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@elseif ($warningMessage)
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        {{ strip_tags((string) $warningMessage) }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
