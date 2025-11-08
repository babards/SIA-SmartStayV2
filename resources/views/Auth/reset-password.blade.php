@extends('layouts.app')

@section('content')
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-5">
            <div class="card shadow-lg p-5" style="border-radius: 20px; border: none; background: linear-gradient(to bottom right, #ffffff, #f8f9fa);">
                <div class="text-center mb-4">
                    <img src="{{ asset('images/logo1.png') }}" alt="SmartStay Logo" class="mb-3" style="height: 180px; width: auto; object-fit: contain;">
                    <h2 class="text-center mb-2" style="color: #2596be; font-weight: 700; letter-spacing: -0.5px;">SmartStay</h2>
                    <p class="text-muted">Set your new password to regain access</p>
                </div>

                <h4 class="text-center mb-4 fw-bold" style="color: #2596be;">{{ __('Reset Password') }}</h4>

        @if(session('success'))
            <div class="alert alert-success" role="alert">
                {{ session('success') }}
            </div>
        @endif
        
        @if (session('error'))
            <div class="alert alert-danger" role="alert">
                {{ session('error') }}
            </div>
        @endif

        <form action="{{ route('password.update') }}" method="POST">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="mb-4">
                <label for="email" class="form-label text-muted mb-2">{{ __('Email Address') }}</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-envelope text-muted"></i></span>
                    <input id="email" type="email" 
                        class="form-control bg-light border-start-0 @error('email') is-invalid @enderror" 
                        name="email" 
                        value="{{ old('email') }}" 
                        required 
                        autocomplete="email" 
                        autofocus
                        placeholder="Enter your email address">

                    @error('email')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label text-muted mb-2">{{ __('New Password') }}</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-lock text-muted"></i></span>
                    <input id="password" type="password" 
                        class="form-control bg-light border-start-0 @error('password') is-invalid @enderror" 
                        name="password" 
                        required 
                        autocomplete="new-password"
                        placeholder="Choose your new password">

                    @error('password')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>

            <div class="mb-4">
                <label for="password-confirm" class="form-label text-muted mb-2">{{ __('Confirm Password') }}</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-lock text-muted"></i></span>
                    <input id="password-confirm" type="password" 
                        class="form-control bg-light border-start-0" 
                        name="password_confirmation" 
                        required 
                        autocomplete="new-password"
                        placeholder="Confirm your new password">
                </div>
            </div>

            <div class="d-grid mb-4">
                <button type="submit" class="btn btn-primary btn-lg fw-semibold" 
                    style="border-radius: 10px; padding: 12px; background: linear-gradient(135deg, #2596be 0%, #1e7898 100%); border: none; box-shadow: 0 4px 6px rgba(37, 150, 190, 0.2);">
                    <i class="fas fa-key me-2"></i>{{ __('Reset Password') }}
                </button>
            </div>

            <div class="text-center">
                <p class="mb-0 text-muted">Remember your password? 
                    <a href="{{ route('login') }}" class="fw-semibold text-decoration-none"
                       style="color: #2596be; transition: color 0.3s ease;">
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