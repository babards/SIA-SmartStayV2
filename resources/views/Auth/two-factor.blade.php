@extends('layouts.app')

@section('content')
<div class="card shadow-sm" style="width: 100%; max-width: 400px;">
    <div class="card-body p-4">
        <h4 class="text-center mb-4">{{ __('Two Factor Authentication') }}</h4>

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

        <form id="verifyForm" method="POST" action="{{ route('2fa.verify') }}">
            @csrf

            <div class="mb-3">
                <label for="two_factor_code" class="form-label">{{ __('2FA Code') }}</label>
                <input id="two_factor_code" type="text" 
                    class="form-control @error('two_factor_code') is-invalid @enderror" 
                    name="two_factor_code" 
                    required 
                    autocomplete="off" 
                    autofocus>
                <div id="codeError" class="invalid-feedback"></div>
            </div>
            <div id="timerText" class="mb-2 text-center text-muted"></div>

            <div class="d-grid gap-2 mb-3">
                <button type="submit" id="verifyBtn" class="btn btn-primary">
                    {{ __('Verify Code') }}
                </button>
                <button type="button" id="resendBtn" class="btn btn-secondary d-none" onclick="resendCode()">
                    <i class="fas fa-sync me-2"></i>Resend Code
                </button>
                <a href="{{ route('login') }}" class="btn btn-link text-center">
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

    // Handle expired code (server-side session check)
    @if(session('expired'))
        handleCodeExpired();
    @endif
</script>
@endsection 