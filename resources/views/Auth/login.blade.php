@extends('layouts.app')

@section('content')

    <div class="container my-">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-5">
                <div class="card shadow-sm p-4" style="border-radius: 12px;">
                    <div class="text-center sm mb-4">
                        <h2 class="text-center mb-4">SmartStay</h2>
                    </div>

                    <h4 class="text-center fw-bold mb-4">{{ __('Login') }}</h4>

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

                        <div class="mb-3">
                            <label for="email" class="form-label">{{ __('Email Address') }}</label>
                            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror"
                                name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">{{ __('Password') }}</label>
                            <input id="password" type="password"
                                class="form-control @error('password') is-invalid @enderror" name="password" required
                                autocomplete="current-password">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary fw-semibold">
                                {{ __('Login') }}
                            </button>
                        </div>

                        <div class="text-center">
                            <p class="mb-1">Don’t have an account?
                                <a href="{{ route('register') }}" class="text-decoration-none">Register here</a>
                            </p>
                            <p class="mb-0">Forgot your password?
                                <a href="{{ route('password.request') }}" class="text-decoration-none">Reset Password</a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection