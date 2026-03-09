<div class="row mb-4 pt-2 rounded-3" style="border:1px solid #e9ecef; border-left:0.25rem darkcyan solid; background-color:rgb(241, 250, 250)">
    <div class="col-12">
        <p class="small text-muted mb-2 ps-2">Câmpurile marcate cu <span class="text-danger">*</span> sunt obligatorii.</p>
    </div>

    <div class="col-lg-6 mb-4">
        <label for="name" class="mb-0 ps-3">Nume<span class="text-danger">*</span></label>
        <input
            type="text"
            class="form-control bg-white rounded-3 {{ $errors->has('name') ? 'is-invalid' : '' }}"
            name="name"
            id="name"
            value="{{ old('name', $user->name ?? '') }}"
            autocomplete="name"
            required
        >
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-lg-6 mb-4">
        <label for="telefon" class="mb-0 ps-3">Telefon</label>
        <input
            type="text"
            class="form-control bg-white rounded-3 {{ $errors->has('telefon') ? 'is-invalid' : '' }}"
            name="telefon"
            id="telefon"
            value="{{ old('telefon', $user->telefon ?? '') }}"
        >
        @error('telefon')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-lg-6 mb-4">
        <label for="email" class="mb-0 ps-3">Email<span class="text-danger">*</span></label>
        <input
            type="email"
            class="form-control bg-white rounded-3 {{ $errors->has('email') ? 'is-invalid' : '' }}"
            name="email"
            id="email"
            autocomplete="email"
            value="{{ old('email', $user->email ?? '') }}"
            required
        >
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-lg-3 mb-4">
        <label for="role" class="mb-0 ps-3">Rol<span class="text-danger">*</span></label>
        <select class="form-select bg-white rounded-3 {{ $errors->has('role') ? 'is-invalid' : '' }}" name="role" id="role" required>
            <option value="">Selectează rol</option>
            <option value="Admin" {{ old('role', $user->role ?? '') == 'Admin' ? 'selected' : '' }}>Admin</option>
            <option value="Operator" {{ old('role', $user->role ?? '') == 'Operator' ? 'selected' : '' }}>Operator</option>
        </select>
        @error('role')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-lg-3 mb-4 text-center">
        <fieldset class="mb-4">
            <legend class="mb-0 fs-6">Cont activ<span class="text-danger">*</span></legend>
            <div class="d-flex py-1 justify-content-center">
                <div class="form-check me-4">
                    <input class="form-check-input" type="radio" value="1" name="activ" id="activ_da" {{ old('activ', $user->activ ?? '') == '1' ? 'checked' : '' }} required>
                    <label class="form-check-label" for="activ_da">DA</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" value="0" name="activ" id="activ_nu" {{ old('activ', $user->activ ?? '') == '0' ? 'checked' : '' }}>
                    <label class="form-check-label" for="activ_nu">NU</label>
                </div>
            </div>
            @error('activ')
                <div class="text-danger small">{{ $message }}</div>
            @enderror
        </fieldset>
    </div>

    <div class="col-lg-6 mb-4">
        <label for="password" class="mb-0 ps-3">
            Parola {!! isset($user) ? '' : '<span class="text-danger">*</span>' !!}
            <small class="text-muted">{{ isset($user) ? '(Completează doar pentru modificare)' : '' }}</small>
        </label>
        <input
            id="password"
            type="password"
            class="form-control rounded-3 {{ $errors->has('password') ? 'is-invalid' : '' }}"
            name="password"
            autocomplete="new-password"
            {{ !isset($user) ? 'required' : '' }}
        >
        <div class="form-text ps-3">Minim 8 caractere</div>
        @error('password')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-lg-6 mb-4">
        <label for="password_confirmation" class="mb-0 ps-3">
            Confirmare parola {!! isset($user) ? '' : '<span class="text-danger">*</span>' !!}
        </label>
        <input
            id="password_confirmation"
            type="password"
            class="form-control rounded-3 {{ $errors->has('password_confirmation') ? 'is-invalid' : '' }}"
            name="password_confirmation"
            autocomplete="new-password"
            {{ !isset($user) ? 'required' : '' }}
        >
        @error('password_confirmation')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="row">
    <div class="col-lg-12 mb-2 d-flex justify-content-center">
        <button type="submit" class="btn btn-primary text-white me-3 rounded-3">
            <i class="fa-solid fa-save me-1"></i> {{ $buttonText }}
        </button>
        <a class="btn btn-secondary rounded-3" href="{{ Session::get('returnUrl', route('users.index')) }}">
            Renunță
        </a>
    </div>
</div>
