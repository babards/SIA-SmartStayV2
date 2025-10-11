@extends('layouts.app')

@section('content')

    <!-- Content offset due to fixed header -->
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">

                <!-- Property Info Card -->
                <div class="card shadow-sm mb-4">
                    @if($property->all_images && count($property->all_images) > 0)
                        <!-- Image Carousel -->
                        <div id="propertyImageCarousel" class="carousel slide" data-bs-ride="carousel">
                            <div class="carousel-inner">
                                @foreach($property->all_images as $index => $image)
                                    <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                                        <img src="{{ asset('storage/' . $image) }}" class="d-block w-100 rounded-top"
                                            style="object-fit:cover; max-height:320px;" alt="Property Image {{ $index + 1 }}">
                                    </div>
                                @endforeach
                            </div>
                            @if(count($property->all_images) > 1)
                                <button class="carousel-control-prev" type="button" data-bs-target="#propertyImageCarousel"
                                    data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Previous</span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#propertyImageCarousel"
                                    data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Next</span>
                                </button>

                                <div class="carousel-indicators">
                                    @foreach($property->all_images as $index => $image)
                                        <button type="button" data-bs-target="#propertyImageCarousel" data-bs-slide-to="{{ $index }}"
                                            class="{{ $index === 0 ? 'active' : '' }}"
                                            aria-current="{{ $index === 0 ? 'true' : 'false' }}"
                                            aria-label="Slide {{ $index + 1 }}"></button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @else
                        <img src="https://via.placeholder.com/600x320?text=No+Image" class="card-img-top rounded-top"
                            style="object-fit:cover; max-height:320px;">
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
                                                    @endif">
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

                <div class="container mt-4 mb-4">
                    <div class="d-flex justify-content-center gap-3">
                        <a href="{{ route('tenant.properties.index') }}" class="btn btn-secondary">
                            Back to Properties
                        </a>

                        @if($property->propertyStatus == 'Available')
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                data-bs-target="#applyPropertyModal">
                                Apply for this Property
                            </button>
                        @endif
                    </div>
                </div>

                <!-- Map -->
                <div class="mt-5">
                    <h3 class="text-center mb-4">Map Location</h3>
                    <div id="map" style="height: 500px; width: 100%; border-radius: 10px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="applyPropertyModal" tabindex="-1" aria-labelledby="applyPropertyModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('tenant.properties.apply', ['propertyId' => $property->propertyID]) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title w-100 text-center" id="applyPropertyModalLabel">Apply Property</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="d-flex justify-content-between mb-2 fw-bold">
                            <div>
                                {{ Auth::user()->name ?? Auth::user()->first_name . ' ' . Auth::user()->last_name }}<br>
                                <span class="fw-normal" style="font-size: 0.9em;">
                                    {{ Auth::user()->email }}<br>
                                </span>
                            </div>
                            <div class="text-end">
                                {{ $property->propertyName }}<br>
                                <span class="fw-normal" style="font-size: 0.9em;">
                                    {{ $property->landlord->first_name ?? '' }} {{ $property->landlord->last_name ?? '' }}<br>
                                    {{ $property->propertyLocation }}
                                </span>
                            </div>
                        </div>
                        <hr>
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

    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                var lat = {{ $property->latitude ?? 0 }};
                var lng = {{ $property->longitude ?? 0 }};

                var map = L.map('map').setView([lat, lng], 15);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '© OpenStreetMap contributors'
                }).addTo(map);

                // Reset Button
                L.Control.ResetView = L.Control.extend({
                    onAdd: function (map) {
                        const container = L.DomUtil.create('div', 'leaflet-bar leaflet-control leaflet-control-custom');
                        container.innerHTML = '<a href="#" title="Reset View">⌂</a>';
                        container.style.backgroundColor = 'white';
                        container.style.width = '30px';
                        container.style.height = '30px';
                        container.style.display = 'flex';
                        container.style.alignItems = 'center';
                        container.style.justifyContent = 'center';
                        container.onclick = function (e) {
                            e.preventDefault();
                            map.setView([lat, lng], 15);
                        }
                        return container;
                    },
                    onRemove: function (map) { }
                });

                new L.Control.ResetView({ position: 'topleft' }).addTo(map);

                L.marker([lat, lng]).addTo(map)
                    .bindPopup("{{ $property->propertyName }}")
                    .openPopup();
            });
        </script>
    @endpush
@endsection
