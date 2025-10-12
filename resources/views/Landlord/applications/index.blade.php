@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">All Tenant Applications</h2>
    </div>

    <!-- Filter Bar -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Search applications..." value="{{ request('search') }}">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="property_filter" class="form-select" onchange="this.form.submit()">
                        <option value="">All Properties</option>
                        @foreach($applications->pluck('property.propertyName')->unique() as $propertyName)
                            <option value="{{ $propertyName }}" {{ request('property_filter') == $propertyName ? 'selected' : '' }}>{{ $propertyName }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="tenant_filter" class="form-select" onchange="this.form.submit()">
                        <option value="">All Tenants</option>
                        @foreach($applications->pluck('tenant')->unique('id') as $tenant)
                            @if($tenant)
                                <option value="{{ $tenant->id }}" {{ request('tenant_filter') == $tenant->id ? 'selected' : '' }}>{{ $tenant->first_name }} {{ $tenant->last_name }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status_filter" class="form-select" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="approved" {{ request('status_filter') == 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="pending" {{ request('status_filter') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="rejected" {{ request('status_filter') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                        <option value="cancelled" {{ request('status_filter') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <a href="{{ route('landlord.applications.all') }}" class="btn btn-outline-secondary w-100">
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

                            <!-- Tenant Information -->
                            <div class="mb-3">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-user text-muted me-2"></i>
                                    <span class="fw-semibold">{{ $application->tenant->first_name ?? 'N/A' }} {{ $application->tenant->last_name ?? '' }}</span>
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
                                        <small class="text-muted">Message:</small>
                                        <p class="mb-0 text-truncate" style="max-height: 60px; overflow: hidden;">
                                            {{ $application->message ?? 'No message provided' }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="mt-auto">
                                @if($application->status == 'pending')
                                    <div class="d-flex gap-2">
                                        <form action="{{ route('landlord.applications.approve', $application->id) }}" method="POST" class="flex-fill">
                                            @csrf
                                            <button type="submit" class="btn btn-success w-100 rounded-pill">
                                                <i class="fas fa-check me-1"></i>Approve
                                            </button>
                                        </form>
                                        <form action="{{ route('landlord.applications.reject', $application->id) }}" method="POST" class="flex-fill">
                                            @csrf
                                            <button type="submit" class="btn btn-danger w-100 rounded-pill">
                                                <i class="fas fa-times me-1"></i>Reject
                                            </button>
                                        </form>
                                    </div>
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
                    <i class="fas fa-inbox text-primary mb-3" style="font-size: 3rem;"></i>
                    <h4 class="text-primary mb-2">No Applications Found</h4>
                    <p class="text-muted mb-0">There are no tenant applications matching your current filters.</p>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
