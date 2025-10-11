@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="sidebar d-flex flex-column align-items-start py-4 position-fixed" style="top:0; left:0; height:100vh; width:240px; background-color:#f8f9fa; border-right:1px solid #dee2e6; z-index:1030;">
            <div class="text-center mb-4 w-100">
                <h4 class="fw-bold" style="letter-spacing:1px;">SmartStay</h4>
            </div>
            <nav class="nav flex-column w-100">
                <a class="nav-link px-4 py-2" href="#properties-section">
                    <i class="fas fa-list me-2"></i>View Listings
                </a>
                <a class="nav-link px-4 py-2" href="#map-section">
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
            <div id="properties-section">
                <h1 class="mb-4 text-center">SmartStay - Available Properties</h1>
                <form method="GET" action="{{ route('welcome') }}" class="mb-4">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <div class="input-group">
                                <input type="text" name="search" class="form-control" placeholder="Search properties..." value="{{ request('search') }}">
                                <button class="btn btn-primary" type="submit">Search</button>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select name="location_filter" class="form-select" onchange="this.form.submit()">
                                <option value="">All Locations</option>
                                @foreach(config('app.bukidnon_cities_municipalities', []) as $lgu)
                                    <option value="{{ $lgu }}" {{ request('location_filter') == $lgu ? 'selected' : '' }}>{{ $lgu }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="price_filter" class="form-select" onchange="this.form.submit()">
                                <option value="">All Prices</option>
                                <option value="below_1000" {{ request('price_filter') == 'below_1000' ? 'selected' : '' }}>Below ₱1,000</option>
                                <option value="1000_2000" {{ request('price_filter') == '1000_2000' ? 'selected' : '' }}>₱1,000 - ₱2,000</option>
                                <option value="2000_3000" {{ request('price_filter') == '2000_3000' ? 'selected' : '' }}>₱2,000 - ₱3,000</option>
                                <option value="above_3000" {{ request('price_filter') == 'above_3000' ? 'selected' : '' }}>Above ₱3,000</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <a href="{{ route('welcome') }}" class="btn btn-outline-secondary w-100">Reset</a>
                        </div>
                    </div>
                </form>
                <div class="row justify-content-center">
                    @forelse($properties as $property)
                        <div class="col-md-3 mb-4">
                            <a href="{{ route('guest.properties.show', ['property' => $property->propertyID]) }}" style="text-decoration: none; color: inherit;">
                                <div class="card h-100 shadow-sm">
                                    @if($property->main_image)
                                        <img src="{{ asset('storage/' . $property->main_image) }}" class="card-img-top property-img" alt="{{ $property->propertyName }}">
                                    @else
                                        <img src="https://via.placeholder.com/400x200?text=No+Image" class="card-img-top property-img" alt="No Image">
                                    @endif
                                    <div class="card-body">
                                        <h5 class="card-title">{{ $property->propertyName }}</h5>
                                        <p class="card-text mb-1"><strong>Location:</strong> {{ $property->propertyLocation }}</p>
                                        <p class="card-text mb-1"><strong>Rent:</strong> ₱{{ number_format($property->propertyRent, 2) }}</p>
                                        @php
                                            $statusDisplay = [
                                                'Available' => 'Available',
                                                'Fullyoccupied' => 'Fully Occupied',
                                                'Maintenance' => 'Maintenance'
                                            ];
                                        @endphp
                                        <p class="card-text mb-1"><strong>Status:</strong> {{ $statusDisplay[$property->propertyStatus] ?? $property->propertyStatus }}</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="alert alert-info text-center">No properties found.</div>
                        </div>
                    @endforelse
                </div>
                <div class="mt-3">
                    {{ $properties->appends(request()->query())->links('pagination::bootstrap-5') }}
                </div>
            </div>
            <div id="map-section" class="mt-5">
                <h2 class="text-center mb-4">Available Properties Map</h2>
                <div id="propertiesMap" style="height: 500px; width: 100%; border-radius: 10px;"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
<style>
    body { background: #f7f8fa; }
    .property-img {
        height: 200px;
        object-fit: cover;
        border-top-left-radius: 0.5rem;
        border-top-right-radius: 0.5rem;
    }
    #propertiesMap {
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    .leaflet-popup-content {
        margin: 10px;
    }
    .leaflet-popup-content h5 {
        margin-bottom: 5px;
    }
    .leaflet-popup-content p {
        margin-bottom: 5px;
    }
    .sidebar {
        min-height: 100vh;
        background-color: #f8f9fa;
        padding-top: 20px;
        border-right: 1px solid #dee2e6;
    }
    .sidebar .nav-link {
        color: #333;
        padding: 10px 20px;
        margin: 5px 0;
        border-radius: 5px;
        transition: all 0.3s ease;
    }
    .sidebar .nav-link:hover {
        background-color: #e9ecef;
        color: #0d6efd;
    }
    .sidebar .nav-link.active {
        background-color: #0d6efd;
        color: white;
    }
    .sidebar h4 {
        font-size: 1.5rem;
        letter-spacing: 1px;
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
    .leaflet-control-zoom-reset {
        font-size: 18px;
        line-height: 26px;
        text-align: center;
        text-decoration: none;
        color: black;
        display: block;
        width: 26px;
        height: 26px;
    }
    .leaflet-control-zoom-reset:hover {
        background-color: #f4f4f4;
        color: black;
    }
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the map with enhanced controls
    const map = L.map('propertiesMap', {
        zoomControl: true, // Enable zoom controls
        zoomControlOptions: {
            position: 'topright'
        }
    });

    // Add the tile layer (OpenStreetMap)
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    // Store all markers for bounds calculation
    var allMarkers = [];
    var markerGroup = L.layerGroup().addTo(map);

    // Add markers for each property
    @foreach($properties as $property)
        @if($property->latitude && $property->longitude)
            const marker{{ $property->propertyID }} = L.marker([{{ $property->latitude }}, {{ $property->longitude }}]);
            const popupContent{{ $property->propertyID }} = `
                <div style="min-width: 380px; max-width: 420px; padding: 5px;">
                    <h5 style="margin-bottom: 8px; color: #333;">{{ $property->propertyName }}</h5>
                    <p style="margin-bottom: 5px;"><strong>Location:</strong> {{ $property->propertyLocation }}</p>
                    <p style="margin-bottom: 5px;"><strong>Rent:</strong> ₱{{ number_format($property->propertyRent, 2) }}</p>
                    @php
                        $statusDisplay = [
                            'Available' => 'Available',
                            'Fullyoccupied' => 'Fully Occupied',
                            'Maintenance' => 'Maintenance'
                        ];
                    @endphp
                    <p style="margin-bottom: 10px;"><strong>Status:</strong> {{ $statusDisplay[$property->propertyStatus] ?? $property->propertyStatus }}</p>
                    
                    <!-- Weather Section -->
                    <div style="border-top: 1px solid #ddd; padding-top: 10px; margin-top: 10px;">
                        <div id="weather-{{ $property->propertyID }}" style="padding: 8px; background: #f8f9fa; border-radius: 5px;">
                            <div class="weather-loading">Loading weather data...</div>
                        </div>
                    </div>
                    
                    <div style="margin-top: 6px; text-align: center;">
                        <a href="{{ route('guest.properties.show', $property->propertyID) }}" class="btn btn-outline-secondary btn-sm" target="_blank" style="border: 1px solid #6c757d; color: #6c757d; background: transparent; text-decoration: none; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                            <i class="fas fa-eye me-1"></i>View Details
                        </a>
                    </div>
                </div>
            `;
            marker{{ $property->propertyID }}.bindPopup(popupContent{{ $property->propertyID }}, {
                maxWidth: 500,
                maxHeight: 400,
                autoPan: true,
                autoPanPadding: [20, 20],
                keepInView: true,
                closeButton: true,
                autoClose: false,
                className: 'custom-popup'
            });
            
            // Auto-load weather data when popup opens
            marker{{ $property->propertyID }}.on('popupopen', function() {
                loadWeatherData({{ $property->propertyID }});
            });
            
            markerGroup.addLayer(marker{{ $property->propertyID }});
            allMarkers.push(marker{{ $property->propertyID }});
        @endif
    @endforeach

    // Function to fit map to show all markers
    function fitMapToMarkers() {
        if (allMarkers.length > 0) {
            var group = new L.featureGroup(allMarkers);
            map.fitBounds(group.getBounds().pad(0.1));
        } else {
            // If no markers, center on Malaybalay City
            map.setView([8.1574, 125.1278], 15);
        }
    }

    // Set initial view to fit all markers
    fitMapToMarkers();

    // Add custom reset control
    L.Control.ResetView = L.Control.extend({
        onAdd: function(map) {
            const container = L.DomUtil.create('div', 'leaflet-bar leaflet-control');
            const link = L.DomUtil.create('a', 'leaflet-control-zoom-reset', container);
            link.innerHTML = '⌂';
            link.href = '#';
            link.title = 'Reset View to All Properties';
            link.setAttribute('role', 'button');
            link.setAttribute('aria-label', 'Reset View to All Properties');
            
            L.DomEvent.on(link, 'click', function(e) {
                L.DomEvent.preventDefault(e);
                fitMapToMarkers();
            });
            
            return container;
        },
        onRemove: function(map) {}
    });

    // Add reset control to map
    new L.Control.ResetView({ position: 'topleft' }).addTo(map);

    // Add geocoder control for searching locations
    L.Control.geocoder({
        defaultMarkGeocode: false,
        position: 'topright'
    })
        .on('markgeocode', function (e) {
            const center = e.geocode.center;
            map.setView(center, 15);
        })
        .addTo(map);

    // Smooth scroll for navigation
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });

    // Weather data functionality
    function loadWeatherData(propertyId) {
        const weatherDiv = document.getElementById(`weather-${propertyId}`);
        if (!weatherDiv) return;

        // Show loading state
        weatherDiv.innerHTML = '<div class="weather-loading">Loading weather data...</div>';

        // Fetch weather data
        fetch(`/properties/${propertyId}/weather`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayWeatherData(weatherDiv, data.data);
                } else {
                    weatherDiv.innerHTML = '<div class="weather-error">Unable to load weather data</div>';
                }
            })
            .catch(error => {
                console.error('Weather fetch error:', error);
                weatherDiv.innerHTML = '<div class="weather-error">Error loading weather data</div>';
            });
    }

    function displayWeatherData(container, weatherData) {
        const current = weatherData.current;
        const forecast = weatherData.forecast;

        let html = `
            <div style="font-size: 14px; width: 100%;">
                <!-- Current Weather Section -->
                <div style="margin-bottom: 8px;">
                    <div style="display: flex; align-items: center; margin-bottom: 4px;">
                        <span style="font-size: 22px; margin-right: 6px;">${current.weather_icon}</span>
                        <div>
                            <div style="font-size: 18px; font-weight: bold; color: #333;">${current.temperature}°C</div>
                            <div style="font-size: 11px; color: #666;">${current.weather_description}</div>
                        </div>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 11px; color: #666; margin-bottom: 4px;">
                        <span>💧 ${current.humidity}%</span>
                        <span>💨 ${current.wind_speed} km/h</span>
                        <span>🌧️ ${current.precipitation} mm</span>
                    </div>
                    <div style="display: flex; justify-content: center; font-size: 11px; color: #666; margin-bottom: 4px;">
                        <span>🌦️ ${current.precipitation_probability}%</span>
                    </div>
                </div>
                
                <!-- Next 4 Days Forecast -->
                <div style="margin-bottom: 6px;">
                    <div style="font-size: 11px; font-weight: bold; color: #333; margin-bottom: 3px;">Next 4 Days Forecast</div>
                    <div style="display: flex; justify-content: space-between; gap: 2px;">
        `;

        // Show next 4 days forecast
        for (let i = 1; i <= 4 && i < forecast.length; i++) {
            const day = forecast[i];
            html += `
                <div style="text-align: center; padding: 2px; background: #fff; border-radius: 3px; border: 1px solid #e0e0e0; flex: 1; min-width: 0;">
                    <div style="font-size: 9px; color: #666; margin-bottom: 1px;">${day.day_name}</div>
                    <div style="font-size: 16px; margin-bottom: 1px;">${day.weather_icon}</div>
                    <div style="font-size: 10px; font-weight: bold; color: #333;">${day.temp_max}°</div>
                    <div style="font-size: 8px; color: #666;">🌦️ ${day.precipitation_probability}%</div>
                </div>
            `;
        }

        html += `
                    </div>
                </div>
                
                <!-- Past 4 Days Weather -->
                <div style="margin-bottom: 6px;">
                    <div style="font-size: 11px; font-weight: bold; color: #333; margin-bottom: 3px;">Past 4 Days Weather</div>
                    <div style="display: flex; justify-content: space-between; gap: 2px;">
        `;

        // Show past 4 days (we'll use forecast data for demo, but in real implementation you'd have historical data)
        for (let i = 0; i < 4 && i < forecast.length; i++) {
            const day = forecast[i];
            html += `
                <div style="text-align: center; padding: 2px; background: #fff; border-radius: 3px; border: 1px solid #e0e0e0; flex: 1; min-width: 0;">
                    <div style="font-size: 9px; color: #666; margin-bottom: 1px;">${day.day_name}</div>
                    <div style="font-size: 16px; margin-bottom: 1px;">${day.weather_icon}</div>
                    <div style="font-size: 10px; font-weight: bold; color: #333;">${day.temp_max}°</div>
                    <div style="font-size: 8px; color: #666;">🌦️ ${day.precipitation_probability}%</div>
                </div>
            `;
        }

        html += `
                    </div>
                </div>
            </div>
        `;

        container.innerHTML = html;
    }
});
</script>

