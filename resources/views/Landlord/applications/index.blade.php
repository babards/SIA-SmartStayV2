@extends('layouts.app')

@section('content')
<div class="container">
    <h2>All Tenant Applications</h2>
    <form method="GET" action="" class="row g-2 mb-3 align-items-end">
        <div class="col-md-3">
            <input type="text" name="search" class="form-control" placeholder="Search..." value="{{ request('search') }}">
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
        <div class="col-md-2">
            <a href="{{ route('landlord.applications.all') }}" class="btn btn-outline-secondary w-100">Reset Filters</a>
        </div>
    </form>
    @if($applications->count())
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Property Name</th>
                    <th>Tenant</th>
                    <th>Application Date</th>
                    <th>Status</th>
                    <th>Message</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($applications as $application)
                    <tr>
                        <td>{{ $application->property->propertyName ?? 'N/A' }}</td>
                        <td>{{ $application->tenant->first_name ?? 'N/A' }} {{ $application->tenant->last_name ?? '' }}</td>
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
                        <td style="max-width: 200px; word-break: break-word;">
                            {{ $application->message ?? 'No message' }}
                        </td>
                        <td>
                            @if($application->status == 'pending')
                                <form action="{{ route('landlord.applications.approve', $application->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm">Approve</button>
                                </form>
                                <form action="{{ route('landlord.applications.reject', $application->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                                </form>
                            @else
                                <span class="text-muted">No action required</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="mt-3">
            {{ $applications->links('pagination::bootstrap-5') }}
        </div>
    @else
        <p>No applications found.</p>
    @endif
</div>
@endsection
