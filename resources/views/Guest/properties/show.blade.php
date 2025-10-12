@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="sidebar d-flex flex-column align-items-start py-4 position-fixed" style="top:0; left:0; height:100vh; width:240px; z-index:1030;">
            <div class="text-center mb-4 w-100">
                <h4 class="fw-bold text-white" style="letter-spacing:1px; font-size: 1.5rem; text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">SmartStay</h4>
            </div>
            <nav class="nav flex-column w-100">
                <a class="nav-link px-4 py-2" href="{{ route('welcome') }}#properties-section">
                    <i class="fas fa-list me-2"></i>View Listings
                </a>
                <a class="nav-link px-4 py-2" href="{{ route('welcome') }}#map-section">
                    <i class="fas fa-map-marked-alt me-2"></i>View Map
                </a>
                @guest
                    <a class="nav-link px-4 py-2" href="{{ route('login') }}">
                        <i class="fas fa-sign-in-alt me-2"></i>Login
                    </a>
                    <a class="nav-link px-4 py-2" href="{{ route('register') }}">
                        <i class="fas fa-user-plus me-2"></i>Register
                    </a>
                @else
                    <a class="nav-link px-4 py-2" href="{{ route('dashboard') }}">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                @endguest
            </nav>
        </div>
        <!-- Main Content -->
        <div class="main-content" style="margin-left:240px; padding: 40px 20px; background-color: #fff; min-height: 100vh; width:calc(100% - 240px);">
            <div class="container py-4">
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="card shadow-sm mb-4">
                            @if($property->all_images && count($property->all_images) > 0)
                                <!-- Image Gallery -->
                                <div id="propertyImageCarousel" class="carousel slide" data-bs-ride="carousel">
                                    <div class="carousel-inner">
                                        @foreach($property->all_images as $index => $image)
                                            <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                                                <img src="{{ asset('storage/' . $image) }}" class="d-block w-100 rounded-top" style="object-fit:cover; max-height:320px;" alt="Property Image {{ $index + 1 }}">
                                            </div>
                                        @endforeach
                                    </div>
                                    @if(count($property->all_images) > 1)
                                        <button class="carousel-control-prev" type="button" data-bs-target="#propertyImageCarousel" data-bs-slide="prev">
                                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                            <span class="visually-hidden">Previous</span>
                                        </button>
                                        <button class="carousel-control-next" type="button" data-bs-target="#propertyImageCarousel" data-bs-slide="next">
                                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                            <span class="visually-hidden">Next</span>
                                        </button>
                                        <!-- Indicators -->
                                        <div class="carousel-indicators">
                                            @foreach($property->all_images as $index => $image)
                                                <button type="button" data-bs-target="#propertyImageCarousel" data-bs-slide-to="{{ $index }}" class="{{ $index === 0 ? 'active' : '' }}" aria-current="{{ $index === 0 ? 'true' : 'false' }}" aria-label="Slide {{ $index + 1 }}"></button>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @else
                                <img src="https://via.placeholder.com/600x320?text=No+Image" class="card-img-top rounded-top" style="object-fit:cover; max-height:320px;">
                            @endif
                            <div class="card-body">
                                <h2 class="card-title mb-3">{{ $property->propertyName }}</h2>
                                <div class="row mb-2">
                                    <div class="col-5 text-muted fw-bold">Description:</div>
                                    <div class="col-7">{{ $property->propertyDescription ?? '-' }}</div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-5 text-muted fw-bold">Location:</div>
                                    <div class="col-7">{{ $property->propertyLocation }}</div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-5 text-muted fw-bold">Rent:</div>
                                    <div class="col-7">₱{{ number_format($property->propertyRent, 2) }}</div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-5 text-muted fw-bold">Status:</div>
                                    <div class="col-7">
                                        @php
                                            $statusDisplay = [
                                                'Available' => 'Available',
                                                'Fullyoccupied' => 'Fully Occupied',
                                                'Maintenance' => 'Maintenance'
                                            ];
                                        @endphp
                                        <span class="badge 
                                            @if($property->propertyStatus == 'Available') bg-success
                                            @elseif($property->propertyStatus == 'Fullyoccupied') bg-danger
                                            @else bg-warning text-dark
                                            @endif
                                        ">
                                            {{ $statusDisplay[$property->propertyStatus] ?? $property->propertyStatus }}
                                        </span>
                                    </div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-5 text-muted fw-bold">Landlord:</div>
                                    <div class="col-7">
                                        {{ $property->landlord->first_name ?? 'N/A' }} {{ $property->landlord->last_name ?? '' }}
                                    </div>
                                </div>
                                <div class="row mb-2">
                                    @if ($property->number_of_boarders >= $property->vacancy)
                                        <div class="col-5 text-muted fw-bold">Vacant:</div>
                                        <div class="col-7">Fully Occupied</div>
                                    @else
                                        <div class="col-5 text-muted fw-bold">Vacant:</div>
                                        <div class="col-7">{{ $property->number_of_boarders ?? 0 }}/{{ $property->vacancy ?? 0 }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @if($property->propertyStatus == 'Available')
                            <div class="d-flex gap-2 mt-3">
                                @guest
                                    <a href="{{ route('register') }}" class="btn btn-primary">
                                        Apply for this Property
                                    </a>
                                @else
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#applyPropertyModal">
                                        Apply for this Property
                                    </button>
                                @endguest
                                <a href="{{ route('welcome') }}" class="btn btn-secondary">
                                    Back to Listings
                                </a>
                            </div>
                        @else
                            <div class="mt-3">
                                <a href="{{ route('welcome') }}" class="btn btn-secondary">
                                    Back to Listings
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
                @auth
                <!-- Apply Property Modal -->
                <div class="modal fade" id="applyPropertyModal" tabindex="-1" aria-labelledby="applyPropertyModalLabel" aria-hidden="true">
                  <div class="modal-dialog">
                    <div class="modal-content">
                      <form action="{{ route('guest.properties.apply', ['propertyId' => $property->propertyID]) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                          <h5 class="modal-title w-100 text-center" id="applyPropertyModalLabel">Apply Property</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                          <div class="mb-3">
                            <label for="guest_name" class="form-label">Your Name:</label>
                            <input type="text" name="guest_name" id="guest_name" class="form-control" required>
                          </div>
                          <div class="mb-3">
                            <label for="guest_email" class="form-label">Your Email:</label>
                            <input type="email" name="guest_email" id="guest_email" class="form-control" required>
                          </div>
                          <div class="mb-3">
                            <label for="message" class="form-label">Message:</label>
                            <textarea name="message" id="message" class="form-control" rows="5" required></textarea>
                          </div>
                        </div>
                        <div class="modal-footer justify-content-center">
                          <button type="button" class="btn btn-danger me-2" data-bs-dismiss="modal">Cancel</button>
                          <button type="submit" class="btn btn-success">Apply</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
                @endauth
                <!-- Map at the bottom -->
                <div class="card shadow-sm mb-4 mt-5">
                    <div class="card-body">
                        <h4 class="card-title mb-3">Map Location</h4>
                        <div id="map" style="height: 400px; width: 100%; border-radius: 10px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
<style>
    body { background: #f7f8fa; }
    .sidebar .nav-link {
        color: #e0e7ff !important;
        padding: 10px 20px;
        margin: 5px 0;
        border-radius: 8px;
        transition: all 0.3s ease;
        font-size: 1.08rem;
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
    .sidebar h4 {
        font-size: 1.5rem;
        letter-spacing: 1px;
        color: white;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    .main-content {
        background: #fff;
    }
    @media (max-width: 991.98px) {
        .sidebar {
            position: static !important;
            width: 100% !important;
            min-height: auto !important;
            border-right: none !important;
        }
        .main-content {
            margin-left: 0 !important;
            width: 100% !important;
        }
    }
</style>
@endpush

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            var lat = {{ $property->latitude ?? 0 }};
            var lng = {{ $property->longitude ?? 0 }};

            var map = L.map('map', {
                zoomControl: true,
                zoomControlOptions: {
                    position: 'topright'
                }
            }).setView([lat, lng], 15);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            // Add custom reset control
            L.Control.ResetView = L.Control.extend({
                onAdd: function(map) {
                    const container = L.DomUtil.create('div', 'leaflet-bar leaflet-control leaflet-control-custom');
                    container.innerHTML = '<a href="#" title="Reset View" role="button" aria-label="Reset View">⌂</a>';
                    container.style.backgroundColor = 'white';
                    container.style.width = '30px';
                    container.style.height = '30px';
                    container.style.display = 'flex';
                    container.style.alignItems = 'center';
                    container.style.justifyContent = 'center';
                    
                                container.onclick = function(e) {
                e.preventDefault();
                map.setView([lat, lng], 15);
            }
                    
                    return container;
                },
                onRemove: function(map) {}
            });

            // Add reset control to map
            new L.Control.ResetView({ position: 'topleft' }).addTo(map);

            L.marker([lat, lng]).addTo(map)
                .bindPopup("{{ $property->propertyName }}")
                .openPopup();
        });
    </script>
@endpush
