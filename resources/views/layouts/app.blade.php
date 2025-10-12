<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartStay</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
    @stack('styles')
</head>

<body>
    @if(auth()->check() && !request()->routeIs('login', 'register', 'password.request', 'password.reset', 'password.email'))
        <div class="container-fluid">
            <div class="row">
                <!-- Sidebar -->
                <div class="sidebar d-flex flex-column position-fixed"
                    style="top:0; left:0; height:100vh; width:240px; z-index:1030;">
                    <!-- Header -->
                    <div class="text-center py-3 border-bottom" style="border-color: rgba(255, 255, 255, 0.2) !important;">
                        <h5 class="mb-0 text-white fw-bold" style="font-size: 1.5rem; letter-spacing: 1px; text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">SmartStay</h5>
                    </div>

                    <!-- User Section -->
                    <div class="user-section p-3">
                        <div class="d-flex align-items-center mb-3">
                            <div class="me-3">
                                <a href="{{ route('profile.edit') }}" class="text-decoration-none">
                                    @if(auth()->user()->avatar_url)
                                        <img src="{{ auth()->user()->avatar_url }}" alt="Profile Picture"
                                            class="rounded-circle shadow-sm user-avatar"
                                            style="width: 50px; height: 50px; object-fit: cover;" title="Click to edit profile">
                                    @else
                                        <div class="rounded-circle avatar-gradient d-flex align-items-center justify-content-center shadow-sm user-avatar"
                                            style="width: 50px; height: 50px;" title="Click to edit profile">
                                            <i class="fas fa-user text-white"></i>
                                        </div>
                                    @endif
                                </a>
                            </div>
                            <div class="flex-grow-1 user-info">
                                <div class="fw-bold text-white mb-1" style="font-size: 0.9rem; line-height: 1.2; word-wrap: break-word;"
                                    title="{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}">
                                    {{ auth()->user()->first_name }} {{ auth()->user()->last_name }}
                                </div>
                                <div class="small text-white-50 mb-2" style="font-size: 0.75rem; line-height: 1.2; word-wrap: break-word; overflow-wrap: break-word;"
                                    title="{{ auth()->user()->email }}">
                                    {{ auth()->user()->email }}
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge bg-light text-dark" style="font-size: 0.7rem; padding: 2px 6px;">{{ ucfirst(auth()->user()->role) }}</span>
                                    <a href="{{ route('profile.edit') }}" class="btn btn-outline-light btn-sm"
                                        style="font-size: 0.7rem; padding: 2px 6px; border-color: rgba(255, 255, 255, 0.5); color: white;">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation -->
                    <nav class="nav flex-column flex-grow-1 py-2">
                        <!-- Admin Navigation -->
                        @if(auth()->user()->role === 'admin')
                            <a class="nav-link {{ Route::currentRouteName() === 'admin.dashboard' ? 'active' : '' }}"
                                href="{{ route('admin.dashboard') }}">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                            <a class="nav-link {{ Route::currentRouteName() === 'admin.users.index' ? 'active' : '' }}"
                                href="{{ route('admin.users.index') }}">
                                <i class="fas fa-users-cog me-2"></i>User Management
                            </a>
                            <a class="nav-link {{ Route::currentRouteName() === 'admin.properties.index' ? 'active' : '' }}"
                                href="{{ route('admin.properties.index') }}">
                                <i class="fas fa-building me-2"></i>All Properties
                            </a>
                            <a class="nav-link {{ Route::currentRouteName() === 'admin.logs.index' ? 'active' : '' }}"
                                href="{{ route('admin.logs.index') }}">
                                <i class="fas fa-history me-2"></i>System Logs
                            </a>
                        @endif

                        <!-- Landlord Navigation -->
                        @if(auth()->user()->role === 'landlord')
                            <a class="nav-link {{ Route::currentRouteName() === 'landlord.properties.index' ? 'active' : '' }}"
                                href="{{ route('landlord.properties.index') }}">
                                <i class="fas fa-home me-2"></i>Manage Properties
                            </a>
                            <a class="nav-link {{ Route::currentRouteName() === 'landlord.applications.all' ? 'active' : '' }}"
                                href="{{ route('landlord.applications.all') }}">
                                <i class="fas fa-file-alt me-2"></i>View Applications
                            </a>
                            <a class="nav-link {{ Route::currentRouteName() === 'landlord.boarders.all' ? 'active' : '' }}"
                                href="{{ route('landlord.boarders.all') }}">
                                <i class="fas fa-users me-2"></i>View Boarders
                            </a>
                        @endif

                        <!-- Tenant Navigation -->
                        @if(auth()->user()->role === 'tenant')
                            <a class="nav-link {{ Route::currentRouteName() === 'tenant.properties.index' ? 'active' : '' }}"
                                href="{{ route('tenant.properties.index') }}">
                                <i class="fas fa-search me-2"></i>Browse Properties
                            </a>
                            <a class="nav-link {{ Route::currentRouteName() === 'tenant.applications.index' ? 'active' : '' }}"
                                href="{{ route('tenant.applications.index') }}">
                                <i class="fas fa-file-alt me-2"></i>My Applications
                            </a>
                        @endif

                        <!-- Logout Form -->
                        <div class="mt-auto border-top pt-3" style="border-color: rgba(255, 255, 255, 0.2) !important;">
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="nav-link w-100 text-start border-0" 
                                        style="color: #e0e7ff !important; font-weight: 500; transition: all 0.3s ease; background: transparent !important; border: none !important; border-radius: 8px; margin: 0 8px; padding: 12px 16px; outline: none !important;"
                                        onmouseover="this.style.background='linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important'; this.style.color='white !important'; this.style.transform='translateX(4px)'; this.style.boxShadow='0 4px 12px rgba(239, 68, 68, 0.3)'; this.style.border='none !important'"
                                        onmouseout="this.style.background='transparent !important'; this.style.color='#e0e7ff !important'; this.style.transform='translateX(0)'; this.style.boxShadow='none'; this.style.border='none !important'">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </button>
                            </form>
                        </div>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="main-content">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @yield('content')
            </div>
        </div>
        </div>
    @else
        <div class="auth-container">
            @yield('content')
        </div>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')

    @if(session('success'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    title: 'Success!',
                    text: '{{ session('success') }}',
                    icon: 'success',
                    showConfirmButton: false,
                    timer: 3000
                });
            });
        </script>
    @endif

    @if(session('error'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    title: 'Error!',
                    text: '{{ session('error') }}',
                    icon: 'error',
                    showConfirmButton: false,
                    timer: 3000
                });
            });
        </script>
    @endif

    @if (session('crud_success'))
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                Swal.fire({
                    toast: true,
                    position: 'bottom-end',
                    icon: 'success',
                    title: @json(session('crud_success')),
                    showConfirmButton: false,
                    background: 'blue',
                    color: '#ffffff',
                    timer: 3000,
                    // timerProgressBar: true
                });
            });
        </script>

    @endif


    @if(session('crud_error'))
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                Swal.fire({
                    toast: true,
                    position: 'bottom-end',
                    icon: 'success',
                    title: @json(session('crud_error')),
                    showConfirmButton: false,
                    background: 'blue', // Custom background color (green)
                    color: '#ffffff', // Text color (white)
                    timer: 5000,  // Auto close after 3 seconds
                    // timerProgressBar: true
                });
            });
        </script>
    @endif


    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Remove any text node that looks like a parent-placeholder artifact
            Array.from(document.body.childNodes).forEach(function (node) {
                if (
                    node.nodeType === Node.TEXT_NODE &&
                    node.textContent.trim().match(/^##parent-placeholder-[a-f0-9]+##$/)
                ) {
                    node.parentNode.removeChild(node);
                }
            });

            // Avatar interaction enhancements
            const userAvatar = document.querySelector('.user-avatar');
            const userSection = document.querySelector('.user-section');

            if (userAvatar && userSection) {
                // Add subtle animation on hover
                userSection.addEventListener('mouseenter', function () {
                    this.style.transform = 'translateY(-2px)';
                    this.style.transition = 'all 0.3s ease';
                });

                userSection.addEventListener('mouseleave', function () {
                    this.style.transform = 'translateY(0)';
                });
            }
        });
    </script>
