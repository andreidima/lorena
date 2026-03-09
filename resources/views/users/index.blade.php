@extends('layouts.app')

@section('content')
<div class="mx-3 px-3 card" style="border-radius: 40px;">
    <div class="row card-header align-items-center g-3" style="border-radius: 40px 40px 0 0;">
        <div class="col-lg-3">
            <span class="badge culoare1 fs-5">
                <i class="fa-solid fa-users"></i> Utilizatori
            </span>
        </div>

        <div class="col-lg-6">
            <form method="GET" action="{{ route('users.index') }}">
                <div class="row mb-1 custom-search-form justify-content-center g-2">
                    <div class="col-lg-6">
                        <input type="text" class="form-control rounded-3" id="searchNume" name="searchNume" placeholder="Nume" value="{{ $searchNume }}">
                    </div>
                    <div class="col-lg-6">
                        <input type="text" class="form-control rounded-3" id="searchTelefon" name="searchTelefon" placeholder="Telefon" value="{{ $searchTelefon }}">
                    </div>
                </div>
                <div class="row custom-search-form justify-content-center g-2">
                    <div class="col-lg-4">
                        <button class="btn btn-sm w-100 btn-primary text-white border border-dark rounded-3" type="submit">
                            <i class="fas fa-search text-white me-1"></i> Caută
                        </button>
                    </div>
                    <div class="col-lg-4">
                        <a class="btn btn-sm w-100 btn-secondary text-white border border-dark rounded-3" href="{{ route('users.index') }}" role="button">
                            <i class="far fa-trash-alt text-white me-1"></i> Resetează căutarea
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-lg-3 text-end">
            <a class="btn btn-sm btn-success text-white border border-dark rounded-3" href="{{ route('users.create') }}" role="button">
                <i class="fas fa-user-plus text-white me-1"></i> Adaugă utilizator
            </a>
        </div>
    </div>

    <div class="card-body px-0 py-3">
        @include('errors.errors')

        <div class="px-3 pb-2 small text-muted">
            Total utilizatori găsiți: {{ number_format($users->total(), 0, ',', '.') }}
        </div>

        <div class="table-responsive rounded">
            <table class="table table-striped table-hover rounded" aria-label="Users table">
                <thead class="text-white rounded">
                    <tr>
                        <th scope="col" class="text-white culoare2" width="5%"><i class="fa-solid fa-hashtag"></i></th>
                        <th scope="col" class="text-white culoare2" width="25%"><i class="fa-solid fa-user me-1"></i> Nume</th>
                        <th scope="col" class="text-white culoare2" width="15%"><i class="fa-solid fa-phone me-1"></i> Telefon</th>
                        <th scope="col" class="text-white culoare2" width="25%"><i class="fa-solid fa-envelope me-1"></i> Email</th>
                        <th scope="col" class="text-white culoare2" width="10%"><i class="fa-solid fa-user-tag me-1"></i> Rol</th>
                        <th scope="col" class="text-white culoare2" width="10%"><i class="fa-solid fa-toggle-on me-1"></i> Stare cont</th>
                        <th scope="col" class="text-white culoare2 text-end" width="10%"><i class="fa-solid fa-cogs me-1"></i> Acțiuni</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>{{ ($users->currentPage() - 1) * $users->perPage() + $loop->index + 1 }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->telefon }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->role }}</td>
                            <td>
                                @if ($user->activ == 0)
                                    <span class="text-danger">Închis</span>
                                @else
                                    <span class="text-success">Deschis</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex flex-wrap justify-content-end gap-1">
                                    <a href="{{ route('users.show', $user) }}" class="btn btn-sm btn-outline-success" title="Vizualizează {{ $user->name }}" aria-label="Vizualizează {{ $user->name }}">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-primary" title="Modifică {{ $user->name }}" aria-label="Modifică {{ $user->name }}">
                                        <i class="fa-solid fa-edit"></i>
                                    </a>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deleteUserModal"
                                        data-delete-url="{{ route('users.destroy', $user) }}"
                                        data-user-name="{{ $user->name }}"
                                        title="Șterge {{ $user->name }}"
                                        aria-label="Șterge {{ $user->name }}"
                                    >
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="fa-solid fa-users-slash fa-2x mb-3 d-block"></i>
                                <p class="mb-0">Nu s-au găsit utilizatori în baza de date.</p>
                                @if($searchNume || $searchTelefon)
                                    <p class="small mb-0 mt-2">Încearcă să modifici criteriile de căutare.</p>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <nav>
            <ul class="pagination justify-content-center">
                {{ $users->appends(Request::except('page'))->links() }}
            </ul>
        </nav>
    </div>
</div>

<div class="modal fade text-dark" id="deleteUserModal" tabindex="-1" role="dialog" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteUserModalLabel">
                    <i class="fa-solid fa-user-minus me-1"></i> Șterge utilizator
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-start">
                Ești sigur că vrei să ștergi utilizatorul <strong id="deleteUserName"></strong>?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Renunță</button>
                <form method="POST" id="deleteUserForm" action="">
                    @method('DELETE')
                    @csrf
                    <button type="submit" class="btn btn-danger text-white">
                        <i class="fa-solid fa-trash me-1"></i> Șterge utilizator
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var deleteModal = document.getElementById('deleteUserModal');

    if (!deleteModal) {
        return;
    }

    deleteModal.addEventListener('show.bs.modal', function (event) {
        var trigger = event.relatedTarget;

        if (!trigger) {
            return;
        }

        var action = trigger.getAttribute('data-delete-url') || '';
        var name = trigger.getAttribute('data-user-name') || '';

        var form = deleteModal.querySelector('#deleteUserForm');
        var userNameLabel = deleteModal.querySelector('#deleteUserName');

        if (form) {
            form.setAttribute('action', action);
        }

        if (userNameLabel) {
            userNameLabel.textContent = name;
        }
    });
});
</script>
@endsection
