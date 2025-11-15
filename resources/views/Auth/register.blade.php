@extends('layouts.app')

@section('content')
<div class="container my-5">
  <div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-5">
      <div class="card shadow-lg p-5" style="border-radius: 20px; border: none; background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);">

        <!-- Logo -->
        <div class="text-center mb-4">
          <img src="{{ asset('images/logo1.png') }}" alt="SmartStay Logo" class="mb-4" style="height: 180px; width: auto; object-fit: contain; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1));">
          <h2 class="text-center mb-2" style="color: #2596be; font-weight: 800; letter-spacing: -0.5px; text-shadow: 2px 2px 4px rgba(0,0,0,0.1);">SmartStay</h2>
          <p class="text-muted" style="font-size: 1.1rem;">Create your account to get started</p>
        </div>

        <h4 class="text-center fw-bold mb-4" style="color: #2596be; opacity: 0.9;">{{ __('Register') }}</h4>

        @if (session('message'))
          <div class="alert alert-info alert-dismissible fade show" role="alert"
               style="border-radius: 10px; border: none; background: rgba(37, 150, 190, 0.1); color: #2596be;">
            <i class="fas fa-info-circle me-2"></i>{{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        @endif

        @if (session('error'))
          <div class="alert alert-danger alert-dismissible fade show" role="alert"
               style="border-radius: 10px; border: none; background: rgba(220, 53, 69, 0.1); color: #dc3545;">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        @endif

        <form method="POST" action="{{ route('register') }}">
          @csrf

          <!-- form inputs same as before -->

          <div class="row mb-3">
            <div class="col-md-6 mb-3">
              <label for="first_name" class="form-label fw-semibold mb-2" style="color: #2596be; opacity: 0.8;">{{ __('First Name') }}</label>
              <div class="input-group input-group-lg">
                <span class="input-group-text border-end-0" style="background: rgba(37, 150, 190, 0.03);"><i class="fas fa-user" style="color: #2596be; opacity: 0.7;"></i></span>
                <input id="first_name" type="text" class="form-control border-start-0 @error('first_name') is-invalid @enderror" 
                  style="background: rgba(37, 150, 190, 0.03); color: #2596be; font-size: 1rem;"
                  name="first_name" value="{{ old('first_name') }}" required autocomplete="given-name" autofocus placeholder="Enter your first name">
                @error('first_name')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <label for="last_name" class="form-label fw-semibold mb-2" style="color: #2596be; opacity: 0.8;">{{ __('Last Name') }}</label>
              <div class="input-group input-group-lg">
                <span class="input-group-text border-end-0" style="background: rgba(37, 150, 190, 0.03);"><i class="fas fa-user" style="color: #2596be; opacity: 0.7;"></i></span>
                <input id="last_name" type="text" class="form-control border-start-0 @error('last_name') is-invalid @enderror" 
                  style="background: rgba(37, 150, 190, 0.03); color: #2596be; font-size: 1rem;"
                  name="last_name" value="{{ old('last_name') }}" required autocomplete="family-name" placeholder="Enter your last name">
                @error('last_name')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
              </div>
            </div>
          </div>

          <div class="mb-3">
              <label for="email" class="form-label fw-semibold mb-2" style="color: #2596be; opacity: 0.8;">{{ __('Email') }}</label>
              <div class="input-group input-group-lg">
                <span class="input-group-text border-end-0" style="background: rgba(37, 150, 190, 0.03);"><i class="fas fa-envelope" style="color: #2596be; opacity: 0.7;"></i></span>
                <input id="email" type="email" class="form-control border-start-0 @error('email') is-invalid @enderror" 
                  style="background: rgba(37, 150, 190, 0.03); color: #2596be; font-size: 1rem;"
                name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="Enter your email address">
              @error('email')
              <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
              @enderror
            </div>
          </div>

          <div class="mb-3">
              <label for="phone_number" class="form-label fw-semibold mb-2" style="color: #2596be; opacity: 0.8;">{{ __('Phone Number') }}</label>
              <div class="input-group input-group-lg">
                <span class="input-group-text border-end-0" style="background: rgba(37, 150, 190, 0.03);"><i class="fas fa-phone" style="color: #2596be; opacity: 0.7;"></i></span>
                <input id="phone_number" type="tel" class="form-control border-start-0 @error('phone_number') is-invalid @enderror" 
                  style="background: rgba(37, 150, 190, 0.03); color: #2596be; font-size: 1rem;"
                name="phone_number" value="{{ old('phone_number') }}" required autocomplete="tel" placeholder="Enter your phone number">
              @error('phone_number')
              <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
              @enderror
            </div>
          </div>

          <div class="mb-3">
              <label for="password" class="form-label fw-semibold mb-2" style="color: #2596be; opacity: 0.8;">{{ __('Password') }}</label>
              <div class="input-group input-group-lg">
                <span class="input-group-text border-end-0" style="background: rgba(37, 150, 190, 0.03);"><i class="fas fa-lock" style="color: #2596be; opacity: 0.7;"></i></span>
                <input id="password" type="password" class="form-control border-start-0 @error('password') is-invalid @enderror" 
                  style="background: rgba(37, 150, 190, 0.03); color: #2596be; font-size: 1rem;"
                name="password" required autocomplete="new-password" placeholder="Choose a password">
              @error('password')
              <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
              @enderror
            </div>
          </div>

          <div class="mb-3">
              <label for="password-confirm" class="form-label fw-semibold mb-2" style="color: #2596be; opacity: 0.8;">{{ __('Confirm Password') }}</label>
              <div class="input-group input-group-lg">
                <span class="input-group-text border-end-0" style="background: rgba(37, 150, 190, 0.03);"><i class="fas fa-lock" style="color: #2596be; opacity: 0.7;"></i></span>
                <input id="password-confirm" type="password" class="form-control border-start-0" 
                  style="background: rgba(37, 150, 190, 0.03); color: #2596be; font-size: 1rem;"
                name="password_confirmation" required autocomplete="new-password" placeholder="Confirm your password">
            </div>
          </div>

          <div class="mb-4">
            <label for="role" class="form-label fw-semibold mb-2" style="color: #2596be; opacity: 0.8;">{{ __('Register as') }}</label>
            <div class="input-group input-group-lg">
              <span class="input-group-text border-end-0" style="background: rgba(37, 150, 190, 0.03);"><i class="fas fa-user-tag" style="color: #2596be; opacity: 0.7;"></i></span>
              <select id="role" name="role" class="form-select border-start-0 @error('role') is-invalid @enderror" 
                style="background: rgba(37, 150, 190, 0.03); color: #2596be; font-size: 1rem;" required>
                <option value="" disabled selected>Select your role</option>
                <option value="tenant" {{ old('role') == 'tenant' ? 'selected' : '' }}>Tenant</option>
                <option value="landlord" {{ old('role') == 'landlord' ? 'selected' : '' }}>Landlord</option>
              </select>
              @error('role')
              <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
              @enderror
            </div>
          </div>

          <div class="d-grid mb-4">
            <button type="submit" class="btn btn-lg fw-bold" 
              style="border-radius: 12px; padding: 15px; background: linear-gradient(135deg, #2596be 0%, #1e7898 100%); border: none; box-shadow: 0 4px 15px rgba(37, 150, 190, 0.3); color: white; font-size: 1.1rem; transition: all 0.3s ease;">
              <i class="fas fa-user-plus me-2"></i>{{ __('Create Account') }}
            </button>
          </div>

          <div class="text-center">
            <p class="mb-0" style="color: #2596be; opacity: 0.7;">Already have an account? 
              <a href="{{ route('login') }}" class="fw-bold text-decoration-none"
                 style="color: #1e7898; transition: all 0.3s ease;">
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
