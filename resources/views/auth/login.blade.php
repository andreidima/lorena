@extends('layouts.app')

@section('content')
<div class="container login-page py-3">
    <div class="row justify-content-center w-100">
        <div class="col-xl-10 col-xxl-9">
            <div class="card login-shell">
                <div class="row g-0">
                    <div class="col-lg-5 text-white p-4 p-lg-5 login-info-panel d-flex flex-column justify-content-between">
                        <div>
                            <span class="login-badge mb-3">
                                <i class="fa-solid fa-shield-halved me-1"></i> Acces securizat
                            </span>
                            <h2 class="h3 mb-3">{{ config('app.name', 'Laravel') }}</h2>
                            <p class="mb-0">Conectează-te pentru a accesa modulele și panourile operaționale.</p>
                        </div>
                        <div class="mt-4">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fa-solid fa-layer-group me-2"></i>
                                <span>Module centralizate</span>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <i class="fa-solid fa-chart-line me-2"></i>
                                <span>Indicatori vizibili dintr-o privire</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <i class="fa-solid fa-user-lock me-2"></i>
                                <span>Control de acces pe roluri</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-7 p-4 p-lg-5 login-form-panel">
                        <h1 class="h4 mb-1">Bine ai revenit</h1>
                        <p class="text-muted mb-4">Autentifică-te pentru a continua.</p>

                        @include('errors.errors')

                        <form method="POST" action="{{ route('login') }}">
                            @csrf

                            <div class="mb-3">
                                <label for="email" class="form-label">{{ __('auth.E-Mail Address') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <input id="email" type="text" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" autocomplete="email" autofocus placeholder="{{ __('auth.E-Mail Address') }}">
                                </div>
                                @error('email')
                                    <span class="text-danger small" role="alert">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">{{ __('auth.Password') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" autocomplete="current-password" placeholder="{{ __('auth.Password') }}">
                                </div>
                                @error('password')
                                    <span class="text-danger small" role="alert">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="remember">
                                        {{ __('auth.Remember Me') }}
                                    </label>
                                </div>
                            </div>

                            <button type="submit" class="btn login-submit-btn text-white w-100 py-2 mb-3">
                                {{ __('auth.Login') }}
                            </button>

                            @if (Route::has('password.request'))
                                <div class="text-center small mb-2">
                                    <a class="btn btn-link p-0 border-0" href="{{ route('password.request') }}">
                                        {{ __('auth.Forgot Your Password?') }}
                                    </a>
                                </div>
                            @endif

                            @if (Route::has('register'))
                                <div class="text-center small">
                                    Nu ai cont?
                                    <a href="{{ route('register') }}">Înregistrează-te</a>
                                </div>
                            @endif
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
