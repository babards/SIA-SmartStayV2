@extends('layouts.app')

@section('content')
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
                                <!-- Indicators -->
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
                                    @endif
                                ">
                                    {{ $statusDisplay[$property->propertyStatus] ?? $property->propertyStatus }}
                                </span>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-5 text-muted fw-bold">Vacant:</div>
                            <div class="col-7">
                                @if($property->number_of_boarders >= $property->vacancy)
                                    {{ $property->number_of_boarders ?? 0 }}/{{ $property->vacancy ?? 0 }} (Fully Occupied)
                                @else
                                    {{ $property->number_of_boarders ?? 0 }}/{{ $property->vacancy ?? 0 }}
                                @endif
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-5 text-muted fw-bold">Applications:</div>
                            <div class="col-7">
                                {{ $property->applications->count() ?? 0 }}
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-5 text-muted fw-bold">Landlord:</div>
                            <div class="col-7">
                                {{ $property->landlord->first_name ?? 'N/A' }} {{ $property->landlord->last_name ?? '' }}
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-5 text-muted fw-bold">Created At:</div>
                            <div class="col-7">{{ $property->propertyCreatedAt }}</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-5 text-muted fw-bold">Updated At:</div>
                            <div class="col-7">{{ $property->propertyUpdatedAt }}</div>
                        </div>
                    </div>
                    <div class="d-flex gap-2 justify-content-end px-3 pb-3">
                        <button class="btn btn-warning btn-sm editPropertyBtn" data-id="{{ $property->propertyID }}"
                            data-name="{{ $property->propertyName }}" data-description="{{ $property->propertyDescription }}"
                            data-location="{{ $property->propertyLocation }}" data-rent="{{ $property->propertyRent }}"
                            data-status="{{ $property->propertyStatus }}" data-latitude="{{ $property->latitude }}"
                            data-longitude="{{ $property->longitude }}" data-vacancy="{{ $property->vacancy }}" data-bs-toggle="modal"
                            data-bs-target="#editPropertyModal" style="color:#000;">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-danger btn-sm deletePropertyBtn" data-id="{{ $property->propertyID }}"
                            data-name="{{ $property->propertyName }}" data-bs-toggle="modal" data-bs-target="#deletePropertyModal"
                            style="color:#fff;">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>

                <div class="d-flex">
                    <a href="{{ route('admin.properties.index') }}" class="btn btn-outline-secondary">
                        Back to List
                    </a>
                </div>

                <div class="mt-5">
                    <h3 class="text-center mb-4">Map Location</h3>
                    <div id="map" style="height: 400px; width: 100%; border-radius: 10px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Property Modal -->
    <div class="modal fade" id="editPropertyModal" tabindex="-1" aria-labelledby="editPropertyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form method="POST" id="editPropertyForm" enctype="multipart/form-data"
                action="{{ route('admin.properties.update', $property->propertyID) }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="propertyID" value="{{ $property->propertyID }}">
                <input type="hidden" name="userID" value="{{ $property->userID }}">
                <input type="hidden" name="redirect_to" value="show">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editPropertyModalLabel">Edit Property</h5>
                    </div>
                    <div class="modal-body">
                        <div id="mapStepEdit">
                            <div id="editMap" style="height: 400px;"></div>
                            <input type="hidden" name="latitude" id="editLatitude" value="{{ $property->latitude }}">
                            <input type="hidden" name="longitude" id="editLongitude" value="{{ $property->longitude }}">
                            <div class="mb-3">
                                <label>Location</label>
                                <input type="text" name="propertyLocation" id="editPropertyLocation" class="form-control"
                                    value="{{ $property->propertyLocation }}" required>
                            </div>
                        </div>
                        <div id="formStepEdit" style="display: none;">
                            <div class="mb-3">
                                <label>Name</label>
                                <input type="text" name="propertyName" class="form-control" value="{{ $property->propertyName }}" required>
                            </div>
                            <div class="mb-3">
                                <label>Description</label>
                                <textarea name="propertyDescription" class="form-control">{{ $property->propertyDescription }}</textarea>
                            </div>
                            <div class="mb-3">
                                <label>Rent</label>
                                <input type="number" name="propertyRent" class="form-control" value="{{ $property->propertyRent }}"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label>Vacancy</label>
                                <input type="number" name="vacancy" class="form-control" id="editVacancyInput"
                                    value="{{ $property->vacancy }}" required min="0">
                            </div>
                            <div class="mb-3">
                                <label>Status</label>
                                <input type="text" class="form-control" id="editPropertyStatusInput"
                                    value="{{ $property->vacancy == 0 ? 'Fully Occupied' : 'Available' }}" readonly>
                                <input type="hidden" name="propertyStatus" id="editPropertyStatusHidden"
                                    value="{{ $property->vacancy == 0 ? 'Fullyoccupied' : 'Available' }}">
                            </div>
                            <div class="mb-3">
                                <label>Images (Max 3)</label>

                                <!-- Current Images Display -->
                                <div id="currentImagesEditAdminShow" class="mb-3">
                                    <div class="d-flex gap-2 flex-wrap" id="imagePreviewContainerAdminShow">
                                        <!-- Will be populated by JavaScript -->
                                    </div>
                                </div>

                                <!-- Add Images -->
                                <input type="file" name="propertyImages[]" id="imageInputAdminShow" class="form-control" multiple
                                    accept="image/*" max="3">
                                <small class="form-text text-muted">You can select up to 3 images. The first image will be
                                    the main image.</small>

                                <!-- Hidden inputs for removed images -->
                                <div id="removedImagesInputsAdminShow"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
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
            <form method="POST" action="{{ route('admin.properties.destroy', $property->propertyID) }}" id="deletePropertyForm">
                @csrf
                @method('DELETE')
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deletePropertyModalLabel">Delete Property</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete <strong>{{ $property->propertyName }}</strong>?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
        <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
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
                    onAdd: function (map) {
                        const container = L.DomUtil.create('div', 'leaflet-bar leaflet-control leaflet-control-custom');
                        container.innerHTML = '<a href="#" title="Reset View" role="button" aria-label="Reset View">⌂</a>';
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

                L.marker([lat, lng]).addTo(map)
                    .bindPopup("{{ $property->propertyName }}")
                    .openPopup();

                // Edit Modal Map Logic
                let editMap, editMarker;
                const editPropertyModal = document.getElementById('editPropertyModal');
                const editMapContainer = document.getElementById('editMap');
                const editLatitudeInput = document.getElementById('editLatitude');
                const editLongitudeInput = document.getElementById('editLongitude');
                const editPropertyLocationInput = document.getElementById('editPropertyLocation');

                // Function to load current images for admin show page editing
                function loadCurrentImagesAdminShow(propertyId) {
                    // Fetch current images from the server
                    fetch(`/admin/properties/${propertyId}/images`)
                        .then(response => response.json())
                        .then(data => {
                            const imagePreviewContainer = document.getElementById('imagePreviewContainerAdminShow');
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
                                                    onclick="removeImageAdminShow(${index}, '${image}')">
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

                // Function to remove image for admin show page
                window.removeImageAdminShow = function (index, imagePath) {
                    // Add hidden input to track removed images
                    const removedImagesContainer = document.getElementById('removedImagesInputsAdminShow');
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'removed_images[]';
                    hiddenInput.value = imagePath;
                    removedImagesContainer.appendChild(hiddenInput);

                    // Remove the image preview
                    event.target.parentElement.remove();
                }

                editPropertyModal.addEventListener('shown.bs.modal', function () {
                    // Load current images when modal is shown
                    loadCurrentImagesAdminShow({{ $property->propertyID }});

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

                        // Add custom reset control for edit map
                        L.Control.ResetViewEdit = L.Control.extend({
                            onAdd: function (map) {
                                const container = L.DomUtil.create('div', 'leaflet-bar leaflet-control leaflet-control-custom');
                                container.innerHTML = '<a href="#" title="Reset View" role="button" aria-label="Reset View">⌂</a>';
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

                        // Add reset control to edit map
                        new L.Control.ResetViewEdit({ position: 'topleft' }).addTo(editMap);

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
                                editLatitudeInput.value = center.lat;
                                editLongitudeInput.value = center.lng;
                                fetch(`https://nominatim.openstreetmap.org/reverse?lat=${center.lat}&lon=${center.lng}&format=json`)
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data && data.display_name) {
                                            editPropertyLocationInput.value = data.display_name;
                                        }
                                    });
                            })
                            .addTo(editMap);

                        editMarker = L.marker([lat, lng]).addTo(editMap);

                        editMap.on('click', function (e) {
                            if (editMarker) editMap.removeLayer(editMarker);
                            editMarker = L.marker(e.latlng).addTo(editMap);
                            editLatitudeInput.value = e.latlng.lat;
                            editLongitudeInput.value = e.latlng.lng;
                            // Optionally, reverse geocode to update the location input
                            fetch(`https://nominatim.openstreetmap.org/reverse?lat=${e.latlng.lat}&lon=${e.latlng.lng}&format=json`)
                                .then(response => response.json())
                                .then(data => {
                                    if (data && data.display_name) {
                                        editPropertyLocationInput.value = data.display_name;
                                    }
                                });
                        });
                    } else {
                        editMap.invalidateSize();
                    }
                });

                // Handle the two-step process
                const nextButtonEdit = document.getElementById('nextButtonEdit');
                const submitButtonEdit = document.getElementById('submitButtonEdit');
                const mapStepEdit = document.getElementById('mapStepEdit');
                const formStepEdit = document.getElementById('formStepEdit');
                // Add vacancy/status logic
                const editVacancyInput = document.getElementById('editVacancyInput');
                const editPropertyStatusInput = document.getElementById('editPropertyStatusInput');
                if (editVacancyInput && editPropertyStatusInput) {
                    editVacancyInput.addEventListener('input', function () {
                        const vacancy = parseInt(editVacancyInput.value, 10);
                        if (vacancy === 0) {
                            editPropertyStatusInput.value = 'Fully Occupied';
                            document.getElementById('editPropertyStatusHidden').value = 'Fullyoccupied';
                        } else {
                            editPropertyStatusInput.value = 'Available';
                            document.getElementById('editPropertyStatusHidden').value = 'Available';
                        }
                    });
                }

                nextButtonEdit.addEventListener('click', function () {
                    const locationVal = editPropertyLocationInput.value.trim();
                    if (!locationVal) {
                        alert('Please select a location before proceeding.');
                        return;
                    }
                    mapStepEdit.style.display = 'none';
                    formStepEdit.style.display = 'block';
                    nextButtonEdit.style.display = 'none';
                    submitButtonEdit.style.display = 'inline-block';
                    const backButtonEdit = document.getElementById('backButtonEdit');
                    backButtonEdit.style.display = 'inline-block';
                });

                submitButtonEdit.addEventListener('click', function () {
                    document.getElementById('editPropertyForm').submit();
                });

                const backButtonEdit = document.getElementById('backButtonEdit');
                if (backButtonEdit) {
                    backButtonEdit.addEventListener('click', function () {
                        formStepEdit.style.display = 'none';
                        mapStepEdit.style.display = 'block';
                        nextButtonEdit.style.display = 'inline-block';
                        submitButtonEdit.style.display = 'none';
                        backButtonEdit.style.display = 'none';
                    });
                }

                // Handle image input preview for admin show page
                const imageInputAdminShow = document.getElementById('imageInputAdminShow');
                if (imageInputAdminShow) {
                    imageInputAdminShow.addEventListener('change', function (e) {
                        const files = Array.from(e.target.files);
                        const imagePreviewContainer = document.getElementById('imagePreviewContainerAdminShow');

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
                                reader.onload = function (e) {
                                    const imageDiv = document.createElement('div');
                                    imageDiv.className = 'position-relative new-image-preview';
                                    imageDiv.style.cssText = 'width: 100px; height: 100px;';
                                    imageDiv.innerHTML = `
                                             <img src="${e.target.result}" class="img-fluid rounded" style="width: 100px; height: 100px; object-fit: cover;">
                                             <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 rounded-circle" 
                                                     style="width: 25px; height: 25px; padding: 0; font-size: 12px; transform: translate(50%, -50%);"
                                                     onclick="removeNewImageAdminShow(this)">
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

                // Function to remove new image preview for admin show page
                window.removeNewImageAdminShow = function (button) {
                    button.parentElement.remove();
                    // Reset file input
                    document.getElementById('imageInputAdminShow').value = '';
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
                    document.getElementById('imageLimitModal').addEventListener('hidden.bs.modal', function () {
                        this.remove();
                    });
                }
            });
        </script>
    @endpush
@endsection