</body>

</html>

<style>
    /* Prevent horizontal scrollbars */
    html,
    body {
        overflow-x: hidden;
        max-width: 100%;
    }

    .container-fluid {
        overflow-x: hidden;
        max-width: 100%;
    }

    .pad-img {
        width: 100%;
        height: 200px;
        /* Set your desired height */
        object-fit: cover;
    }

    .sidebar {
        min-height: 100vh;
        background: linear-gradient(180deg, #1e3a8a 0%, #1e40af 50%, #2563eb 100%);
        border-right: 1px solid #1e40af;
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        width: 240px;
        z-index: 1030;
        overflow-y: auto;
        overflow-x: hidden;
        box-shadow: 2px 0 10px rgba(30, 58, 138, 0.1);
    }

    .sidebar .nav-link {
        color: #e0e7ff !important;
        padding: 10px 16px;
        margin: 3px 8px;
        border-radius: 8px;
        transition: all 0.3s ease;
        font-size: 0.9rem;
        font-weight: 500;
        line-height: 1.4;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .sidebar .nav-link:hover {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
        color: white !important;
        transform: translateX(4px);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }

    .sidebar .nav-link.active {
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%) !important;
        color: white !important;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        font-weight: 600;
    }

    .main-content {
        padding: 20px;
        background-color: #fff;
        margin-left: 240px;
        width: calc(100% - 240px);
        overflow-x: hidden;
        min-height: 100vh;
    }

    .auth-container {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #f8f9fa;
    }

    .alert {
        margin-bottom: 20px;
    }

    /* Avatar Styling */
    .sidebar .user-avatar {
        transition: all 0.3s ease;
        cursor: pointer;
        image-rendering: -webkit-optimize-contrast;
        image-rendering: crisp-edges;
    }

    .sidebar .user-avatar:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2) !important;
    }

    .sidebar .user-info {
        transition: all 0.3s ease;
    }

    .sidebar .user-section {
        background: rgba(255, 255, 255, 0.05);
        transition: all 0.3s ease;
        border-radius: 12px;
        margin: 8px;
        padding: 12px;
    }

    .sidebar .user-section:hover {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .sidebar .user-info {
        color: white !important;
    }

    .sidebar .user-info .text-white {
        color: white !important;
    }

    .sidebar .user-info .text-white-50 {
        color: rgba(255, 255, 255, 0.7) !important;
    }

    .sidebar .user-info {
        min-width: 0;
        flex: 1;
    }

    .sidebar .user-info div {
        word-break: break-word;
        overflow-wrap: break-word;
        hyphens: auto;
    }

    /* Logout button specific styling */
    .sidebar .nav-link[type="submit"] {
        border: none !important;
        outline: none !important;
        box-shadow: none !important;
        background: transparent !important;
        color: #e0e7ff !important;
    }

    .sidebar .nav-link[type="submit"]:focus {
        border: none !important;
        outline: none !important;
        box-shadow: none !important;
        background: transparent !important;
    }

    .sidebar .nav-link[type="submit"]:hover {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
        color: white !important;
        transform: translateX(4px);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    }

    /* Gradient background for default avatar */
    .avatar-gradient {
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    }

    .avatar-gradient-alt {
        background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
    }

    .avatar-gradient-blue {
        background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
    }

    /* Responsive Design */
    @media (max-width: 991.98px) {
        .sidebar {
            position: static !important;
            width: 100% !important;
            height: auto !important;
            min-height: auto !important;
            border-right: none !important;
            border-bottom: 1px solid #dee2e6 !important;
        }

        .main-content {
            margin-left: 0 !important;
            width: 100% !important;
        }
    }
</style>