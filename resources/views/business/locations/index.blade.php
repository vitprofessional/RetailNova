@extends('include')
@section('backTitle') Business Locations @endsection
@section('container')

<div class="col-12">
    @include('sweetalert::alert')
</div>
<div class="card mt-5">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">Business Locations</h4>
            <a href="{{ route('business.locations.create') }}" class="btn btn-primary btn-sm">
                <i class="las la-plus"></i> Add New Location
            </a>
        </div>
        @if($locations->count() > 0)
            <div class="table-responsive">
                <table class="data-tables table mb-0 table-bordered">
                    <thead class="bg-white text-uppercase">
                        <tr>
                            <th>Location Name</th>
                            <th>Address</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($locations as $location)
                            <tr class="table-row">
                                <td class="align-middle">
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <h6 class="mb-0 font-weight-600">{{ $location->name }}</h6>
                                            @if($location->is_main_location)
                                                <span class="badge badge-success">
                                                    <i class="las la-check-circle mr-1"></i>Main Location
                                                </span>
                                            @endif
                                            @if($location->manager_name)
                                                <small class="d-block text-muted">Manager: {{ $location->manager_name }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="align-middle">
                                    <small class="text-muted">
                                        {{ $location->address }}<br>
                                        {{ $location->city }}, {{ $location->state }} {{ $location->postal_code }}<br>
                                        {{ $location->country }}
                                    </small>
                                </td>
                                <td class="align-middle">
                                    <a href="tel:{{ $location->phone }}" class="text-primary">
                                        {{ $location->phone }}
                                    </a>
                                </td>
                                <td class="align-middle">
                                    @if($location->email)
                                        <a href="mailto:{{ $location->email }}" class="text-primary">
                                            {{ $location->email }}
                                        </a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="align-middle">
                                    @if($location->status)
                                        <span class="badge badge-success">
                                            <i class="las la-check mr-1"></i>Active
                                        </span>
                                    @else
                                        <span class="badge badge-danger">
                                            <i class="las la-times mr-1"></i>Inactive
                                        </span>
                                    @endif
                                </td>
                                <td class="align-middle">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('business.locations.edit', $location->id) }}" 
                                            class="btn btn-sm btn-info" title="Edit Location">
                                            <i class="las la-edit"></i>
                                        </a>
                                        @if(!$location->is_main_location)
                                            <a href="{{ route('business.locations.delete', $location->id) }}" 
                                                class="btn btn-sm btn-danger" 
                                                onclick="return confirm('Are you sure you want to delete this location?');"
                                                title="Delete Location">
                                                <i class="las la-trash"></i>
                                            </a>
                                        @else
                                            <button class="btn btn-sm btn-secondary" disabled title="Main location cannot be deleted">
                                                <i class="las la-lock"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($locations->hasPages())
                <div class="d-flex justify-content-center mt-4">
                    {{ $locations->links('pagination::bootstrap-4') }}
                </div>
            @endif
        @else
            <div class="text-center py-5">
                <i class="las la-inbox" style="font-size: 60px; color: #ddd;"></i>
                <h5 class="text-muted mt-3">No Business Locations Found</h5>
                <p class="text-muted">Start by creating your first business location.</p>
                <a href="{{ route('business.locations.create') }}" class="btn btn-primary btn-lg mt-3">
                    <i class="las la-plus-circle mr-2"></i>Create First Location
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
<style>
    .table-header {
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
    }
    .table-header th {
        color: #495057;
        font-weight: 600;
        padding: 1rem;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
    }
    .table-row:hover {
        background-color: #f8f9fa;
    }
    .table-row td {
        padding: 1rem;
        border-bottom: 1px solid #dee2e6;
    }
</style>
