@extends('include')

@section('backTitle'){{ __('common.heading_warranty_serial_list') }}@endsection
@section('container')
<div class="col-12">
    <h4>{{ __('common.product_serials') }}</h4>
    <p class="text-muted">{{ __('common.listing_of_product_serials') }}</p>
    <div class="card">
        <div class="card-body">
            <form class="mb-3" id="serialSearch" method="GET">
                <div class="input-group">
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="{{ __('common.search_serial') }}">
                    <div class="input-group-append">
                        <button class="btn btn-outline-secondary btn-sm">{{ __('common.search') }}</button>
                        <a href="{{ route('serials.export', request()->all()) }}" class="btn btn-success btn-sm ml-2">{{ __('common.export_csv') }}</a>
                    </div>
                </div>
            </form>

            @if(isset($serials) && $serials->count())
            <div class="table-responsive">
                <table class="table table-sm table-hover rn-table-pro">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('common.serial') }}</th>
                            <th>{{ __('common.product') }}</th>
                            <th>{{ __('common.created_at') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($serials as $s)
                        <tr>
                            <td>{{ $s->id }}</td>
                            <td>{{ $s->serialNumber ?? $s->serial ?? $s->serial_number }}</td>
                            <td>{{ $s->productName ?? '-' }}</td>
                            <td>{{ optional($s->created_at)->format('Y-m-d H:i') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div>{{ $serials->links() }}</div>
            @else
            <div class="text-muted">{{ __('common.no_serials_found') }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
