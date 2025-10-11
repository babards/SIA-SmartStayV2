@extends('layouts.app')

@section('content')
<div class="container">
    <h2>My Applications</h2>
    <form method="GET" action="" class="row g-2 mb-3 align-items-end">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control" placeholder="Search..." value="{{ request('search') }}">
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
            <a href="{{ route('tenant.applications.index') }}" class="btn btn-outline-secondary w-100">Reset Filters</a>
        </div>
    </form>
    
    @if($applications->count())
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Property Name</th>
                    <th>Location</th>
                    <th>Application Date</th>
                    <th>Status</th>
                    <th>Message</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($applications as $application)
                    <tr>
                        <td>{{ $application->property->propertyName ?? 'N/A' }}</td>
                        <td>{{ $application->property->propertyLocation ?? 'N/A' }}</td>
                        <td>{{ $application->application_date->format('Y-m-d') }}</td>
                        <td>
                            <span class="badge 
                                @if($application->status == 'approved') bg-success
                                @elseif($application->status == 'rejected') bg-danger
                                @elseif($application->status == 'pending') bg-warning text-dark
                                @else bg-secondary
                                @endif
                            ">
                                {{ ucfirst($application->status) }}
                            </span>
                        </td>
                        <td>
                            {{ $application->message ?? 'No message' }}
                        </td>
                        <td>
                            @if($application->status == 'pending')
                                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#cancelModal{{ $application->id }}">
                                    Cancel
                                </button>
                            @else
                                <span class="text-muted">No actions available</span>
                            @endif
                        </td>
                    </tr>

                    <!-- Cancel Modal for each application -->
                    @if($application->status == 'pending')
                    <div class="modal fade" id="cancelModal{{ $application->id }}" tabindex="-1" aria-labelledby="cancelModalLabel{{ $application->id }}" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="cancelModalLabel{{ $application->id }}">
                                        <i class="fas fa-exclamation-triangle text-warning me-2"></i>Cancel Application
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="alert alert-warning">
                                        <strong>Are you sure you want to cancel this application?</strong>
                                    </div>
                                    <p><strong>Property:</strong> {{ $application->property->propertyName ?? 'N/A' }}</p>
                                    <p><strong>Location:</strong> {{ $application->property->propertyLocation ?? 'N/A' }}</p>
                                    <p><strong>Application Date:</strong> {{ $application->application_date->format('F j, Y') }}</p>
                                    <p class="text-muted">This action cannot be undone. You will need to submit a new application if you change your mind.</p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                        <i class="fas fa-times me-1"></i>Keep Application
                                    </button>
                                    <form action="{{ route('tenant.applications.cancel', $application->id) }}" method="POST" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-danger">
                                            <i class="fas fa-trash me-1"></i>Yes, Cancel Application
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                @endforeach
            </tbody>
        </table>
        {{ $applications->links() }}
    @else
        <p>You have not applied for any properties yet.</p>
    @endif

    <a href="{{ route('tenant.properties.index') }}" class="btn btn-secondary mb-3">Back to Properties</a>
</div>
@endsection
