@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Verifică adresa de email</div>

                <div class="card-body">
                    @if (session('resent'))
                        <div class="alert alert-success" role="alert">
                            A fost trimis un nou link de verificare către adresa ta de email.
                        </div>
                    @endif

                    Înainte să continui, verifică emailul pentru linkul de verificare.
                    Dacă nu ai primit emailul,
                    <form class="d-inline" method="POST" action="{{ route('verification.resend') }}">
                        @csrf
                        <button type="submit" class="btn btn-link p-0 m-0 align-baseline">apasă aici pentru a solicita altul</button>.
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
