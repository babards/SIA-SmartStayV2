@extends('layouts.app')

@section('content')
    <div class="container">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Property Management</h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPropertyModal">
            Create New Property
        </button>
    </div>
    <div class="row align-items-end mb-3">
            <div class="col">
                <form method="GET" action="{{ route('landlord.properties.index') }}" class="mb-4 flex-grow-1 me-3">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <div class="input-group">
                                <input type="text" name="search" class="form-control" placeholder="Search properties..." value="{{ request('search') }}">
                                <button class="btn btn-primary" type="submit">Search</button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <select name="location_filter" class="form-select" onchange="this.form.submit()">
                                <option value="">All Locations</option>
                                @foreach(config('app.bukidnon_cities_municipalities') as $lgu)
                                    <option value="{{ $lgu }}" {{ request('location_filter') == $lgu ? 'selected' : '' }}>{{ $lgu }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="price_filter" class="form-select" onchange="this.form.submit()">
                                <option value="">All Prices</option>
                                <option value="below_1000" {{ request('price_filter') == 'below_1000' ? 'selected' : '' }}>Below ₱1,000</option>
                                <option value="1000_2000" {{ request('price_filter') == '1000_2000' ? 'selected' : '' }}>₱1,000 - ₱2,000</option>
                                <option value="2000_3000" {{ request('price_filter') == '2000_3000' ? 'selected' : '' }}>₱2,000 - ₱3,000</option>
                                <option value="above_3000" {{ request('price_filter') == 'above_3000' ? 'selected' : '' }}>Above ₱3,000</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <a href="{{ route('landlord.properties.index') }}" class="btn btn-outline-secondary w-100">Reset</a>
                        </div>
                    </div>
                </form>
            </div>

    <div class="row" id="property-list">
    @foreach ($properties as $property)
        <div class="col-md-3 mb-4" id="property-card-{{ $property->propertyID }}">
            <div class="card h-100 d-flex flex-column shadow-sm property-card">
                <a href="{{ route('landlord.properties.show', $property->propertyID) }}" style="text-decoration: none; color: inherit;">
                    @if ($property->main_image)
                        <img src="{{ asset('storage/' . $property->main_image) }}" class="card-img-top"
                            style="height: 160px; object-fit: cover;">
                    @else
                        <img src="https://via.placeholder.com/300x160?text=No+Image" class="card-img-top"
                            style="height: 160px; object-fit: cover;">
                    @endif
                    <div class="card-body flex-grow-1">
                        <h5 class="card-title">{{ $property->propertyName }}</h5>
                        <p class="card-text">{{ $property->propertyLocation }}</p>
                        <p class="card-text text-muted mb-1">₱{{ number_format($property->propertyRent, 2) }}</p>
                        @php
                            $statusDisplay = [
                                'Available' => 'Available',
                                'Fullyoccupied' => 'Fully Occupied',
                                'Maintenance' => 'Maintenance'
                            ];
                        @endphp
                        <p class="card-text text-muted mb-1">Status: 
                            {{ $statusDisplay[$property->propertyStatus] ?? $property->propertyStatus }}
                        </p>
                        @if ($property->number_of_boarders >= $property->vacancy)
                            <p class="card-text text-muted mb-1"><strong>Vacant:</strong> {{ $property->number_of_boarders ?? 0 }}/{{ $property->vacancy ?? 0 }} (Fully Occupied)</p>
                        @else
                            <p class="card-text text-muted mb-1"><strong>Vacant:</strong> {{ $property->number_of_boarders ?? 0 }}/{{ $property->vacancy ?? 0 }}</p>
                        @endif
                    </div>
                </a>
                <div class="card-footer bg-white border-0 d-flex justify-content-end gap-2 mt-auto">
                    <button class="btn btn-warning btn-sm editPropertyBtn" data-id="{{ $property->propertyID }}"
                        data-name="{{ $property->propertyName }}" data-description="{{ $property->propertyDescription }}"
                        data-location="{{ $property->propertyLocation }}" data-rent="{{ $property->propertyRent }}"
                        data-status="{{ $property->propertyStatus }}" data-latitude="{{ $property->latitude }}"
                        data-longitude="{{ $property->longitude }}" data-vacancy="{{ $property->vacancy }}"
                        data-bs-toggle="modal" data-bs-target="#editPropertyModal"
                        style="color:#000;">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-danger btn-sm deletePropertyBtn" data-id="{{ $property->propertyID }}"
                        data-name="{{ $property->propertyName }}" data-bs-toggle="modal" data-bs-target="#deletePropertyModal"
                        style="color:#fff;">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    @endforeach
    </div>

    @if ($properties->isEmpty())
    <div class="alert alert-info text-center">
        <i class="fas fa-info-circle me-2"></i>No properties found.
    </div>
    @endif

    @if($properties->hasPages())
        <div class="mt-3">
            {{ $properties->appends(request()->query())->links('pagination::bootstrap-5') }}
        </div>
    @endif
    </div>

    <!-- Properties Map Section -->
    <div class="row mt-5">
        <div class="col-12">
            <h3 class="text-center mb-4">Properties Map</h3>
            <div class="mt-4" id="propertiesMap" style="height: 500px; width: 100%; border-radius: 10px;"></div>
        </div>
    </div>

    <!-- Create Property Modal -->
    <div class="modal fade" id="createPropertyModal" tabindex="-1" aria-labelledby="createPropertyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form method="POST" action="{{ route('landlord.properties.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="createPropertyModalLabel">Create New Property - Select Location</h5>
                    </div>

                    <div class="modal-body">
                        <!-- Step 1: Map -->
                        <div id="mapStep">
                            <div id="map" style="height: 400px;"></div>
                            <input type="hidden" name="latitude" id="latitude">
                            <input type="hidden" name="longitude" id="longitude">
                            <div class="mb-3">
                                <label>Location</label>
                                <input type="text" name="propertyLocation" id="propertyLocation" class="form-control" required>
                            </div>
                        </div>

                        <!-- Step 2: Form -->
                        <div id="formStep" style="display: none;">
                            <div class="mb-3">
                                <label>Name</label>
                                <input type="text" name="propertyName" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Description</label>
                                <textarea name="propertyDescription" class="form-control"></textarea>
                            </div>
                            <div class="mb-3">
                                <label>Rent</label>
                                <input type="number" name="propertyRent" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Vacancy</label>
                                <input type="number" name="vacancy" id="createVacancy" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Status</label>
                                <input type="text" class="form-control" id="createPropertyStatusDisplay" value="Available" readonly>
                                <input type="hidden" name="propertyStatus" id="createPropertyStatus" value="Available">
                            </div>
                            <div class="mb-3">
                                <label>Images (Max 3)</label>
                                
                                <!-- Image Preview Container for Landlord Create -->
                                <div id="imagePreviewContainerLandlordCreate" class="mb-3">
                                    <div class="d-flex gap-2 flex-wrap">
                                        <!-- Will be populated by JavaScript when images are selected -->
                                    </div>
                                </div>
                                
                                <!-- Add Images -->
                                <input type="file" name="propertyImages[]" id="imageInputLandlordCreate" class="form-control" multiple accept="image/*" max="3">
                                <small class="form-text text-muted">You can select up to 3 images. The first image will be the main image.</small>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" id="cancelButtonCreate">Cancel</button>
                        <button type="button" class="btn btn-secondary" id="backButton" style="display: none;">Back</button>
                        <button type="button" class="btn btn-primary" id="nextButton">Next</button>
                        <button type="submit" class="btn btn-primary" id="submitButton"
                            style="display: none;">Create</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Property Modal -->
    <div class="modal fade" id="editPropertyModal" tabindex="-1" aria-labelledby="editPropertyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form method="POST" id="editPropertyForm" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <input type="hidden" name="form_type" value="edit">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editPropertyModalLabel">Edit Property - Update Location</h5>
                    </div>
                    <div class="modal-body">

                        {{-- Step 1: Location --}}
                        <div id="mapStepEdit">
                            <div id="editMap" style="height: 400px;"></div>
                            <input type="hidden" name="latitude" id="editLatitude">
                            <input type="hidden" name="longitude" id="editLongitude">
                            <div class="mb-3">
                                <label>Location</label>
                                <input type="text" name="propertyLocation" id="editPropertyLocation" class="form-control" required>
                            </div>
                        </div>

                        {{-- Step 2: Form --}}
                        <div id="formStepEdit" style="display: none;">
                            <input type="hidden" name="propertyID" id="editPropertyId">
                            <input type="hidden" name="redirect_to" value="index">
                            <div class="mb-3">
                                <label>Name</label>
                                <input type="text" name="propertyName" id="editPropertyName" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Description</label>
                                <textarea name="propertyDescription" id="editPropertyDescription" class="form-control"></textarea>
                            </div>
                            <div class="mb-3">
                                <label>Rent</label>
                                <input type="number" name="propertyRent" id="editPropertyRent" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Vacancy</label>
                                <input type="number" name="vacancy" id="editPropertyVacancy" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Status</label>
                                <input type="text" class="form-control" id="editPropertyStatusDisplay" readonly>
                                <input type="hidden" name="propertyStatus" id="editPropertyStatus">
                            </div>
                            <div class="mb-3">
                                <label>Images (Max 3)</label>
                                
                                <!-- Current Images Display -->
                                <div id="currentImagesEditLandlord" class="mb-3">
                                    <div class="d-flex gap-2 flex-wrap" id="imagePreviewContainerLandlord">
                                        <!-- Will be populated by JavaScript -->
                                    </div>
                                </div>
                                
                                <!-- Add Images -->
                                <input type="file" name="propertyImages[]" id="imageInputLandlord" class="form-control" multiple accept="image/*" max="3">
                                <small class="form-text text-muted">You can select up to 3 images. The first image will be the main image.</small>
                                
                                <!-- Hidden inputs for removed images -->
                                <div id="removedImagesInputsLandlord"></div>
                            </div>

                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" id="cancelButtonEdit">Cancel</button>
                        <button type="button" class="btn btn-secondary" id="backButtonEdit"
                            style="display: none;">Back</button>
                        <button type="button" class="btn btn-primary" id="nextButtonEdit">Next</button>
                        <button type="submit" class="btn btn-primary" id="submitButtonEdit"
                            style="display: none;">Update</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Property Modal -->
    <div class="modal fade" id="deletePropertyModal" tabindex="-1" aria-labelledby="deletePropertyModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="deletePropertyForm">
                @csrf
                @method('DELETE')
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deletePropertyModalLabel">Delete Property</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete property: <strong id="deletePropertyName"></strong>?</p>
                        <p>This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Property</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- JavaScript content would be similar but with property references instead of pad references -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            function reverseGeocode(lat, lng, inputId) {
                fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json`)
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.display_name) {
                            document.getElementById(inputId).value = data.display_name;
                        } else {
                            document.getElementById(inputId).value = '';
                            alert('Unable to fetch address. Please enter manually.');
                        }
                    })
                    .catch(() => {
                        document.getElementById(inputId).value = '';
                        alert('Failed to fetch address. Please enter manually.');
                    });
            }

            // Elements for Add New Property modal
            const mapStep = document.getElementById('mapStep');
            const formStep = document.getElementById('formStep');
            const nextButton = document.getElementById('nextButton');
            const backButton = document.getElementById('backButton');
            const submitButton = document.getElementById('submitButton');
            const createPropertyModalLabel = document.getElementById('createPropertyModalLabel');
            const createPropertyModal = document.getElementById('createPropertyModal');
            const cancelCreateBtn = document.getElementById('cancelButtonCreate');

            // Map and marker for Add New Property
            let map;
            let marker;

            if (createPropertyModal) {
                createPropertyModal.addEventListener('shown.bs.modal', function () {
                    if (!map) {
                        const defaultLatLng = [8.1574, 125.1278];
                        map = L.map('map', {
                            zoomControl: true,
                            zoomControlOptions: {
                                position: 'topright'
                            }
                        }).setView(defaultLatLng, 15);

                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '© OpenStreetMap contributors'
                        }).addTo(map);

                        // Add custom reset control
                        L.Control.ResetView = L.Control.extend({
                            onAdd: function(map) {
                                const container = L.DomUtil.create('div', 'leaflet-bar leaflet-control');
                                const link = L.DomUtil.create('a', 'leaflet-control-zoom-reset', container);
                                link.innerHTML = '⌂';
                                link.href = '#';
                                link.title = 'Reset View';
                                link.setAttribute('role', 'button');
                                link.setAttribute('aria-label', 'Reset View');
                                
                                L.DomEvent.on(link, 'click', function(e) {
                                    L.DomEvent.preventDefault(e);
                                    map.setView(defaultLatLng, 15);
                                });
                                
                                return container;
                            },
                            onRemove: function(map) {}
                        });

                        // Add reset control to map
                        new L.Control.ResetView({ position: 'topleft' }).addTo(map);

                        // Geocoder
                        L.Control.geocoder({
                            defaultMarkGeocode: false,
                            position: 'topright'
                        })
                            .on('markgeocode', function (e) {
                                const center = e.geocode.center;
                                map.setView(center, 15);

                                if (marker) map.removeLayer(marker);
                                marker = L.marker(center).addTo(map);

                                document.getElementById('latitude').value = center.lat;
                                document.getElementById('longitude').value = center.lng;

                                reverseGeocode(center.lat, center.lng, 'propertyLocation');
                            })
                            .addTo(map);

                        map.on('click', function (e) {
                            if (marker) map.removeLayer(marker);
                            marker = L.marker(e.latlng).addTo(map);

                            document.getElementById('latitude').value = e.latlng.lat;
                            document.getElementById('longitude').value = e.latlng.lng;

                            reverseGeocode(e.latlng.lat, e.latlng.lng, 'propertyLocation');
                        });
                    }
                    setTimeout(() => map.invalidateSize(), 100);
                });
            }

            // Navigation buttons for Add New Property modal
            if (nextButton && backButton && submitButton && mapStep && formStep && createPropertyModalLabel) {
                nextButton.addEventListener('click', function () {
                    const lat = document.getElementById('latitude').value;
                    const lng = document.getElementById('longitude').value;

                    if (!lat || !lng) {
                        alert('Please select a location on the map first.');
                        return;
                    }

                    mapStep.style.display = 'none';
                    formStep.style.display = 'block';
                    nextButton.style.display = 'none';
                    submitButton.style.display = 'inline-block';
                    backButton.style.display = 'inline-block';
                    createPropertyModalLabel.innerText = 'Add New Property - Fill Details';
                });

                backButton.addEventListener('click', function () {
                    mapStep.style.display = 'block';
                    formStep.style.display = 'none';
                    nextButton.style.display = 'inline-block';
                    submitButton.style.display = 'none';
                    backButton.style.display = 'none';
                    createPropertyModalLabel.innerText = 'Add New Property - Select Location';

                    if (map) {
                        setTimeout(() => map.invalidateSize(), 100);
                    }
                });
            }

            // Cancel button for New Property
            cancelCreateBtn.addEventListener('click', function () {
                // Reset map markers & view
                if (typeof map !== 'undefined') {
                    map.eachLayer(function (layer) {
                        if (layer instanceof L.Marker) {
                            map.removeLayer(layer);
                        }
                    });
                    const defaultLatLng = [8.1574, 125.1278];
                    map.setView(defaultLatLng, 15);
                }

                // Reset form fields (including hidden latitude, longitude, location input)
                document.getElementById('latitude').value = '';
                document.getElementById('longitude').value = '';
                document.getElementById('propertyLocation').value = '';

                // Reset status fields to default
                document.getElementById('createPropertyStatusDisplay').value = 'Available';
                document.getElementById('createPropertyStatus').value = 'Available';

                // Clear image previews for landlord create modal
                const imagePreviewContainerLandlordCreate = document.getElementById('imagePreviewContainerLandlordCreate');
                if (imagePreviewContainerLandlordCreate) {
                    imagePreviewContainerLandlordCreate.innerHTML = '<div class="d-flex gap-2 flex-wrap"></div>';
                }

                // Reset the whole form
                const form = createPropertyModal.querySelector('form');
                if (form) form.reset();

                // Reset step visibility and buttons to initial state
                document.getElementById('mapStep').style.display = 'block';
                document.getElementById('formStep').style.display = 'none';

                nextButton.style.display = 'inline-block';
                backButton.style.display = 'none';
                submitButton.style.display = 'none';

                createPropertyModalLabel.innerText = 'Add New Property - Select Location';

                // Hide the modal (Bootstrap 5)
                const modalInstance = bootstrap.Modal.getInstance(createPropertyModal);
                if (modalInstance) {
                    modalInstance.hide();
                }
            });

            // Add vacancy change listener for create modal
            const createVacancyInput = document.getElementById('createVacancy');
            const createPropertyStatusDisplay = document.getElementById('createPropertyStatusDisplay');
            const createPropertyStatusHidden = document.getElementById('createPropertyStatus');

            if (createVacancyInput && createPropertyStatusDisplay && createPropertyStatusHidden) {
                createVacancyInput.addEventListener('input', function () {
                    const vacancy = parseInt(createVacancyInput.value, 10);
                    if (vacancy === 0) {
                        createPropertyStatusDisplay.value = 'Fully Occupied';
                        createPropertyStatusHidden.value = 'Fullyoccupied';
                    } else {
                        createPropertyStatusDisplay.value = 'Available';
                        createPropertyStatusHidden.value = 'Available';
                    }
                });
            }

            // Edit Property Modal
            let originalEditPropertyData = {};
            const mapStepEdit = document.getElementById('mapStepEdit');
            const formStepEdit = document.getElementById('formStepEdit');
            const nextButtonEdit = document.getElementById('nextButtonEdit');
            const backButtonEdit = document.getElementById('backButtonEdit');
            const submitButtonEdit = document.getElementById('submitButtonEdit');
            const EditPropertyModalLabel = document.getElementById('editPropertyModalLabel');

            let editMap = null;
            let editMarker = null;

            function setMarkerOnMap(lat, lng) {
                if (!editMap) {
                    editMap = L.map('editMap', {
                        zoomControl: true,
                        zoomControlOptions: {
                            position: 'topright'
                        }
                    }).setView([lat, lng], 15);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© OpenStreetMap contributors'
                    }).addTo(editMap);

                    // Add custom reset control
                    L.Control.ResetView = L.Control.extend({
                        onAdd: function(map) {
                            const container = L.DomUtil.create('div', 'leaflet-bar leaflet-control');
                            const link = L.DomUtil.create('a', 'leaflet-control-zoom-reset', container);
                            link.innerHTML = '⌂';
                            link.href = '#';
                            link.title = 'Reset View';
                            link.setAttribute('role', 'button');
                            link.setAttribute('aria-label', 'Reset View');
                            
                            L.DomEvent.on(link, 'click', function(e) {
                                L.DomEvent.preventDefault(e);
                                map.setView([8.1574, 125.1278], 15);
                            });
                            
                            return container;
                        },
                        onRemove: function(map) {}
                    });

                    // Add reset control to map
                    new L.Control.ResetView({ position: 'topleft' }).addTo(editMap);

                    // Add geocoder control for searching locations in edit modal
                    L.Control.geocoder({
                        defaultMarkGeocode: false,
                        position: 'topright'
                    })
                        .on('markgeocode', function (e) {
                            const center = e.geocode.center;
                            editMap.setView(center, 15);
                            if (editMarker) editMap.removeLayer(editMarker);
                            editMarker = L.marker(center).addTo(editMap);
                            document.getElementById('editLatitude').value = center.lat;
                            document.getElementById('editLongitude').value = center.lng;
                            reverseGeocode(center.lat, center.lng, 'editPropertyLocation');
                        })
                        .addTo(editMap);

                    editMap.on('click', function (e) {
                        if (editMarker) editMap.removeLayer(editMarker);
                        editMarker = L.marker(e.latlng).addTo(editMap);

                        document.getElementById('editLatitude').value = e.latlng.lat;
                        document.getElementById('editLongitude').value = e.latlng.lng;
                        reverseGeocode(e.latlng.lat, e.latlng.lng, 'editPropertyLocation');
                    });
                    
                } else {
                    editMap.setView([lat, lng], 15);
                    if (editMarker) editMap.removeLayer(editMarker);
                    editMarker = L.marker([lat, lng]).addTo(editMap);
                }
                editMap.invalidateSize();
            }

            // Function to load current images for landlord editing
            function loadCurrentImagesLandlord(propertyId) {
                // Fetch current images from the server
                fetch(`/landlord/properties/${propertyId}/images`)
                    .then(response => response.json())
                    .then(data => {
                        const imagePreviewContainer = document.getElementById('imagePreviewContainerLandlord');
                        imagePreviewContainer.innerHTML = '';
                        
                        if (data.images && data.images.length > 0) {
                            data.images.forEach((image, index) => {
                                const imageDiv = document.createElement('div');
                                imageDiv.className = 'position-relative';
                                imageDiv.style.cssText = 'width: 100px; height: 100px;';
                                imageDiv.innerHTML = `
                                    <img src="/storage/${image}" class="img-fluid rounded" style="width: 100px; height: 100px; object-fit: cover;">
                                    <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 rounded-circle" 
                                            style="width: 25px; height: 25px; padding: 0; font-size: 12px; transform: translate(50%, -50%);"
                                            onclick="removeImageLandlord(${index}, '${image}')">
                                        ×
                                    </button>
                                    ${index === 0 ? '<small class="position-absolute bottom-0 start-0 bg-primary text-white px-1 rounded" style="font-size: 10px;">Main</small>' : ''}
                                `;
                                imagePreviewContainer.appendChild(imageDiv);
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error loading images:', error);
                    });
            }

            // Function to remove image for landlord
            window.removeImageLandlord = function(index, imagePath) {
                // Add hidden input to track removed images
                const removedImagesContainer = document.getElementById('removedImagesInputsLandlord');
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'removed_images[]';
                hiddenInput.value = imagePath;
                removedImagesContainer.appendChild(hiddenInput);
                
                // Remove the image preview
                event.target.parentElement.remove();
            }

            // When clicking edit buttons
            document.querySelectorAll('.editPropertyBtn').forEach(function (button) {
                button.addEventListener('click', function () {
                    originalEditPropertyData = {
                        id: this.dataset.id || '',
                        name: this.dataset.name || '',
                        description: this.dataset.description || '',
                        location: this.dataset.location || '',
                        rent: this.dataset.rent || '',
                        vacancy: this.dataset.vacancy || '',
                        status: this.dataset.status || '',
                        latitude: this.dataset.latitude || '',
                        longitude: this.dataset.longitude || ''
                    };

                    // Fill form fields
                    document.getElementById('editPropertyId').value = this.dataset.id || '';
                    document.getElementById('editPropertyName').value = this.dataset.name || '';
                    document.getElementById('editPropertyDescription').value = this.dataset.description || '';
                    document.getElementById('editPropertyLocation').value = this.dataset.location || '';
                    document.getElementById('editPropertyRent').value = this.dataset.rent || '';
                    document.getElementById('editPropertyVacancy').value = this.dataset.vacancy || '';
                    document.getElementById('editLatitude').value = this.dataset.latitude || '';
                    document.getElementById('editLongitude').value = this.dataset.longitude || '';
                    document.getElementById('editPropertyForm').action = '/landlord/properties/' + this.dataset.id;

                    // Set initial status based on vacancy
                    const vacancy = parseInt(this.dataset.vacancy || '0', 10);
                    if (vacancy === 0) {
                        document.getElementById('editPropertyStatusDisplay').value = 'Fully Occupied';
                        document.getElementById('editPropertyStatus').value = 'Fullyoccupied';
                    } else {
                        document.getElementById('editPropertyStatusDisplay').value = 'Available';
                        document.getElementById('editPropertyStatus').value = 'Available';
                    }

                    // Load current images
                    loadCurrentImagesLandlord(this.dataset.id);

                    const lat = parseFloat(this.dataset.latitude) || 7.9092;
                    const lng = parseFloat(this.dataset.longitude) || 125.0949;

                    // Show step 1 on open every time
                    mapStepEdit.style.display = 'block';
                    formStepEdit.style.display = 'none';
                    nextButtonEdit.style.display = 'inline-block';
                    backButtonEdit.style.display = 'none';
                    submitButtonEdit.style.display = 'none';

                    setTimeout(() => {
                        setMarkerOnMap(lat, lng);
                    }, 300);
                });
            });

            // Add vacancy change listener for edit modal
            const editVacancyInput = document.getElementById('editPropertyVacancy');
            const editPropertyStatusDisplay = document.getElementById('editPropertyStatusDisplay');
            const editPropertyStatusHidden = document.getElementById('editPropertyStatus');

            if (editVacancyInput && editPropertyStatusDisplay && editPropertyStatusHidden) {
                editVacancyInput.addEventListener('input', function () {
                    const vacancy = parseInt(editVacancyInput.value, 10);
                    if (vacancy === 0) {
                        editPropertyStatusDisplay.value = 'Fully Occupied';
                        editPropertyStatusHidden.value = 'Fullyoccupied';
                    } else {
                        editPropertyStatusDisplay.value = 'Available';
                        editPropertyStatusHidden.value = 'Available';
                    }
                });
            }

            const cancelEditBtn = document.getElementById('cancelButtonEdit');
            if (cancelEditBtn) {
                cancelEditBtn.addEventListener('click', function () {
                    // Reset form fields to original
                    document.getElementById('editPropertyId').value = originalEditPropertyData.id;
                    document.getElementById('editPropertyName').value = originalEditPropertyData.name;
                    document.getElementById('editPropertyDescription').value = originalEditPropertyData.description;
                    document.getElementById('editPropertyLocation').value = originalEditPropertyData.location;
                    document.getElementById('editPropertyRent').value = originalEditPropertyData.rent;
                    document.getElementById('editPropertyVacancy').value = originalEditPropertyData.vacancy;
                    document.getElementById('editPropertyStatus').value = originalEditPropertyData.status;
                    document.getElementById('editLatitude').value = originalEditPropertyData.latitude;
                    document.getElementById('editLongitude').value = originalEditPropertyData.longitude;

                    editPropertyModalLabel.innerText = 'Edit Property - Update Location';

                    // Close modal
                    var modal = bootstrap.Modal.getInstance(document.getElementById('editPropertyModal'));
                    if (modal) modal.hide();
                });
            }

            nextButtonEdit.addEventListener('click', function () {
                const locationVal = document.getElementById('editPropertyLocation').value.trim();
                if (!locationVal) {
                    alert('Please enter a location before proceeding.');
                    return;
                }
                mapStepEdit.style.display = 'none';
                formStepEdit.style.display = 'block';
                nextButtonEdit.style.display = 'none';
                backButtonEdit.style.display = 'inline-block';
                submitButtonEdit.style.display = 'inline-block';
                editPropertyModalLabel.innerText = 'Edit Property - Update Details';
            });

            backButtonEdit.addEventListener('click', function () {
                mapStepEdit.style.display = 'block';
                formStepEdit.style.display = 'none';

                nextButtonEdit.style.display = 'inline-block';
                backButtonEdit.style.display = 'none';
                submitButtonEdit.style.display = 'none';
                editPropertyModalLabel.innerText = 'Edit Property - Update Location';

                // When going back to step 1, show marker again
                const lat = parseFloat(document.getElementById('editLatitude').value) || 7.9092;
                const lng = parseFloat(document.getElementById('editLongitude').value) || 125.0949;
                setMarkerOnMap(lat, lng);
            });

            // Optional: Reset UI and marker when modal is shown (in case user closes and reopens)
            const editPropertyModal = document.getElementById('editPropertyModal');
            if (editPropertyModal) {
                editPropertyModal.addEventListener('show.bs.modal', () => {
                    // Reset steps and buttons to step 1
                    mapStepEdit.style.display = 'block';
                    formStepEdit.style.display = 'none';
                    nextButtonEdit.style.display = 'inline-block';
                    backButtonEdit.style.display = 'none';
                    submitButtonEdit.style.display = 'none';

                    // Reset marker to current lat/lng or default
                    const lat = parseFloat(document.getElementById('editLatitude').value) || 7.9092;
                    const lng = parseFloat(document.getElementById('editLongitude').value) || 125.0949;
                    setTimeout(() => {
                        setMarkerOnMap(lat, lng);
                    }, 300);
                });
            }

            // Delete Property buttons
            document.querySelectorAll('.deletePropertyBtn').forEach(function (button) {
                button.addEventListener('click', function () {
                    const nameElem = document.getElementById('deletePropertyName');
                    const formElem = document.getElementById('deletePropertyForm');
                    if (nameElem && formElem) {
                        nameElem.textContent = this.dataset.name;
                        formElem.action = '/landlord/properties/' + this.dataset.id;
                    }
                });
            });

        });

        // Handle image input preview for landlord edit modal
        const imageInputLandlord = document.getElementById('imageInputLandlord');
        if (imageInputLandlord) {
            imageInputLandlord.addEventListener('change', function(e) {
                const files = Array.from(e.target.files);
                const imagePreviewContainer = document.getElementById('imagePreviewContainerLandlord');
                
                // Count existing images (not new previews)
                const existingImages = imagePreviewContainer.querySelectorAll('div:not(.new-image-preview)').length;
                
                // Check if total would exceed 3
                if (existingImages + files.length > 3) {
                    showImageLimitModal(existingImages, files.length);
                    e.target.value = ''; // Clear the file input
                    return;
                }
                
                // Clear existing previews of new files
                const existingPreviews = imagePreviewContainer.querySelectorAll('.new-image-preview');
                existingPreviews.forEach(preview => preview.remove());
                
                files.forEach((file, index) => {
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const imageDiv = document.createElement('div');
                            imageDiv.className = 'position-relative new-image-preview';
                            imageDiv.style.cssText = 'width: 100px; height: 100px;';
                            imageDiv.innerHTML = `
                                <img src="${e.target.result}" class="img-fluid rounded" style="width: 100px; height: 100px; object-fit: cover;">
                                <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 rounded-circle" 
                                        style="width: 25px; height: 25px; padding: 0; font-size: 12px; transform: translate(50%, -50%);"
                                        onclick="removeNewImageLandlord(this)">
                                    ×
                                </button>
                                <small class="position-absolute bottom-0 start-0 bg-success text-white px-1 rounded" style="font-size: 10px;">New</small>
                            `;
                            imagePreviewContainer.appendChild(imageDiv);
                        };
                        reader.readAsDataURL(file);
                    }
                });
            });
        }

        // Handle image input preview for landlord create modal
        const imageInputLandlordCreate = document.getElementById('imageInputLandlordCreate');
        if (imageInputLandlordCreate) {
            imageInputLandlordCreate.addEventListener('change', function(e) {
                const files = Array.from(e.target.files);
                const imagePreviewContainer = document.getElementById('imagePreviewContainerLandlordCreate');
                
                // Check if total would exceed 3
                if (files.length > 3) {
                    showImageLimitModal(0, files.length);
                    e.target.value = ''; // Clear the file input
                    return;
                }
                
                // Clear existing previews
                imagePreviewContainer.innerHTML = '<div class="d-flex gap-2 flex-wrap"></div>';
                const flexContainer = imagePreviewContainer.querySelector('.d-flex');
                
                files.forEach((file, index) => {
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const imageDiv = document.createElement('div');
                            imageDiv.className = 'position-relative new-image-preview';
                            imageDiv.style.cssText = 'width: 100px; height: 100px;';
                            imageDiv.innerHTML = `
                                <img src="${e.target.result}" class="img-fluid rounded" style="width: 100px; height: 100px; object-fit: cover;">
                                <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 rounded-circle" 
                                        style="width: 25px; height: 25px; padding: 0; font-size: 12px; transform: translate(50%, -50%);"
                                        onclick="removeNewImageLandlordCreate(this)">
                                    ×
                                </button>
                            `;
                            flexContainer.appendChild(imageDiv);
                            
                            // Update badges after adding image
                            updateImageBadgesLandlordCreate();
                        };
                        reader.readAsDataURL(file);
                    }
                });
            });
        }

        // Function to remove new image preview for landlord
        window.removeNewImageLandlord = function(button) {
            button.parentElement.remove();
            // Reset file input
            document.getElementById('imageInputLandlord').value = '';
        }

        // Function to remove new image preview for landlord create modal
        window.removeNewImageLandlordCreate = function(button) {
            button.parentElement.remove();
            // Reset file input
            document.getElementById('imageInputLandlordCreate').value = '';
        }

        // Function to make image main for landlord create modal
        window.makeMainImageLandlordCreate = function(clickedImage) {
            const container = document.getElementById('imagePreviewContainerLandlordCreate').querySelector('.d-flex');
            const allImages = Array.from(container.querySelectorAll('.new-image-preview'));
            
            // Find the clicked image index
            const clickedIndex = allImages.indexOf(clickedImage);
            
            if (clickedIndex > 0) {
                // Remove clicked image from its current position
                const imageToMove = allImages[clickedIndex];
                container.removeChild(imageToMove);
                
                // Insert it at the beginning
                container.insertBefore(imageToMove, container.firstChild);
                
                // Update all badges
                updateImageBadgesLandlordCreate();
            }
        }

        // Function to update image badges for landlord create modal
        function updateImageBadgesLandlordCreate() {
            const container = document.getElementById('imagePreviewContainerLandlordCreate').querySelector('.d-flex');
            const allImages = container.querySelectorAll('.new-image-preview');
            
            allImages.forEach((imageDiv, index) => {
                // Remove existing badge
                const existingBadge = imageDiv.querySelector('small');
                if (existingBadge) {
                    existingBadge.remove();
                }
                
                // Add new badge
                if (index === 0) {
                    const mainBadge = document.createElement('small');
                    mainBadge.className = 'position-absolute bottom-0 start-0 bg-primary text-white px-1 rounded';
                    mainBadge.style.fontSize = '10px';
                    mainBadge.style.cursor = 'pointer';
                    mainBadge.textContent = 'Main';
                    mainBadge.onclick = function() { makeMainImageLandlordCreate(imageDiv); };
                    imageDiv.appendChild(mainBadge);
                } else {
                    const numberBadge = document.createElement('small');
                    numberBadge.className = 'position-absolute bottom-0 start-0 bg-secondary text-white px-1 rounded';
                    numberBadge.style.fontSize = '10px';
                    numberBadge.style.cursor = 'pointer';
                    numberBadge.textContent = index + 1;
                    numberBadge.onclick = function() { makeMainImageLandlordCreate(imageDiv); };
                    imageDiv.appendChild(numberBadge);
                }
            });
        }

        // Function to show image limit modal
        function showImageLimitModal(existingCount, selectedCount) {
            const modalHtml = `
                <div class="modal fade" id="imageLimitModal" tabindex="-1" aria-labelledby="imageLimitModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-sm">
                        <div class="modal-content border-0 shadow">
                            <div class="modal-header bg-warning text-white border-0">
                                <h6 class="modal-title" id="imageLimitModalLabel">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Image Limit Exceeded
                                </h6>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body text-center py-3">
                                <p class="mb-0">Maximum of 3 images allowed</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Remove existing modal if any
            const existingModal = document.getElementById('imageLimitModal');
            if (existingModal) {
                existingModal.remove();
            }
            
            // Add modal to body
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('imageLimitModal'));
            modal.show();
            
            // Remove modal from DOM after it's hidden
            document.getElementById('imageLimitModal').addEventListener('hidden.bs.modal', function() {
                this.remove();
            });
        }

        // Initialize Properties Map
        document.addEventListener("DOMContentLoaded", function () {
            // Initialize map with enhanced controls
            var propertiesMap = L.map('propertiesMap', {
                zoomControl: true,
                zoomControlOptions: {
                    position: 'topright'
                }
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(propertiesMap);

            // Store all markers for bounds calculation
            var allMarkers = [];
            var markerGroup = L.layerGroup().addTo(propertiesMap);

            // Add markers for each property
            @foreach ($properties as $property)
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
                                <a href="{{ route('landlord.properties.show', $property->propertyID) }}" class="btn btn-outline-secondary btn-sm" target="_blank" style="border: 1px solid #6c757d; color: #6c757d; background: transparent; text-decoration: none; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
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
                    propertiesMap.fitBounds(group.getBounds().pad(0.1));
                } else {
                    // If no markers, center on Malaybalay City
                    propertiesMap.setView([8.1574, 125.1278], 15);
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
            new L.Control.ResetView({ position: 'topleft' }).addTo(propertiesMap);

            // Add geocoder control for searching locations
            L.Control.geocoder({
                defaultMarkGeocode: false,
                position: 'topright'
            })
                .on('markgeocode', function (e) {
                    const center = e.geocode.center;
                    propertiesMap.setView(center, 15);
                })
                .addTo(propertiesMap);

        // Weather data functionality
        function loadWeatherData(propertyId) {
            const weatherDiv = document.getElementById(`weather-${propertyId}`);
            if (!weatherDiv) return;

            // Show loading state
            weatherDiv.innerHTML = '<div class="weather-loading">Loading weather data...</div>';

            // Fetch weather data
            fetch(`/landlord/properties/${propertyId}/weather`)
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

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
@endsection

@push('scripts')
    <style>
        .property-img {
            width: 100%;
            height: 160px;
            object-fit: cover;
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
        
        /* Properties Map Styles */
        #propertiesMap {
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 10px;
        }
        
        /* Popup Styles */
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
        .leaflet-popup-content {
            margin: 10px;
        }
        .leaflet-popup-content h5 {
            margin-bottom: 5px;
        }
        .leaflet-popup-content p {
            margin-bottom: 5px;
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
            margin: 10px 15px;
            line-height: 1.4;
            word-wrap: break-word;
            max-width: 470px !important;
        }
        .leaflet-popup-tip {
            max-width: 500px !important;
        }
        .property-card {
            transition: transform 0.2s ease-in-out;
        }
        .property-card:hover {
            transform: translateY(-2px);
        }
    </style>
@endpush
