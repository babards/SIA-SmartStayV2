@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>All Boarders</h2>
        <form method="GET" action="" class="row g-2 mb-3 align-items-end">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="Search..."
                    value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="property_filter" class="form-select" onchange="this.form.submit()">
                    <option value="">All Properties</option>
                    @foreach($boarders->pluck('property.propertyName')->unique() as $propertyName)
                        <option value="{{ $propertyName }}" {{ request('property_filter') == $propertyName ? 'selected' : '' }}>{{ $propertyName }}
                        </option>
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
            <div class="col-md-2">
                <a href="{{ route('landlord.applications.all') }}" class="btn btn-outline-secondary w-100">Reset Filters</a>
            </div>
        </form>
        @if($boarders->count())
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Property Name</th>
                        <th>Tenant</th>
                        <th>Duration</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($boarders as $boarder)
                        <tr>
                            <td>{{ $boarder->property->propertyName ?? 'N/A' }}</td>
                            <td>{{ $boarder->tenant->first_name ?? 'N/A' }} {{ $boarder->tenant->last_name ?? '' }}</td>
                            @php
                                $start = \Carbon\Carbon::parse($boarder->created_at);
                                $now = \Carbon\Carbon::now();
                                $diff = $start->diff($now);
                            @endphp
                            <td>{{ $diff->m }} months and {{ $diff->d }} days</td>
                            <td>
                                <span class="badge 
                                            @if($boarder->status == 'active') bg-success
                                            @elseif($boarder->status == 'left') bg-danger
                                            @elseif($boarder->status == 'kicked') bg-warning text-dark
                                                @else bg-secondary
                                            @endif
                                        ">
                                    {{ ucfirst($boarder->status) }}
                                </span>
                            </td>
                            <td>
                                @if($boarder->status == 'active')
                                    <form action="{{ route('landlord.boarders.kicked', $boarder->id) }}" method="POST"
                                        style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-danger btn-sm">Kick Out</button>
                                    </form>
                                @else
                                    <span class="text-muted">No action required</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $boarders->links() }}
        @else
            <p>No applications found.</p>
        @endif
    </div>
@endsection