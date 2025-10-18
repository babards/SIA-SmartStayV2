@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <h2 class="mb-4">Admin Dashboard</h2>
            </div>
        </div>

        <div class="row">
            <!-- User Statistics -->
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">User Statistics</h5>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Total Users:</span>
                            <span class="fw-bold">{{ $stats['total_users'] }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Landlords:</span>
                            <span class="fw-bold">{{ $stats['total_landlords'] }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Tenants:</span>
                            <span class="fw-bold">{{ $stats['total_tenants'] }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Property Statistics -->
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Property Statistics</h5>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Total Properties:</span>
                            <span class="fw-bold">{{ $stats['total_properties'] }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Available Properties:</span>
                            <span class="fw-bold">{{ $stats['available_properties'] }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Application Statistics -->
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Application Statistics</h5>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Total Applications:</span>
                            <span class="fw-bold">{{ $stats['total_applications'] }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Pending Applications:</span>
                            <span class="fw-bold">{{ $stats['pending_applications'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    

        <h3 class="text-center mb-4">Properties Map</h3>
   
    <div class="mt-4" id="map" style="height: 600px;"></div>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Initialize map with enhanced controls
            var map = L.map('map', {
                zoomControl: true,
                zoomControlOptions: {
                    position: 'topright'
                }
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            // Store all markers for bounds calculation
            var allMarkers = [];
            var markerGroup = L.layerGroup().addTo(map);

            @foreach ($properties as $property)
                @if($property->latitude && $property->longitude)
                    const marker{{ $property->propertyID }} = L.marker([{{ $property->latitude }}, {{ $property->longitude }}]);
                    const popupContent{{ $property->propertyID }} = `
                        <div style="min-width: 300px; max-width: 350px;">
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
                                <a href="{{ route('admin.properties.show', $property->propertyID) }}" class="btn btn-outline-secondary btn-sm" target="_blank" style="border: 1px solid #6c757d; color: #6c757d; background: transparent; text-decoration: none; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
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

            // Weather data loading function
            function loadWeatherData(propertyId) {
                const container = document.getElementById(`weather-${propertyId}`);
                if (!container) return;

                // Show loading state
                container.innerHTML = '<div class="weather-loading">Loading weather data...</div>';

                fetch(`/properties/${propertyId}/weather`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            displayWeatherData(container, data.data);
                        } else {
                            container.innerHTML = '<div style="color: #666; font-size: 12px;">Weather data unavailable</div>';
                        }
                    })
                    .catch(error => {
                        console.error('Weather data error:', error);
                        container.innerHTML = '<div style="color: #666; font-size: 12px;">Weather data unavailable</div>';
                    });
            }

            // Display weather data function
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

                // Show past 4 days historical data
                const historical = weatherData.historical || [];
                for (let i = 0; i < 4 && i < historical.length; i++) {
                    const day = historical[i];
                    html += `
                        <div style="text-align: center; padding: 2px; background: #fff; border-radius: 3px; border: 1px solid #e0e0e0; flex: 1; min-width: 0;">
                            <div style="font-size: 9px; color: #666; margin-bottom: 1px;">${day.day_name}</div>
                            <div style="font-size: 16px; margin-bottom: 1px;">${day.weather_icon}</div>
                            <div style="font-size: 10px; font-weight: bold; color: #333;">${day.temp_max}°</div>
                            <div style="font-size: 8px; color: #666;">🌧️ ${day.precipitation}mm</div>
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
        #map {
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 10px;
        }
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
        .leaflet-popup-content h5 {
            margin-bottom: 5px;
        }
        .leaflet-popup-content p {
            margin-bottom: 5px;
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

@endsection