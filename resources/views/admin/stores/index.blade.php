@extends('include')
@section('backTitle'){{ __('common.heading_stores') }}@endsection
@section('container')
<div class="col-12">
    @include('sweetalert::alert')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ __('common.business_locations') }}</h5>
            <a href="{{ route('business.locations.create') }}" class="btn btn-primary btn-sm">
                <i class="las la-plus"></i> {{ __('common.add_location') }}
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>{{ __('common.id') }}</th><th>{{ __('common.name') }}</th><th>{{ __('common.city') }}</th><th>{{ __('common.phone') }}</th><th>{{ __('common.main') }}</th><th>{{ __('common.actions') }}</th></tr></thead>
                    <tbody>
                        @foreach($locations as $l)
                        <tr>
                            <td>{{ $l->id }}</td>
                            <td>{{ $l->name }}</td>
                            <td>{{ $l->city }}</td>
                            <td>{{ $l->phone }}</td>
                            <td>{!! $l->is_main_location ? '<span class="badge badge-success">{{ __('common.main') }}</span>' : '<span class="badge badge-secondary">Branch</span>' !!}</td>
                            <td>
                                <a href="{{ route('business.locations.edit', $l->id) }}" class="btn btn-sm btn-info">{{ __('common.edit') }}</a>
                                @if(!$l->is_main_location)
                                <form action="{{ route('business.locations.delete', $l->id) }}" method="POST" style="display:inline-block">
                                    @csrf
                                    <button class="btn btn-sm btn-danger" onclick="return confirm('Delete this location?')">{{ __('common.delete') }}</button>
                                </form>
                                @else
                                <button class="btn btn-sm btn-secondary" disabled>{{ __('common.delete') }}</button>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $locations->links() }}
        </div>
    </div>
</div>
@endsection
