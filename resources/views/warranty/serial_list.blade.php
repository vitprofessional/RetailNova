@extends('include')

@section('backTitle'){{ __('common.heading_warranty_serial_list') }}@endsection
@section('container')
@mobile
<section class="rn-m-list-page" data-rn-list="serials">
  <header class="rn-m-list-header"><div class="rn-m-list-toolbar"><form class="rn-m-search" id="serialSearch" method="GET" role="search"><i class="ri-search-line"></i><label class="sr-only" for="serialQuery">{{ __('common.search_serial') }}</label><input id="serialQuery" type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('common.search_serial') }}"><button type="submit" class="sr-only">{{ __('common.search') }}</button></form></div></header>
  <ul class="rn-m-cards" role="list">
    @forelse($serials as $s)
      <x-m.list-card :card="['id'=>$s->id,'url'=>null,'avatar'=>mb_substr((string)($s->serialNumber ?? $s->serial ?? $s->serial_number),0,1),'title'=>$s->serialNumber ?? $s->serial ?? $s->serial_number,'subtitle'=>$s->product?->name ?? null,'amount'=>'','meta'=>array_values(array_filter([$s->created_at ? \App\Support\MobileDateTime::date($s->created_at).' '.\App\Support\MobileDateTime::time($s->created_at) : null])),'badge'=>null,'actions'=>[]]" />
    @empty
      <li class="rn-m-empty">{{ __('common.no_serials_found') }}</li>
    @endforelse
  </ul>
  {{ $serials->links() }}
  <a class="rn-m-btn rn-m-btn--outline" href="{{ route('serials.export', request()->all()) }}">{{ __('common.export_csv') }}</a>
</section>
@else
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
@endmobile
@endsection
