@extends('layouts.app')

@section('content')

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">User Management</h4>
                        <button class="btn btn-light btn-sm text-primary" data-bs-toggle="modal"
                            data-bs-target="#createUserModal">
                            <i class="fas fa-user-plus"></i> Register New User
                        </button>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-2 mb-3 align-items-center">
                            <div class="col-md-4">
                                <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                                    placeholder="Search user...">
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-primary w-100" type="submit">Search</button>
                            </div>
                            <div class="col-md-2">
                                <select name="sort" class="form-select" onchange="this.form.submit()">
                                    <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>Sort: Latest
                                    </option>
                                    <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Sort: Oldest
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="role" class="form-select" onchange="this.form.submit()">
                                    <option value="all" {{ request('role') == 'all' ? 'selected' : '' }}>Role: All</option>
                                    <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                                    <option value="tenant" {{ request('role') == 'tenant' ? 'selected' : '' }}>Tenant</option>
                                    <option value="landlord" {{ request('role') == 'landlord' ? 'selected' : '' }}>Landlord
                                    </option>
                                </select>
                            </div>
                        </form>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th class="py-1">Profile</th>
                                        <th class="py-1">First Name</th>
                                        <th class="py-1">Last Name</th>
                                        <th class="py-1">Email</th>
                                        <th class="py-1">Role</th>
                                        <th class="py-1">Status</th>
                                        <th class="py-1">Created At</th>
                                        <th class="py-1">Update At</th>
                                        <th class="py-1">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($users as $user)
                                        <tr>
                                            <td class="py-1">
                                                <div class="d-flex align-items-center">
                                                    @if($user->avatar_url)
                                                        <img src="{{ $user->avatar_url }}" alt="{{ $user->first_name }}'s Avatar"
                                                            class="rounded-circle me-2 user-management-avatar"
                                                            style="width: 40px; height: 40px; object-fit: cover;">
                                                    @else
                                                        <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center me-2 user-management-avatar"
                                                            style="width: 40px; height: 40px;">
                                                            <i class="fas fa-user text-white"></i>
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="py-1">{{ $user->first_name }}</td>
                                            <td class="py-1">{{ $user->last_name }}</td>
                                            <td class="py-1">{{ $user->email }}</td>
                                            <td class="py-1">
                                                <span class="badge bg-secondary">{{ ucfirst($user->role) }}</span>
                                            </td>
                                            <td class="py-1">
                                                @if($user->locked) {{-- Replace with your actual locked column --}}
                                                    <form action="{{ route('admin.unlock-user', $user->id) }}" method="POST" style="display:inline;">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="btn btn-sm btn-outline-warning">
                                                            <i class="fas fa-lock-open"></i>
                                                        </button>
                                                    </form>

                                                @else
                                                    <span class="badge bg-success">Active</span>
                                                @endif
                                            </td>
                                            <td class="py-1">{{ $user->created_at->format('Y-m-d H:i') }}</td>
                                            <td class="py-1">{{ $user->updated_at->format('Y-m-d H:i') }}</td>
                                            <td class="py-1">
                                                <button class="btn btn-sm btn-warning editUserBtn" data-id="{{ $user->id }}"
                                                    data-first_name="{{ $user->first_name }}"
                                                    data-last_name="{{ $user->last_name }}" data-email="{{ $user->email }}"
                                                    data-role="{{ $user->role }}" data-avatar="{{ $user->avatar_url }}"
                                                    data-bs-toggle="modal" data-bs-target="#editUserModal">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger deleteUserBtn" data-id="{{ $user->id }}"
                                                    data-name="{{ $user->first_name }} {{ $user->last_name }}"
                                                    data-bs-toggle="modal" data-bs-target="#deleteUserModal">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-3">No users found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            {{ $users->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create User Modal -->
    <div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('admin.users.store') }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="createUserModalLabel">Register New User</h5>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>First Name</label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Last Name</label>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Role</label>
                            <select name="role" class="form-select" required>
                                <option value="admin">Admin</option>
                                <option value="tenant">Tenant</option>
                                <option value="landlord">Landlord</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="editUserForm" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="editUserId">

                        <!-- Avatar Section -->
                        <div class="mb-3">
                            <label class="form-label">Profile Picture</label>
                            <div class="d-flex align-items-center">
                                <div class="me-3" id="editAvatarPreview">
                                    <!-- Avatar preview will be populated by JavaScript -->
                                </div>
                                <div class="flex-grow-1">
                                    <input type="file" class="form-control" id="editAvatar" name="avatar" accept="image/*">
                                    <div class="form-text">Upload a new profile picture (JPEG, PNG, JPG, GIF - Max: 2MB)
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label>First Name</label>
                            <input type="text" name="first_name" id="editFirstName" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Last Name</label>
                            <input type="text" name="last_name" id="editLastName" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Email</label>
                            <input type="email" name="email" id="editEmail" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Role</label>
                            <select name="role" id="editRole" class="form-select" required>
                                <option value="admin">Admin</option>
                                <option value="tenant">Tenant</option>
                                <option value="landlord">Landlord</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete User Modal -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="deleteUserForm">
                @csrf
                @method('DELETE')
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteUserModalLabel">Delete User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete <strong id="deleteUserName"></strong>?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- JS for populating modals -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Edit User
            document.querySelectorAll('.editUserBtn').forEach(function (button) {
                button.addEventListener('click', function () {
                    document.getElementById('editUserId').value = this.dataset.id;
                    document.getElementById('editFirstName').value = this.dataset.first_name;
                    document.getElementById('editLastName').value = this.dataset.last_name;
                    document.getElementById('editEmail').value = this.dataset.email;
                    document.getElementById('editRole').value = this.dataset.role;
                    document.getElementById('editUserForm').action = '/admin/users/' + this.dataset.id;

                    // Populate avatar preview
                    const avatarPreview = document.getElementById('editAvatarPreview');
                    const avatarUrl = this.dataset.avatar;

                    if (avatarUrl && avatarUrl !== 'null') {
                        avatarPreview.innerHTML = `
                <img src="${avatarUrl}" 
                   alt="Current Avatar" 
                   class="rounded-circle" 
                   style="width: 60px; height: 60px; object-fit: cover; border: 2px solid #dee2e6;">
              `;
                    } else {
                        avatarPreview.innerHTML = `
                <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" 
                   style="width: 60px; height: 60px; border: 2px solid #dee2e6;">
                  <i class="fas fa-user text-white"></i>
                </div>
              `;
                    }

                    // Clear file input
                    document.getElementById('editAvatar').value = '';
                });
            });

            // Delete User
            document.querySelectorAll('.deleteUserBtn').forEach(function (button) {
                button.addEventListener('click', function () {
                    document.getElementById('deleteUserName').textContent = this.dataset.name;
                    document.getElementById('deleteUserForm').action = '/admin/users/' + this.dataset.id;
                });
            });

            // Avatar preview for edit modal
            document.getElementById('editAvatar').addEventListener('change', function (e) {
                const file = e.target.files[0];
                const avatarPreview = document.getElementById('editAvatarPreview');

                if (file) {
                    // Validate file size (2MB = 2048KB)
                    if (file.size > 2048 * 1024) {
                        alert('File too large! Please select an image smaller than 2MB.');
                        this.value = '';
                        return;
                    }

                    // Validate file type
                    if (!file.type.match('image.*')) {
                        alert('Invalid file type! Please select a valid image file.');
                        this.value = '';
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = function (e) {
                        avatarPreview.innerHTML = `
                <img src="${e.target.result}" 
                   alt="New Avatar Preview" 
                   class="rounded-circle" 
                   style="width: 60px; height: 60px; object-fit: cover; border: 2px solid #0d6efd;">
              `;
                    };
                    reader.readAsDataURL(file);
                }
            });
        });


    </script>

    <style>
        .user-management-avatar {
            transition: all 0.3s ease;
            border: 2px solid #dee2e6;
            image-rendering: -webkit-optimize-contrast;
            image-rendering: crisp-edges;
        }

        .user-management-avatar:hover {
            transform: scale(1.1);
            border-color: #0d6efd;
            box-shadow: 0 2px 8px rgba(13, 110, 253, 0.3);
        }

        /* Gradient backgrounds for default avatars */
        .user-management-avatar.bg-secondary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        }

        /* Make table more compact and professional */
        .table-sm td {
            vertical-align: middle;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .user-management-avatar {
                width: 35px !important;
                height: 35px !important;
            }

            .user-management-avatar i {
                font-size: 0.8rem;
            }
        }
    </style>
@endsection