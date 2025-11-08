@extends('layouts.app')

@section('content')

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-5">
                <div class="card shadow-lg p-5" style="border-radius: 20px; border: none; background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);">
                    <div class="text-center mb-4">
                    <img src="{{ asset('images/logo1.png') }}" alt="SmartStay Logo" class="mb-4" style="height: 180px; width: auto; object-fit: contain; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1));">
                    <h2 class="text-center mb-2" style="color: #2596be; font-weight: 800; letter-spacing: -0.5px; text-shadow: 2px 2px 4px rgba(0,0,0,0.1);">SmartStay</h2>
                    <p class="text-muted" style="font-size: 1.1rem;">Welcome back! Please login to your account.</p>
                    </div>

                    <h4 class="text-center fw-bold mb-4" style="color: #2596be; opacity: 0.9;">{{ __('Login') }}</h4>

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

                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="mb-4">
                            <label for="email" class="form-label fw-semibold mb-2" style="color: #2596be; opacity: 0.8;">{{ __('Email Address') }}</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text border-end-0" style="background: rgba(37, 150, 190, 0.03);"><i class="fas fa-envelope" style="color: #2596be; opacity: 0.7;"></i></span>
                                <input id="email" type="email" class="form-control border-start-0 @error('email') is-invalid @enderror"
                                    style="background: rgba(37, 150, 190, 0.03); color: #2596be; font-size: 1rem;"
                                    name="email" value="{{ old('email') }}" required autocomplete="email" autofocus
                                    placeholder="Enter your email">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label fw-semibold mb-2" style="color: #2596be; opacity: 0.8;">{{ __('Password') }}</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text border-end-0" style="background: rgba(37, 150, 190, 0.03);"><i class="fas fa-lock" style="color: #2596be; opacity: 0.7;"></i></span>
                                <input id="password" type="password"
                                    class="form-control border-start-0 @error('password') is-invalid @enderror" 
                                    style="background: rgba(37, 150, 190, 0.03); color: #2596be; font-size: 1rem;"
                                    name="password" required autocomplete="current-password"
                                    placeholder="Enter your password">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-grid mb-4">
                            <button type="submit" class="btn btn-lg fw-bold" 
                                style="border-radius: 12px; padding: 15px; background: linear-gradient(135deg, #2596be 0%, #1e7898 100%); border: none; box-shadow: 0 4px 15px rgba(37, 150, 190, 0.3); color: white; font-size: 1.1rem; transition: all 0.3s ease;">
                                <i class="fas fa-sign-in-alt me-2"></i>{{ __('Login') }}
                            </button>
                        </div>

                        <div class="text-center">
                            <p class="mb-3" style="color: #2596be; opacity: 0.7;">Don't have an account?
                                <a href="{{ route('register') }}" class="fw-bold text-decoration-none" 
                                   style="color: #1e7898; transition: all 0.3s ease;">Register here</a>
                            </p>
                            <p class="mb-0" style="color: #2596be; opacity: 0.7;">Forgot your password?
                                <a href="{{ route('password.request') }}" class="fw-bold text-decoration-none"
                                   style="color: #1e7898; transition: all 0.3s ease;">Reset Password</a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection