@extends('layouts.app')

@section('content')
<style>
    .verification-input {
        letter-spacing: 3px;
        font-size: 1.2rem;
        text-align: center;
        font-weight: 600;
    }
    
    .timer-text {
        font-size: 0.9rem;
        color: #6c757d;
        font-weight: 500;
    }
    
    .btn-verify {
        transition: transform 0.2s;
    }
    
    .btn-verify:hover {
        transform: translateY(-1px);
    }
    
    .alert {
        border: none !important;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
</style>
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-5">
            <div class="card shadow-lg p-5" style="border-radius: 20px; border: none; background: linear-gradient(to bottom right, #ffffff, #f8f9fa);">
                <div class="text-center mb-4">
                    <img src="{{ asset('images/logo1.png') }}" alt="SmartStay Logo" class="mb-3" style="height: 180px; width: auto; object-fit: contain;">
                    <h2 class="text-center mb-2" style="color: #2596be; font-weight: 700; letter-spacing: -0.5px;">SmartStay</h2>
                    <p class="text-muted">Security verification required</p>
                </div>

                <h4 class="text-center mb-2" style="color: #2596be;">{{ __('Two Factor Authentication') }}</h4>
                <p class="text-center text-muted mb-4">Enter the verification code sent to your email</p>

                @if (session('message'))
                    <div class="alert alert-info alert-dismissible fade show" role="alert"
                         style="border-radius: 10px; border: none; background-color: #cff4fc; color: #055160;">
                        <i class="fas fa-info-circle me-2"></i>
                        {{ session('message') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert"
                         style="border-radius: 10px; border: none; background-color: #f8d7da; color: #842029;">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

        <form id="verifyForm" method="POST" action="{{ route('2fa.verify') }}">
            @csrf

            <div class="mb-4">
                <label for="two_factor_code" class="form-label text-muted mb-2">{{ __('2FA Code') }}</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-key text-muted"></i></span>
                    <input id="two_factor_code" type="text" 
                        class="form-control bg-light border-start-0 @error('two_factor_code') is-invalid @enderror" 
                        name="two_factor_code" 
                        required 
                        autocomplete="off" 
                        autofocus
                        placeholder="Enter 6-digit code"
                        style="letter-spacing: 3px; font-size: 1.2rem;">
                    <div id="codeError" class="invalid-feedback"></div>
                </div>
            </div>
            <div id="timerText" class="mb-3 text-center text-muted" style="font-size: 0.9rem;"></div>

            <div class="d-grid gap-3 mb-3">
                <button type="submit" id="verifyBtn" class="btn btn-primary btn-lg fw-semibold"
                    style="border-radius: 10px; padding: 12px; background: linear-gradient(135deg, #2596be 0%, #1e7898 100%); border: none; box-shadow: 0 4px 6px rgba(37, 150, 190, 0.2);">
                    <i class="fas fa-shield-check me-2"></i>{{ __('Verify Code') }}
                </button>
                <button type="button" id="resendBtn" class="btn btn-outline-secondary btn-lg fw-semibold d-none" 
                    onclick="resendCode()"
                    style="border-radius: 10px; padding: 12px;">
                    <i class="fas fa-sync me-2"></i>Resend Code
                </button>
                <a href="{{ route('login') }}" class="btn btn-link text-center text-decoration-none text-muted"
                   style="transition: color 0.3s ease;">
                    <i class="fas fa-arrow-left me-2"></i>Back to Login
                </a>
            </div>
        </form>
    </div>
</div>

<script>
    const twoFaUserId = "{{ session('2fa_user_id') }}";
    let expiryTime = new Date();
    let timerInterval;

    // Function to start the countdown timer
    function startTimer(initialExpiryTime = null) {
        if (initialExpiryTime) {
            expiryTime = new Date(initialExpiryTime);
        } else {
            expiryTime.setTime(new Date().getTime() + (2 * 60 * 1000)); // 2 minutes
        }

        if (timerInterval) clearInterval(timerInterval);

        timerInterval = setInterval(() => {
            const now = new Date().getTime();
            const distance = expiryTime - now;

            if (distance >= 0) {
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                document.getElementById('timerText').innerText = 
                    `Code expires in: ${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
            } else {
                clearInterval(timerInterval);
                handleCodeExpired();
            }
        }, 1000);
    }

    // Handle expired code
    function handleCodeExpired() {
        const codeInput = document.getElementById('two_factor_code');
        const resendBtn = document.getElementById('resendBtn');
        const verifyBtn = document.getElementById('verifyBtn');
        
        resendBtn.classList.remove('d-none');
        verifyBtn.disabled = true;
        codeInput.disabled = true;

        Swal.fire({
            icon: 'warning',
            title: 'Code Expired',
            text: 'The verification code has expired. Please request a new code.',
            confirmButtonColor: '#3085d6'
        });
    }

    // Handle form submission
    document.getElementById('verifyForm').addEventListener('submit', function(event) {
        event.preventDefault();

        const verifyBtn = document.getElementById('verifyBtn');
        const codeInput = document.getElementById('two_factor_code');
        const codeError = document.getElementById('codeError');
        
        verifyBtn.disabled = true;
        verifyBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Verifying...';

        codeInput.classList.remove('is-invalid');
        codeError.textContent = '';

        const formData = new FormData(this);

        fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Authentication successful!',
                    showConfirmButton: false,
                    timer: 2500,
                }).then(() => {
                    window.location.href = data.redirect_url;
                });
            } else {
                codeInput.classList.add('is-invalid');
                codeError.textContent = data.message || 'The code you entered is incorrect.';
                verifyBtn.disabled = false;
                verifyBtn.innerHTML = 'Verify Code';
            }
        })
        .catch(error => {
            codeInput.classList.add('is-invalid');
            codeError.textContent = 'An error occurred. Please try again.';
            verifyBtn.disabled = false;
            verifyBtn.innerHTML = 'Verify Code';
        });
    });

    // Resend OTP Code
    function resendCode() {
        const resendBtn = document.getElementById('resendBtn');
        const codeInput = document.getElementById('two_factor_code');
        const codeError = document.getElementById('codeError');
        
        codeInput.classList.remove('is-invalid');
        codeError.textContent = '';
        
        resendBtn.disabled = true;
        resendBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sending...';

        fetch("{{ route('2fa.resend') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ user_id: twoFaUserId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Code Sent!',
                    text: data.message,
                    showConfirmButton: false,
                    timer: 3000
                }).then(() => {
                    codeInput.value = '';
                    codeInput.disabled = false;
                    document.getElementById('verifyBtn').disabled = false;
                    resendBtn.classList.add('d-none');
                    startTimer(); // Restart the timer for the new code
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Failed to resend the code. Please try again.',
                    confirmButtonColor: '#3085d6'
                });
            }
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Unexpected Error',
                text: 'An unexpected error occurred. Please try again later.',
                confirmButtonColor: '#3085d6'
            });
        })
        .finally(() => {
            resendBtn.disabled = false;
            resendBtn.innerHTML = '<i class="fas fa-sync me-2"></i>Resend Code';
        });
    }

    // Start the initial timer
    startTimer();
</script>
@endsection 