@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">All Boarders</h2>
        </div>

        <!-- Filter Bar -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Search boarders..." value="{{ request('search') }}">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <select name="property_filter" class="form-select" onchange="this.form.submit()">
                            <option value="">All Properties</option>
                            @foreach($boarders->pluck('property.propertyName')->unique() as $propertyName)
                                <option value="{{ $propertyName }}" {{ request('property_filter') == $propertyName ? 'selected' : '' }}>{{ $propertyName }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="tenant_filter" class="form-select" onchange="this.form.submit()">
                            <option value="">All Tenants</option>
                            @foreach($boarders->pluck('tenant')->unique('id') as $tenant)
                                @if($tenant)
                                    <option value="{{ $tenant->id }}" {{ request('tenant_filter') == $tenant->id ? 'selected' : '' }}>
                                        {{ $tenant->first_name }} {{ $tenant->last_name }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="status_filter" class="form-select" onchange="this.form.submit()">
                            <option value="">All Status</option>
                            <option value="active" {{ request('status_filter') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="left" {{ request('status_filter') == 'left' ? 'selected' : '' }}>Left</option>
                            <option value="kicked" {{ request('status_filter') == 'kicked' ? 'selected' : '' }}>Kicked</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <a href="{{ route('landlord.boarders.all') }}" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-undo me-1"></i>Reset Filters
                        </a>
                    </div>
                </form>
            </div>
        </div>
        @if($boarders->count())
            <!-- Boarders Grid -->
            <div class="row">
                @foreach($boarders as $boarder)
                    <div class="col-lg-6 col-xl-4 mb-4">
                        <div class="card h-100 shadow-sm border-0" style="border-radius: 12px;">
                            <div class="card-body">
                                <!-- Header with Property Name and Status -->
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="card-title mb-0 text-truncate" style="max-width: 200px;">
                                        {{ $boarder->property->propertyName ?? 'N/A' }}
                                    </h5>
                                    <span class="badge 
                                        @if($boarder->status == 'active') bg-success
                                        @elseif($boarder->status == 'left') bg-danger
                                        @elseif($boarder->status == 'kicked') bg-warning text-dark
                                        @else bg-secondary
                                        @endif
                                        rounded-pill px-3 py-2">
                                        {{ ucfirst($boarder->status) }}
                                    </span>
                                </div>

                                <!-- Tenant Information -->
                                <div class="mb-3">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-user text-muted me-2"></i>
                                        <span class="fw-semibold">{{ $boarder->tenant->first_name ?? 'N/A' }} {{ $boarder->tenant->last_name ?? '' }}</span>
                                    </div>
                                </div>

                                <!-- Duration -->
                                <div class="mb-3">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-clock text-muted me-2"></i>
                                        <span class="text-muted">
                                            @php
                                                $start = \Carbon\Carbon::parse($boarder->created_at);
                                                $now = \Carbon\Carbon::now();
                                                $diff = $start->diff($now);
                                            @endphp
                                            {{ $diff->m }} months and {{ $diff->d }} days
                                        </span>
                                    </div>
                                </div>

                                <!-- Property Location -->
                                <div class="mb-3">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-map-marker-alt text-muted me-2"></i>
                                        <span class="text-muted text-truncate">{{ $boarder->property->propertyLocation ?? 'N/A' }}</span>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="mt-auto">
                                    @if($boarder->status == 'active')
                                        <button type="button" class="btn btn-danger w-100 rounded-pill" data-bs-toggle="modal" data-bs-target="#kickModal{{ $boarder->id }}">
                                            <i class="fas fa-user-times me-1"></i>Kick Out
                                        </button>
                                    @else
                                        <div class="text-center">
                                            <span class="text-muted">
                                                <i class="fas fa-info-circle me-1"></i>No action required
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Kick Out Modal for each boarder -->
                    @if($boarder->status == 'active')
                    <div class="modal fade" id="kickModal{{ $boarder->id }}" tabindex="-1" aria-labelledby="kickModalLabel{{ $boarder->id }}" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
                                <div class="modal-header border-0 pb-0">
                                    <h5 class="modal-title" id="kickModalLabel{{ $boarder->id }}">
                                        <i class="fas fa-exclamation-triangle text-warning me-2"></i>Kick Out Boarder
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body pt-0">
                                    <div class="alert alert-warning border-0" style="border-radius: 8px; background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);">
                                        <strong>Are you sure you want to kick out this boarder?</strong>
                                    </div>
                                    <div class="card border-0" style="background: #f8f9fa;">
                                        <div class="card-body">
                                            <p class="mb-2"><strong>Tenant:</strong> {{ $boarder->tenant->first_name ?? 'N/A' }} {{ $boarder->tenant->last_name ?? '' }}</p>
                                            <p class="mb-2"><strong>Property:</strong> {{ $boarder->property->propertyName ?? 'N/A' }}</p>
                                            <p class="mb-0"><strong>Duration:</strong> {{ $diff->m }} months and {{ $diff->d }} days</p>
                                        </div>
                                    </div>
                                    <p class="text-muted mt-3 mb-0">This action will mark the boarder as "kicked" and they will no longer be considered an active tenant.</p>
                                </div>
                                <div class="modal-footer border-0 pt-0">
                                    <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">
                                        <i class="fas fa-times me-1"></i>Cancel
                                    </button>
                                    <form action="{{ route('landlord.boarders.kicked', $boarder->id) }}" method="POST" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-danger rounded-pill">
                                            <i class="fas fa-user-times me-1"></i>Yes, Kick Out
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
                {{ $boarders->links('pagination::bootstrap-5') }}
            </div>
        @else
            <!-- No Results Message -->
            <div class="text-center py-5">
                <div class="card border-0 shadow-sm" style="border-radius: 12px; background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);">
                    <div class="card-body py-5">
                        <i class="fas fa-users text-primary mb-3" style="font-size: 3rem;"></i>
                        <h4 class="text-primary mb-2">No Boarders Found</h4>
                        <p class="text-muted mb-0">There are no boarders matching your current filters.</p>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection