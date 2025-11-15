@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">My Applications</h2>
    </div>

    <!-- Filter Bar -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Search applications..." value="{{ request('search') }}">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="property_filter" class="form-select" onchange="this.form.submit()">
                        <option value="">All Properties</option>
                        @foreach($applications->pluck('property.propertyName')->unique() as $propertyName)
                            <option value="{{ $propertyName }}" {{ request('property_filter') == $propertyName ? 'selected' : '' }}>{{ $propertyName }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="status_filter" class="form-select" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="approved" {{ request('status_filter') == 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="pending" {{ request('status_filter') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="rejected" {{ request('status_filter') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                        <option value="cancelled" {{ request('status_filter') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('tenant.applications.index') }}" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-undo me-1"></i>Reset Filters
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    @if($applications->count())
        <!-- Applications Grid -->
        <div class="row">
            @foreach($applications as $application)
                <div class="col-lg-6 col-xl-4 mb-4">
                    <div class="card h-100 shadow-sm border-0" style="border-radius: 12px;">
                        <div class="card-body">
                            <!-- Header with Property Name and Status -->
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h5 class="card-title mb-0 flex-grow-1" style="word-break: break-word; padding-right: 10px;">
                                    {{ $application->property->propertyName ?? 'N/A' }}
                                </h5>
                                <span class="badge 
                                    @if($application->status == 'approved') bg-success
                                    @elseif($application->status == 'rejected') bg-danger
                                    @elseif($application->status == 'pending') bg-warning text-dark
                                    @else bg-secondary
                                    @endif
                                    rounded-pill px-3 py-2 flex-shrink-0">
                                    {{ ucfirst($application->status) }}
                                </span>
                            </div>

                            <!-- Property Location -->
                            <div class="mb-3">
                                <div class="d-flex align-items-start">
                                    <i class="fas fa-map-marker-alt text-muted me-2 mt-1"></i>
                                    <span class="text-muted" style="word-break: break-word;">{{ $application->property->propertyLocation ?? 'N/A' }}</span>
                                </div>
                            </div>

                            <!-- Landlord Information -->
                            <div class="mb-3">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-user-tie text-muted me-2"></i>
                                    <span class="text-muted">{{ $application->property->landlord->first_name ?? 'N/A' }} {{ $application->property->landlord->last_name ?? '' }}</span>
                                </div>
                            </div>

                            <!-- Landlord Phone Number -->
                            <div class="mb-3">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-phone text-muted me-2"></i>
                                    <span class="text-muted">{{ $application->property->landlord->phone_number ?? 'N/A' }}</span>
                                </div>
                            </div>

                            <!-- Application Date -->
                            <div class="mb-3">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-calendar text-muted me-2"></i>
                                    <span class="text-muted">{{ $application->application_date->format('M j, Y') }}</span>
                                </div>
                            </div>

                            <!-- Message -->
                            <div class="mb-3">
                                <div class="d-flex align-items-start">
                                    <i class="fas fa-comment text-muted me-2 mt-1"></i>
                                    <div>
                                        <small class="text-muted">Your Message:</small>
                                        <p class="mb-0 text-truncate" style="max-height: 60px; overflow: hidden;">
                                            {{ $application->message ?? 'No message provided' }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="mt-auto">
                                @if($application->status == 'pending')
                                    <button type="button" class="btn btn-danger w-100 rounded-pill" data-bs-toggle="modal" data-bs-target="#cancelModal{{ $application->id }}">
                                        <i class="fas fa-times me-1"></i>Cancel Application
                                    </button>
                                @elseif($application->status == 'approved')
                                    <!-- Actions for approved applications -->
                                    <button type="button" class="btn btn-info w-100 rounded-pill" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#weatherModal{{ $application->id }}"
                                            onclick="loadWeatherForApplication({{ $application->property->propertyID }}, {{ $application->id }})">
                                        <i class="fas fa-cloud-sun me-1"></i>View Weather Data
                                    </button>
                                @else
                                    <div class="text-center">
                                        <span class="text-muted">
                                            <i class="fas fa-info-circle me-1"></i>No actions available
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cancel Modal for each application -->
                @if($application->status == 'pending')
                <div class="modal fade" id="cancelModal{{ $application->id }}" tabindex="-1" aria-labelledby="cancelModalLabel{{ $application->id }}" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
                            <div class="modal-header border-0 pb-0">
                                <h5 class="modal-title" id="cancelModalLabel{{ $application->id }}">
                                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>Cancel Application
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body pt-0">
                                <div class="alert alert-warning border-0" style="border-radius: 8px; background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);">
                                    <strong>Are you sure you want to cancel this application?</strong>
                                </div>
                                <div class="card border-0" style="background: #f8f9fa;">
                                    <div class="card-body">
                                        <p class="mb-2"><strong>Property:</strong> {{ $application->property->propertyName ?? 'N/A' }}</p>
                                        <p class="mb-2"><strong>Location:</strong> {{ $application->property->propertyLocation ?? 'N/A' }}</p>
                                        <p class="mb-0"><strong>Application Date:</strong> {{ $application->application_date->format('F j, Y') }}</p>
                                    </div>
                                </div>
                                <p class="text-muted mt-3 mb-0">This action cannot be undone. You will need to submit a new application if you change your mind.</p>
                            </div>
                            <div class="modal-footer border-0 pt-0">
                                <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-1"></i>Keep Application
                                </button>
                                <form action="{{ route('tenant.applications.cancel', $application->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-danger rounded-pill">
                                        <i class="fas fa-trash me-1"></i>Yes, Cancel Application
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                
                <!-- Weather Modal for approved applications -->
                @if($application->status == 'approved')
                <div class="modal fade" id="weatherModal{{ $application->id }}" tabindex="-1" aria-labelledby="weatherModalLabel{{ $application->id }}" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="weatherModalLabel{{ $application->id }}">
                                    <i class="fas fa-cloud-sun me-2"></i>Weather Data - {{ $application->property->propertyName }}
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <!-- Current Weather Summary -->
                                <div id="current-weather-summary-{{ $application->id }}" class="mb-4" style="display: none;">
                                    <div class="alert alert-info">
                                        <h6 class="alert-heading mb-2">
                                            <i class="fas fa-cloud-sun me-2"></i>Current Weather Summary
                                        </h6>
                                        <div id="current-weather-content-{{ $application->id }}"></div>
                                    </div>
                                </div>
                                
                                <!-- Historical Weather Data -->
                                <div id="historical-weather-container-{{ $application->id }}">
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
                @endif
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-4">
            {{ $applications->links('pagination::bootstrap-5') }}
        </div>
    @else
        <!-- No Results Message -->
        <div class="text-center py-5">
            <div class="card border-0 shadow-sm" style="border-radius: 12px; background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);">
                <div class="card-body py-5">
                    <i class="fas fa-file-alt text-primary mb-3" style="font-size: 3rem;"></i>
                    <h4 class="text-primary mb-2">No Applications Found</h4>
                    <p class="text-muted mb-3">You haven't applied for any properties yet.</p>
                    <a href="{{ route('tenant.properties.index') }}" class="btn btn-primary rounded-pill">
                        <i class="fas fa-search me-1"></i>Browse Properties
                    </a>
                </div>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
// Global function to load weather data for a specific application
function loadWeatherForApplication(propertyId, applicationId) {
    // Load both current and historical weather data
    loadCurrentWeatherForApplication(propertyId, applicationId);
    loadHistoricalWeatherForApplication(propertyId, applicationId);
}

// Load current weather data
function loadCurrentWeatherForApplication(propertyId, applicationId) {
    const container = document.getElementById(`current-weather-content-${applicationId}`);
    const summaryDiv = document.getElementById(`current-weather-summary-${applicationId}`);
    if (!container) return;

    fetch(`/properties/${propertyId}/weather`)
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
function loadHistoricalWeatherForApplication(propertyId, applicationId) {
    const container = document.getElementById(`historical-weather-container-${applicationId}`);
    if (!container) return;

    fetch(`/properties/${propertyId}/historical-weather`)
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

// Make toggleDailyView globally accessible
window.toggleDailyView = function(monthId) {
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
</script>
@endpush

@push('styles')
<style>
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
    
    /* Modal Styles */
    .modal-xl {
        max-width: 1200px;
    }
    
    .modal-body {
        max-height: 85vh;
        overflow-y: auto;
    }
</style>
@endpush
@endsection