<style>
    .leaflet-popup {
        max-width: 500px !important;
    }
    .leaflet-popup-content-wrapper {
        border-radius: 8px;
        box-shadow: 0 3px 14px rgba(0,0,0,0.4);
        max-width: 500px !important;
    }
    .leaflet-popup-content {
        margin: 8px 12px;
        line-height: 1.3;
        word-wrap: break-word;
        max-width: 470px !important;
        max-height: 350px !important;
        overflow-y: auto;
    }
    .leaflet-popup-tip {
        max-width: 500px !important;
    }
    .custom-popup .leaflet-popup-content-wrapper {
        max-width: 500px !important;
        max-height: 400px !important;
    }
    .custom-popup .leaflet-popup-content {
        max-height: 350px !important;
        overflow-y: auto;
    }
    /* Responsive design for mobile devices */
    @media (max-width: 768px) {
        .leaflet-popup {
            max-width: 90vw !important;
        }
        .leaflet-popup-content-wrapper {
            max-width: 90vw !important;
        }
        .leaflet-popup-content {
            max-width: calc(90vw - 30px) !important;
            max-height: 300px !important;
        }
        .custom-popup .leaflet-popup-content-wrapper {
            max-width: 90vw !important;
            max-height: 350px !important;
        }
        .custom-popup .leaflet-popup-content {
            max-height: 300px !important;
        }
    }
    
    @media (max-width: 480px) {
        .leaflet-popup {
            max-width: 95vw !important;
        }
        .leaflet-popup-content-wrapper {
            max-width: 95vw !important;
        }
        .leaflet-popup-content {
            max-width: calc(95vw - 20px) !important;
            max-height: 250px !important;
        }
        .custom-popup .leaflet-popup-content-wrapper {
            max-width: 95vw !important;
            max-height: 300px !important;
        }
        .custom-popup .leaflet-popup-content {
            max-height: 250px !important;
        }
    }
</style>
@endpush