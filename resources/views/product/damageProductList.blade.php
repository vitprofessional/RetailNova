@extends('include') @section('backTitle'){{ __('common.heading_damage_product_list') }}@endsection @section('container')
@mobile
<section class="rn-m-list-page" data-rn-list="damage-products">
  <ul class="rn-m-cards" role="list">
    @forelse($damageList as $d)
      @php($productName = $d->product?->name ?? '-')
      <x-m.list-card :card="['id'=>$d->id,'url'=>route('damageProductView',$d->id),'avatar'=>mb_substr($productName,0,1),'title'=>$productName,'subtitle'=>$d->reference ?: null,'amount'=>\App\Support\LocalizedNumber::money($d->total ?? 0),'meta'=>array_values(array_filter([$d->admin?->name ?: null, $d->date ? \App\Support\MobileDateTime::date($d->date) : null])),'badge'=>['label'=>__('inventory.damage_product'),'tone'=>'danger'],'actions'=>[['type'=>'link','label'=>__('inventory.view'),'url'=>route('damageProductView',$d->id),'icon'=>'ri-eye-line'],['type'=>'link','label'=>__('inventory.print'),'url'=>route('damageProductPrint',$d->id),'icon'=>'ri-printer-line'],['type'=>'link','label'=>__('inventory.delete'),'url'=>route('damageProductDelete',$d->id),'icon'=>'ri-delete-bin-line']]]" />
    @empty
      <li class="rn-m-empty">{{ __('inventory.no_damage_records_found') }}</li>
    @endforelse
  </ul>
</section>
@else
<div class="col-12">
    @include('sweetalert::alert')
</div>
<div class="row">
    <div class="col-md-12 col-sm-12 col-lg-12">
        <div class="card">
            <div class="card-body">
                <div class="col-12 p-0 mt-0 mb-4">
                    <h4>{{ __('inventory.damage_product_list') }}</h4>
                </div>
                @include('partials.bulk-actions', ['deleteRoute' => 'damageProducts.bulkDelete', 'entity' => 'Damage Records'])
                <div class="rounded mb-2 table-responsive product-table">
                    <div class="rn-search-box rn-col-compact" style="min-width:240px;">
                        <span class="rn-search-icon"><i class="las la-search"></i></span>
                        <input type="text" class="rn-search-input rn-filter-input" placeholder="{{ __('common.search') }}" data-table-target="damageTable">
                        <button class="rn-search-clear">&times;</button>
                    </div>
                    <div class="d-flex mb-3 align-items-center flex-wrap" style="gap:.5rem;">
                        <div>
                            <label class="mb-0">{{ __('inventory.product') }}</label>
                            <select class="form-control rn-filter-input" data-table-target="damageTable" data-filter-for="product" style="min-width:200px;">
                                <option value="">{{ __('inventory.all_products') }}</option>
                                @foreach(($productList ?? []) as $p)
                                    <option value="{{ strtolower($p->name) }}">{{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-0">{{ __('inventory.from') }}</label>
                            <input type="date" class="form-control rn-filter-input" data-table-target="damageTable" data-filter-date="from">
                        </div>
                        <div>
                            <label class="mb-0">{{ __('inventory.to') }}</label>
                            <input type="date" class="form-control rn-filter-input" data-table-target="damageTable" data-filter-date="to">
                        </div>
                    </div>
                    <table id="damageTable" class="data-tables table mb-0 table-bordered rn-table-pro">
                        <thead class="bg-white text-uppercase">
                            <tr>
                                <th>
                                    <div class="checkbox d-inline-block">
                                        <input type="checkbox" class="checkbox-input" id="selectAllDamage" />
                                        <label for="selectAllDamage" class="mb-0"></label>
                                    </div>
                                </th>
                                <th>{{ __('inventory.reference') }}</th>
                                <th>{{ __('inventory.product_name') }}</th>
                                <th>{{ __('inventory.total') }}</th>
                                <th>{{ __('inventory.created_by') }}</th>
                                <th>{{ __('inventory.date') }}</th>
                                <th>{{ __('inventory.print') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($damageList as $d)
                            <tr data-date="{{ optional($d->date)->format('Y-m-d') }}">
                                <td>
                                    <div class="checkbox d-inline-block">
                                        <input type="checkbox" class="checkbox-input bulk-select" value="{{ $d->id }}" />
                                        <label class="mb-0"></label>
                                    </div>
                                </td>
                                <td>{{ $d->reference ?? '-' }}</td>
                                <td>{{ $d->product ? $d->product->name : '-' }}</td>
                                <td>{{ number_format($d->total ?? 0, 2) }}</td>
                                <td>{{ $d->admin ? $d->admin->name : ($d->admin_id ? 'Admin #'.$d->admin_id : '-') }}</td>
                                <td>{{ \Carbon\Carbon::parse($d->date)->format('Y-m-d') }}</td>
                                <td class="list-action">
                                    <a class="badge badge-info mr-2" data-toggle="tooltip" data-placement="top" title="{{ __('inventory.view') }}" href="{{ route('damageProductView', $d->id) }}"><i class="ri-eye-line mr-0"></i></a>
                                    <a class="badge badge-primary mr-2" data-toggle="tooltip" data-placement="top" title="{{ __('inventory.print') }}" href="{{ route('damageProductPrint', $d->id) }}" target="_blank"><i class="ri-printer-line mr-0"></i></a>
                                    <a class="badge badge-danger" data-toggle="tooltip" data-placement="top" title="{{ __('inventory.delete') }}" href="{{ route('damageProductDelete', $d->id) }}"><i class="ri-delete-bin-line mr-0"></i></a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center">{{ __('inventory.no_damage_records_found') }}</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endmobile
@endsection
@section('scripts')
    @parent
    <script>
    // Initialize DataTable for damage list if DataTables is available
    (function(){
        window.__jqOnReady(function(){
            try{
                if(window.jQuery && typeof jQuery.fn.DataTable === 'function'){
                    var $t = $('#damageTable');
                    if(!$t.length) return;
                    if(!$.fn.DataTable.isDataTable($t)){
                        var opts = { pageLength: 10, order: [], lengthChange: false };
                        // Enable buttons export if Buttons extension is present
                        if($.fn.dataTable && $.fn.dataTable.Buttons){
                            opts.dom = 'Bfrtip';
                            opts.buttons = [ 'copy', 'csv', 'excel', 'pdf', 'print' ];
                        }
                        $t.DataTable(opts);
                    }
                }
            }catch(e){ console.warn('DataTable init failed', e); }
        });
    })();
    </script>
    @include('partials.bulk-actions-script')
@endsection
