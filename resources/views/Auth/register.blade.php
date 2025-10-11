@extends('layouts.app')

@section('content')
<div class="container my-5">
  <div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-5">
      <div class="card shadow-sm p-4" style="border-radius: 12px;">

        <!-- Logo -->
        <div class="text-center mb-4">
          <h2 class="text-center mb-4">SmartStay</h2>
        </div>

        <h4 class="text-center mb-4 fw-bold">{{ __('Register') }}</h4>

        @if (session('message'))
          <div class="alert alert-info" role="alert">
            {{ session('message') }}
          </div>
        @endif

        @if (session('error'))
          <div class="alert alert-danger" role="alert">
            {{ session('error') }}
          </div>
        @endif

        <form method="POST" action="{{ route('register') }}">
          @csrf

          <!-- form inputs same as before -->

          <div class="mb-3">
            <label for="first_name" class="form-label">{{ __('First Name') }}</label>
            <input id="first_name" type="text" class="form-control @error('first_name') is-invalid @enderror" name="first_name" value="{{ old('first_name') }}" required autocomplete="given-name" autofocus>
            @error('first_name')
            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
          </div>

          <div class="mb-3">
            <label for="last_name" class="form-label">{{ __('Last Name') }}</label>
            <input id="last_name" type="text" class="form-control @error('last_name') is-invalid @enderror" name="last_name" value="{{ old('last_name') }}" required autocomplete="family-name">
            @error('last_name')
            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
          </div>

          <div class="mb-3">
            <label for="email" class="form-label">{{ __('Email Address') }}</label>
            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email">
            @error('email')
            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
          </div>

          <div class="mb-3">
            <label for="password" class="form-label">{{ __('Password') }}</label>
            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">
            @error('password')
            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
          </div>

          <div class="mb-3">
            <label for="password-confirm" class="form-label">{{ __('Confirm Password') }}</label>
            <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">
          </div>

          <div class="mb-4">
            <label for="role" class="form-label">{{ __('Register as') }}</label>
            <select id="role" name="role" class="form-select @error('role') is-invalid @enderror" required>
              <option value="tenant" {{ old('role') == 'tenant' ? 'selected' : '' }}>Tenant</option>
              <option value="landlord" {{ old('role') == 'landlord' ? 'selected' : '' }}>Landlord</option>
            </select>
            @error('role')
            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
          </div>

          <div class="d-grid mb-3">
            <button type="submit" class="btn btn-primary fw-semibold">
              {{ __('Register') }}
            </button>
          </div>

          <div class="text-center">
            <p class="mb-0">Already have an account? 
              <a href="{{ route('login') }}" class="text-primary fw-semibold">
                Login here
              </a>
            </p>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
