@extends('include')
@section('backTitle'){{ __('common.heading_business_locations') }}@endsection
@section('container')

@mobile
<section class="rn-m-list-page" data-rn-list="business-locations">
    <header class="rn-m-list-header">
        <h2>{{ __('settings.business_locations') }}</h2>
        <a href="{{ route('business.locations.create') }}" class="rn-m-btn rn-m-btn--primary">+ {{ __('mobile.add_new') }}</a>
    </header>
    <ul class="rn-m-cards" role="list">
        @forelse($locations as $location)
            @php
                $address = collect([$location->address, $location->city, $location->state, $location->postal_code, $location->country])->filter()->implode(', ');
                $actions = [['type' => 'link', 'label' => __('common.edit'), 'url' => route('business.locations.edit', $location->id), 'icon' => 'ri-edit-line']];
                if (!$location->is_main_location) {
                    $actions[] = ['type' => 'link', 'label' => __('common.delete'), 'url' => route('business.locations.delete', $location->id), 'icon' => 'ri-delete-bin-line'];
                }
            @endphp
            @php
                $badgeLabel = $location->is_main_location ? __('settings.main_location') : ($location->status ? __('settings.active') : __('settings.inactive'));
                $badgeTone = $location->status ? 'success' : 'muted';
            @endphp
            <x-m.list-card :card="['id'=>$location->id,'url'=>null,'avatar'=>mb_substr($location->name,0,1),'title'=>$location->name,'subtitle'=>$address ?: null,'amount'=>'','meta'=>array_values(array_filter([$location->phone,$location->email,$location->manager_name ? __('common.manager').': '.$location->manager_name : null])),'badge'=>['label'=>$badgeLabel,'tone'=>$badgeTone],'actions'=>$actions]" />
        @empty
            <li class="rn-m-empty">{{ __('settings.no_business_locations_found') }}</li>
        @endforelse
    </ul>
    @if($locations->hasPages()){{ $locations->links() }}@endif
</section>
@else
<div class="col-12">
    @include('sweetalert::alert')
</div>
<div class="card mt-5">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">{{ __('settings.business_locations') }}</h4>
            <a href="{{ route('business.locations.create') }}" class="btn btn-primary btn-sm">
                <i class="las la-plus"></i> {{ __('settings.add_new_location') }}
            </a>
        </div>
        @if($locations->count() > 0)
            <div class="table-responsive">
                <table class="data-tables table mb-0 table-bordered rn-table-pro">
                    <thead class="bg-white text-uppercase">
                        <tr>
                            <th>{{ __('settings.location_name') }}</th>
                            <th>{{ __('settings.address') }}</th>
                            <th>{{ __('settings.phone') }}</th>
                            <th>{{ __('settings.email') }}</th>
                            <th>{{ __('settings.status') }}</th>
                            <th>{{ __('settings.actions') }}</th>
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
                                                    <i class="las la-check-circle mr-1"></i>{{ __('settings.main_location') }}
                                                </span>
                                            @endif
                                            @if($location->manager_name)
                                                <small class="d-block text-muted">{{ __('common.manager') }}: {{ $location->manager_name }}</small>
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
                                            <i class="las la-check mr-1"></i>{{ __('settings.active') }}
                                        </span>
                                    @else
                                        <span class="badge badge-danger">
                                            <i class="las la-times mr-1"></i>{{ __('settings.inactive') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="align-middle">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('business.locations.edit', $location->id) }}"
                                            class="btn btn-sm btn-info" title="{{ __('common.edit_location') }}">
                                            <i class="las la-edit"></i>
                                        </a>
                                        @if(!$location->is_main_location)
                                            <a href="{{ route('business.locations.delete', $location->id) }}"
                                                class="btn btn-sm btn-danger"
                                                onclick="return confirm('Are you sure you want to delete this location?');"
                                                title="{{ __('common.delete_location') }}">
                                                <i class="las la-trash"></i>
                                            </a>
                                        @else
                                            <button class="btn btn-sm btn-secondary" disabled title="{{ __('common.main_location_cannot_be_deleted') }}">
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
                <h5 class="text-muted mt-3">{{ __('settings.no_business_locations_found') }}</h5>
                <p class="text-muted">{{ __('settings.start_by_creating_your_first_business_location') }}</p>
                <a href="{{ route('business.locations.create') }}" class="btn btn-primary btn-lg mt-3">
                    <i class="las la-plus-circle mr-2"></i>{{ __('settings.create_first_location') }}
                </a>
            </div>
        @endif
    </div>
</div>
@endmobile
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
