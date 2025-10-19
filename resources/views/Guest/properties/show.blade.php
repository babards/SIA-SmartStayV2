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
                                <!-- Action Buttons at the top -->
                                <div class="d-flex gap-2 mb-4">
                                    <a href="{{ route('welcome') }}" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left me-1"></i>Back to Listings
                                    </a>
                                    @if($property->propertyStatus == 'Available')
                                        @guest
                                            <a href="{{ route('register') }}" class="btn btn-primary">
                                                <i class="fas fa-file-alt me-1"></i>Apply for this Property
                                            </a>
                                        @else
                                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#applyPropertyModal">
                                                <i class="fas fa-file-alt me-1"></i>Apply for this Property
                                            </button>
                                        @endguest
                                    @endif
                                    <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#weatherDataModal" id="weatherDataBtn">
                                        <i class="fas fa-cloud-sun me-1"></i>View Weather Data
                                    </button>
                                </div>
                                
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
                
                <!-- Weather Data Modal -->
                <div class="modal fade" id="weatherDataModal" tabindex="-1" aria-labelledby="weatherDataModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-xl">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="weatherDataModalLabel">
                                    <i class="fas fa-cloud-sun me-2"></i>Weather Data for {{ $property->propertyName }}
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <!-- Current Weather Summary -->
                                <div id="current-weather-summary" class="mb-4" style="display: none;">
                                    <div class="alert alert-info">
                                        <h6 class="alert-heading mb-2">
                                            <i class="fas fa-cloud-sun me-2"></i>Current Weather Summary
                                        </h6>
                                        <div id="current-weather-content"></div>
                                    </div>
                                </div>
                                
                                <!-- Historical Weather Data -->
                                <div id="historical-weather-container">
                                    <div class="text-center py-4">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <p class="mt-2 text-muted">Loading weather data...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

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
    
    /* Historical Weather Styles */
    .historical-weather-content .card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    
    .historical-weather-content .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.1) !important;
    }
    
    .historical-weather-content .card-header {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%) !important;
        border-bottom: 1px solid #dee2e6;
    }
    
    .historical-weather-content .bg-light {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%) !important;
        border: 1px solid #dee2e6;
    }
    
    /* Daily Weather View Styles */
    .daily-weather-grid {
        border: 1px solid #e9ecef;
        border-radius: 8px;
        background: #fff;
    }
    
    .daily-weather-item {
        transition: background-color 0.2s ease;
        padding: 8px 12px !important;
    }
    
    .daily-weather-item:hover {
        background-color: #f8f9fa;
    }
    
    .daily-weather-item:last-child {
        border-bottom: none !important;
    }
    
    .daily-weather-item .small {
        font-size: 0.75rem;
    }
    
    /* Forecast Grid Styles */
    .forecast-grid {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    
    .forecast-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 12px;
        background: #f8f9fa;
        border-radius: 6px;
        border: 1px solid #e9ecef;
        transition: all 0.2s ease;
    }
    
    .forecast-item:hover {
        background: #e9ecef;
        transform: translateX(2px);
    }
    
    .forecast-item .small {
        font-size: 0.7rem;
    }
    
    /* Current Weather Summary Styles */
    .current-weather-summary .bg-light {
        background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%) !important;
        border: 1px solid #bbdefb;
    }
    
    /* Enhanced Daily Weather Items */
    .daily-weather-item .gap-2 {
        gap: 0.5rem !important;
    }
    
    /* Action Buttons Styles */
    .card-body .d-flex.gap-2 .btn {
        flex: 1;
        min-width: 0;
        white-space: nowrap;
        transition: all 0.3s ease;
    }
    
    .card-body .d-flex.gap-2 .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    
    .card-body .d-flex.gap-2 .btn i {
        font-size: 0.9rem;
    }
    
    /* Modal Styles */
    .modal-xl {
        max-width: 1200px;
    }
    
    .modal-body {
        max-height: 70vh;
        overflow-y: auto;
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
        
        /* Mobile button adjustments */
        .card-body .d-flex.gap-2 {
            flex-direction: column;
            gap: 0.5rem !important;
        }
        
        .card-body .d-flex.gap-2 .btn {
            flex: none;
            width: 100%;
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

        // Load both current and historical weather data
        function loadWeatherData() {
            // Load current weather data
            loadCurrentWeatherData();
            // Load historical weather data
            loadHistoricalWeatherData();
        }

        // Load current weather data
        function loadCurrentWeatherData() {
            const container = document.getElementById('current-weather-content');
            const summaryDiv = document.getElementById('current-weather-summary');
            if (!container) return;

            fetch(`/properties/{{ $property->propertyID }}/weather`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayCurrentWeatherData(container, data.data);
                        summaryDiv.style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Current weather fetch error:', error);
                });
        }

        // Load historical weather data
        function loadHistoricalWeatherData() {
            const container = document.getElementById('historical-weather-container');
            if (!container) return;

            fetch(`/properties/{{ $property->propertyID }}/historical-weather`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayHistoricalWeatherData(container, data.data);
                    } else {
                        container.innerHTML = `
                            <div class="alert alert-warning text-center">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Unable to load historical weather data
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Historical weather fetch error:', error);
                    container.innerHTML = `
                        <div class="alert alert-danger text-center">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            Error loading historical weather data
                        </div>
                    `;
                });
        }

        function displayCurrentWeatherData(container, weatherData) {
            const current = weatherData.current;
            const forecast = weatherData.forecast;
            const historical = weatherData.historical || [];

            let html = `
                <div class="row">
                    <!-- Current Weather -->
                    <div class="col-md-4 mb-3">
                        <div class="text-center p-3 bg-light rounded">
                            <div style="font-size: 2.5rem; margin-bottom: 8px;">${current.weather_icon}</div>
                            <div class="h4 mb-1">${current.temperature}°C</div>
                            <div class="small text-muted mb-2">${current.weather_description}</div>
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="small text-muted">💧 Humidity</div>
                                    <div class="fw-bold">${current.humidity}%</div>
                                </div>
                                <div class="col-6">
                                    <div class="small text-muted">💨 Wind</div>
                                    <div class="fw-bold">${current.wind_speed} km/h</div>
                                </div>
                            </div>
                            <div class="row text-center mt-2">
                                <div class="col-6">
                                    <div class="small text-muted">🌧️ Rain</div>
                                    <div class="fw-bold">${current.precipitation} mm</div>
                                </div>
                                <div class="col-6">
                                    <div class="small text-muted">🌦️ Rain Chance</div>
                                    <div class="fw-bold">${current.precipitation_probability}%</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Next 4 Days Forecast -->
                    <div class="col-md-4 mb-3">
                        <h6 class="small text-muted mb-2">Next 4 Days Forecast</h6>
                        <div class="forecast-grid">
            `;

            // Show next 4 days forecast
            for (let i = 0; i < 4 && i < forecast.length; i++) {
                const day = forecast[i];
                html += `
                    <div class="forecast-item">
                        <div class="small text-muted">${day.day_name}</div>
                        <div style="font-size: 1.5rem; margin: 4px 0;">${day.weather_icon}</div>
                        <div class="fw-bold">${day.temp_max}°/${day.temp_min}°</div>
                        <div class="small text-muted">🌦️ ${day.precipitation_probability}%</div>
                    </div>
                `;
            }

            html += `
                        </div>
                    </div>
                    
                    <!-- Past 4 Days Weather -->
                    <div class="col-md-4 mb-3">
                        <h6 class="small text-muted mb-2">Past 4 Days Weather</h6>
                        <div class="forecast-grid">
            `;

            // Show past 4 days historical data
            for (let i = 0; i < 4 && i < historical.length; i++) {
                const day = historical[i];
                html += `
                    <div class="forecast-item">
                        <div class="small text-muted">${day.day_name}</div>
                        <div style="font-size: 1.5rem; margin: 4px 0;">${day.weather_icon}</div>
                        <div class="fw-bold">${day.temp_max}°/${day.temp_min}°</div>
                        <div class="small text-muted">🌧️ ${day.precipitation}mm</div>
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

        function displayHistoricalWeatherData(container, historicalData) {
            if (!historicalData || historicalData.length === 0) {
                container.innerHTML = `
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle me-2"></i>
                        No historical weather data available
                    </div>
                `;
                return;
            }

            // Group data by month for better organization
            const monthlyData = {};
            historicalData.forEach(day => {
                const date = new Date(day.date);
                const monthKey = date.toLocaleDateString('en-US', { year: 'numeric', month: 'long' });
                
                if (!monthlyData[monthKey]) {
                    monthlyData[monthKey] = [];
                }
                monthlyData[monthKey].push(day);
            });

            let html = `
                <div class="historical-weather-content">
                    <div class="row">
            `;

            // Display each month's data
            Object.keys(monthlyData).forEach(month => {
                const monthData = monthlyData[month];
                const avgTemp = Math.round(monthData.reduce((sum, day) => sum + day.temp_max, 0) / monthData.length);
                const totalPrecip = monthData.reduce((sum, day) => sum + day.precipitation, 0).toFixed(1);
                const mostCommonWeather = getMostCommonWeather(monthData);
                const monthId = month.replace(/\s+/g, '-').toLowerCase();

                html += `
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                <h6 class="card-title mb-0">${month}</h6>
                                <button class="btn btn-sm btn-outline-primary" onclick="toggleDailyView('${monthId}')" id="toggle-${monthId}">
                                    <i class="fas fa-calendar-day"></i> Daily
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="text-center mb-3">
                                    <div style="font-size: 2rem; margin-bottom: 8px;">${mostCommonWeather.icon}</div>
                                    <div class="h5 mb-1">${avgTemp}°C</div>
                                    <div class="text-muted small">${mostCommonWeather.description}</div>
                                </div>
                                
                                <div class="row text-center">
                                    <div class="col-6">
                                        <div class="small text-muted">Total Rain</div>
                                        <div class="fw-bold">${totalPrecip}mm</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="small text-muted">Days</div>
                                        <div class="fw-bold">${monthData.length}</div>
                                    </div>
                                </div>
                                
                                <div class="mt-3">
                                    <div class="small text-muted mb-2">Temperature Range</div>
                                    <div class="d-flex justify-content-between small">
                                        <span>Min: ${Math.min(...monthData.map(d => d.temp_min))}°C</span>
                                        <span>Max: ${Math.max(...monthData.map(d => d.temp_max))}°C</span>
                                    </div>
                                </div>
                                
                                <!-- Daily View (Initially Hidden) -->
                                <div id="daily-${monthId}" class="mt-3" style="display: none;">
                                    <div class="border-top pt-3">
                                        <h6 class="small text-muted mb-2">Daily Breakdown</h6>
                                        <div class="daily-weather-grid" style="max-height: 200px; overflow-y: auto;">
                                            ${generateDailyView(monthData)}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });

            html += `
                    </div>
                    
                    <!-- Summary Statistics -->
                    <div class="mt-4">
                        <h6 class="mb-3">Summary Statistics (Past 3 Months)</h6>
                        <div class="row">
                            <div class="col-md-3 col-6 mb-3">
                                <div class="text-center p-3 bg-light rounded">
                                    <div class="h5 mb-1 text-primary">${Math.round(historicalData.reduce((sum, day) => sum + day.temp_max, 0) / historicalData.length)}°C</div>
                                    <div class="small text-muted">Avg High Temp</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <div class="text-center p-3 bg-light rounded">
                                    <div class="h5 mb-1 text-info">${Math.round(historicalData.reduce((sum, day) => sum + day.temp_min, 0) / historicalData.length)}°C</div>
                                    <div class="small text-muted">Avg Low Temp</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <div class="text-center p-3 bg-light rounded">
                                    <div class="h5 mb-1 text-success">${historicalData.reduce((sum, day) => sum + day.precipitation, 0).toFixed(1)}mm</div>
                                    <div class="small text-muted">Total Rainfall</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <div class="text-center p-3 bg-light rounded">
                                    <div class="h5 mb-1 text-warning">${historicalData.length}</div>
                                    <div class="small text-muted">Days Recorded</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            container.innerHTML = html;
        }

        function getMostCommonWeather(monthData) {
            const weatherCounts = {};
            monthData.forEach(day => {
                const key = day.weather_code;
                weatherCounts[key] = (weatherCounts[key] || 0) + 1;
            });

            const mostCommon = Object.keys(weatherCounts).reduce((a, b) => 
                weatherCounts[a] > weatherCounts[b] ? a : b
            );

            const day = monthData.find(d => d.weather_code == mostCommon);
            return {
                icon: day.weather_icon,
                description: day.weather_description
            };
        }

        function generateDailyView(monthData) {
            return monthData.map(day => {
                const date = new Date(day.date);
                const dayOfWeek = date.toLocaleDateString('en-US', { weekday: 'short' });
                const dayOfMonth = date.getDate();
                
                return `
                    <div class="daily-weather-item d-flex justify-content-between align-items-center py-2 border-bottom">
                        <div class="d-flex align-items-center">
                            <span class="me-2" style="font-size: 1.2rem;">${day.weather_icon}</span>
                            <div>
                                <div class="small fw-bold">${dayOfWeek} ${dayOfMonth}</div>
                                <div class="small text-muted">${day.weather_description}</div>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="small fw-bold">${day.temp_max}°/${day.temp_min}°</div>
                            <div class="small text-muted d-flex justify-content-end gap-2">
                                <span>🌧️ ${day.precipitation}mm</span>
                                ${day.humidity ? `<span>💧 ${day.humidity}%</span>` : ''}
                                ${day.wind_speed ? `<span>💨 ${day.wind_speed}km/h</span>` : ''}
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function toggleDailyView(monthId) {
            const dailyView = document.getElementById(`daily-${monthId}`);
            const toggleBtn = document.getElementById(`toggle-${monthId}`);
            
            if (dailyView.style.display === 'none') {
                dailyView.style.display = 'block';
                toggleBtn.innerHTML = '<i class="fas fa-eye-slash"></i> Hide';
                toggleBtn.classList.remove('btn-outline-primary');
                toggleBtn.classList.add('btn-primary');
            } else {
                dailyView.style.display = 'none';
                toggleBtn.innerHTML = '<i class="fas fa-calendar-day"></i> Daily';
                toggleBtn.classList.remove('btn-primary');
                toggleBtn.classList.add('btn-outline-primary');
            }
        }

        // Load weather data when modal is opened
        document.getElementById('weatherDataModal').addEventListener('shown.bs.modal', function () {
            loadWeatherData();
        });
        
        // Show loading state on button when modal is opening
        document.getElementById('weatherDataModal').addEventListener('show.bs.modal', function () {
            const btn = document.getElementById('weatherDataBtn');
            const originalContent = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Loading Weather Data...';
            btn.disabled = true;
            
            // Reset button after modal is shown
            setTimeout(() => {
                btn.innerHTML = originalContent;
                btn.disabled = false;
            }, 1000);
        });
    </script>
@endpush
