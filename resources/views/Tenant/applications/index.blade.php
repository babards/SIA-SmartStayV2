@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">My Applications</h2>
        <a href="{{ route('tenant.properties.index') }}" class="btn btn-outline-primary">
            <i class="fas fa-search me-1"></i>Browse Properties
        </a>
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
                                <h5 class="card-title mb-0 text-truncate" style="max-width: 200px;">
                                    {{ $application->property->propertyName ?? 'N/A' }}
                                </h5>
                                <span class="badge 
                                    @if($application->status == 'approved') bg-success
                                    @elseif($application->status == 'rejected') bg-danger
                                    @elseif($application->status == 'pending') bg-warning text-dark
                                    @else bg-secondary
                                    @endif
                                    rounded-pill px-3 py-2">
                                    {{ ucfirst($application->status) }}
                                </span>
                            </div>

                            <!-- Property Location -->
                            <div class="mb-3">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-map-marker-alt text-muted me-2"></i>
                                    <span class="text-muted text-truncate">{{ $application->property->propertyLocation ?? 'N/A' }}</span>
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
@endsection
