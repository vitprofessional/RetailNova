@extends('include')

@section('backTitle')Warranty - Serial List @endsection
@section('container')
<div class="col-12">
    <h4>Product Serials</h4>
    <p class="text-muted">Listing of product serials.</p>
    <div class="card">
        <div class="card-body">
            <form class="mb-3" id="serialSearch" method="GET">
                <div class="input-group">
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search serial...">
                    <div class="input-group-append">
                        <button class="btn btn-outline-secondary btn-sm">Search</button>
                        <a href="{{ route('serials.export', request()->all()) }}" class="btn btn-success btn-sm ml-2">Export CSV</a>
                    </div>
                </div>
            </form>

            @if(isset($serials) && $serials->count())
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Serial</th>
                            <th>Product</th>
                            <th>Created At</th>
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
            <div class="text-muted">No serials found.</div>
            @endif
        </div>
    </div>
</div>
@endsection
