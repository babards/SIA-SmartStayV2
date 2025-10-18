@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-user-edit me-2"></i>Edit Profile
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <!-- Avatar Section -->
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <label class="form-label">Profile Picture</label>
                                <div class="d-flex align-items-center">
                                    <div class="me-3" id="avatar-preview-container">
                                        @if($user->avatar_url)
                                            <img src="{{ $user->avatar_url }}" 
                                                 alt="Current Avatar" 
                                                 class="rounded-circle profile-avatar" 
                                                 style="width: 100px; height: 100px; object-fit: cover;">
                                        @else
                                            <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" 
                                                 style="width: 100px; height: 100px;">
                                                <i class="fas fa-user fa-3x text-white"></i>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex-grow-1">
                                        <input type="file" 
                                               class="form-control @error('avatar') is-invalid @enderror" 
                                               id="avatar" 
                                               name="avatar" 
                                               accept="image/*">
                                        <div class="form-text">Upload a new profile picture (JPEG, PNG, JPG, GIF - Max: 2MB)</div>
                                        @error('avatar')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Personal Information -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" 
                                       class="form-control @error('first_name') is-invalid @enderror" 
                                       id="first_name" 
                                       name="first_name" 
                                       value="{{ old('first_name', $user->first_name) }}" 
                                       required>
                                @error('first_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" 
                                       class="form-control @error('last_name') is-invalid @enderror" 
                                       id="last_name" 
                                       name="last_name" 
                                       value="{{ old('last_name', $user->last_name) }}" 
                                       required>
                                @error('last_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email', $user->email) }}" 
                                   required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Password Change Section -->
                        <hr class="my-4">
                        <div class="password-section">
                            <h6 class="text-muted mb-3">
                                <i class="fas fa-lock me-2"></i>Change Password (Optional)
                                <small class="text-info d-block mt-1">Leave all password fields blank to keep your current password</small>
                            </h6>
                        
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" 
                                   class="form-control @error('current_password') is-invalid @enderror" 
                                   id="current_password" 
                                   name="current_password">
                            <div class="form-text">Required only if you want to change your password</div>
                            @error('current_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="password" class="form-label">New Password</label>
                                <input type="password" 
                                       class="form-control @error('password') is-invalid @enderror" 
                                       id="password" 
                                       name="password">
                                <div class="form-text">Leave blank to keep current password</div>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="password_confirmation" class="form-label">Confirm New Password</label>
                                <input type="password" 
                                       class="form-control" 
                                       id="password_confirmation" 
                                       name="password_confirmation">
                            </div>
                        </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('dashboard') }}" class="btn btn-secondary" id="cancel-btn">
                                <i class="fas fa-arrow-left me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .avatar-preview {
        transition: all 0.3s ease;
        border: 3px solid #dee2e6;
        cursor: pointer;
        image-rendering: -webkit-optimize-contrast;
        image-rendering: crisp-edges;
    }
    
    .avatar-preview:hover {
        border-color: #0d6efd;
        transform: scale(1.05);
    }
    
    .profile-avatar {
        image-rendering: -webkit-optimize-contrast;
        image-rendering: crisp-edges;
    }
    
    .file-input-wrapper {
        position: relative;
        overflow: hidden;
        display: inline-block;
    }
    
    .profile-section {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .password-section {
        transition: all 0.3s ease;
        padding: 15px;
        border-radius: 8px;
        border: 2px solid transparent;
    }

</style>
@endpush

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Preview avatar before upload
    const avatarInput = document.getElementById('avatar');
    const avatarContainer = document.getElementById('avatar-preview-container');
    
    avatarInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            // Validate file size (2MB = 2048KB)
            if (file.size > 2048 * 1024) {
                Swal.fire({
                    title: 'File Too Large!',
                    text: 'Please select an image smaller than 2MB.',
                    icon: 'error'
                });
                this.value = '';
                return;
            }
            
            // Validate file type
            if (!file.type.match('image.*')) {
                Swal.fire({
                    title: 'Invalid File Type!',
                    text: 'Please select a valid image file.',
                    icon: 'error'
                });
                this.value = '';
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                // Only update the avatar preview in the form, not the navbar
                avatarContainer.innerHTML = `
                    <img src="${e.target.result}" 
                         alt="New Avatar Preview" 
                         class="rounded-circle avatar-preview" 
                         style="width: 100px; height: 100px; object-fit: cover;"
                         id="avatar-preview-image">
                `;
            };
            reader.readAsDataURL(file);
        } else {
            // Reset to original avatar if no file is selected
            resetAvatarPreview();
        }
    });
    
    // Function to reset avatar preview to original
    function resetAvatarPreview() {
        const originalAvatar = `@if($user->avatar_url)
            <img src="{{ $user->avatar_url }}" 
                 alt="Current Avatar" 
                 class="rounded-circle profile-avatar" 
                 style="width: 100px; height: 100px; object-fit: cover;">
        @else
            <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" 
                 style="width: 100px; height: 100px;">
                <i class="fas fa-user fa-3x text-white"></i>
            </div>
        @endif`;
        avatarContainer.innerHTML = originalAvatar;
    }

    // Password field interaction
    const passwordInput = document.getElementById('password');
    const currentPasswordInput = document.getElementById('current_password');
    const passwordConfirmationInput = document.getElementById('password_confirmation');
    
    passwordInput.addEventListener('input', function() {
        if (this.value.length > 0) {
            currentPasswordInput.required = true;
            passwordConfirmationInput.required = true;
            // Add visual indicator that password section is active
            document.querySelector('.password-section').classList.add('password-active');
        } else {
            currentPasswordInput.required = false;
            passwordConfirmationInput.required = false;
            // Clear the confirmation field when new password is cleared
            passwordConfirmationInput.value = '';
            // Remove visual indicator
            document.querySelector('.password-section').classList.remove('password-active');
        }
        
        // Check if new password is same as current password
        if (this.value.length > 0 && currentPasswordInput.value.length > 0) {
            if (this.value === currentPasswordInput.value) {
                this.setCustomValidity('New password must be different from your current password.');
                this.classList.add('is-invalid');
                
                // Show or update error message
                let errorDiv = this.parentNode.querySelector('.password-same-error');
                if (!errorDiv) {
                    errorDiv = document.createElement('div');
                    errorDiv.className = 'invalid-feedback password-same-error';
                    this.parentNode.appendChild(errorDiv);
                }
                errorDiv.textContent = 'New password must be different from your current password.';
            } else {
                this.setCustomValidity('');
                this.classList.remove('is-invalid');
                
                // Remove error message
                const errorDiv = this.parentNode.querySelector('.password-same-error');
                if (errorDiv) {
                    errorDiv.remove();
                }
            }
        }
    });
    
    // Also check when current password is entered
    currentPasswordInput.addEventListener('input', function() {
        if (passwordInput.value.length > 0 && this.value.length > 0) {
            if (passwordInput.value === this.value) {
                passwordInput.setCustomValidity('New password must be different from your current password.');
                passwordInput.classList.add('is-invalid');
                
                // Show or update error message
                let errorDiv = passwordInput.parentNode.querySelector('.password-same-error');
                if (!errorDiv) {
                    errorDiv = document.createElement('div');
                    errorDiv.className = 'invalid-feedback password-same-error';
                    passwordInput.parentNode.appendChild(errorDiv);
                }
                errorDiv.textContent = 'New password must be different from your current password.';
            } else {
                passwordInput.setCustomValidity('');
                passwordInput.classList.remove('is-invalid');
                
                // Remove error message
                const errorDiv = passwordInput.parentNode.querySelector('.password-same-error');
                if (errorDiv) {
                    errorDiv.remove();
                }
            }
        }
    });
    
    // Cancel button functionality
    const cancelBtn = document.getElementById('cancel-btn');
    cancelBtn.addEventListener('click', function(e) {
        // Reset avatar preview before navigating away
        resetAvatarPreview();
    });
    
    // Form submission with loading state
    const form = document.querySelector('form');
    const submitBtn = document.querySelector('button[type="submit"]');
    
    form.addEventListener('submit', function() {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Updating...';
    });
});
</script>
@endsection 