@extends('layouts.app')

@section('content')
<div class="card shadow-sm" style="width: 100%; max-width: 400px;">
    <div class="card-body p-4">
        <h4 class="text-center mb-4">{{ __('Reset Password') }}</h4>

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

            <div class="mb-3">
                <label for="email" class="form-label">{{ __('Email Address') }}</label>
                <input id="email" type="email" 
                    class="form-control @error('email') is-invalid @enderror" 
                    name="email" 
                    value="{{ old('email') }}" 
                    required 
                    autocomplete="email" 
                    autofocus
                    placeholder="Enter your email">

                @error('email')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">{{ __('New Password') }}</label>
                <input id="password" type="password" 
                    class="form-control @error('password') is-invalid @enderror" 
                    name="password" 
                    required 
                    autocomplete="new-password"
                    placeholder="Enter new password">

                @error('password')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="mb-3">
                <label for="password-confirm" class="form-label">{{ __('Confirm Password') }}</label>
                <input id="password-confirm" type="password" 
                    class="form-control" 
                    name="password_confirmation" 
                    required 
                    autocomplete="new-password"
                    placeholder="Confirm new password">
            </div>

            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-primary">
                    {{ __('Reset Password') }}
                </button>
            </div>

            <div class="text-center">
                <p class="mb-0">Remember your password? 
                    <a href="{{ route('login') }}" class="text-primary">
                        Login here
                    </a>
                </p>
            </div>
        </form>
    </div>
</div>
@endsection