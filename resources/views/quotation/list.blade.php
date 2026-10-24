@extends('include')
@section('backTitle'){{ __('common.heading_quotations') }}@endsection
@section('container')
@mobile
<section class="rn-m-list-page" data-rn-list="quotations">
  <header class="rn-m-list-header"><div class="rn-m-list-toolbar"><form method="GET" class="rn-m-search" role="search"><i class="ri-search-line"></i><label class="sr-only" for="quoteSearch">{{ __('mobile.list_search') }}</label><input id="quoteSearch" name="q" value="{{ request('q') }}" placeholder="{{ __('mobile.list_search') }}"></form></div></header>
  <ul class="rn-m-cards" role="list">
    @forelse($quotes as $q)
      @php
        $customer = \App\Models\Customer::displayLabel($q->customer, $q->customer_id, $q->businessId);
        $actions = [['type'=>'link','label'=>__('common.view'),'url'=>route('quotation.show',['id'=>$q->id]),'icon'=>'ri-eye-line'], ['type'=>'link','label'=>__('common.print'),'url'=>route('quotation.print',['id'=>$q->id]),'icon'=>'ri-printer-line']];
        if ($q->status === 'draft') {
            $actions[] = ['type'=>'link','label'=>__('common.edit'),'url'=>route('quotation.edit',['id'=>$q->id]),'icon'=>'ri-pencil-line'];
        }
      @endphp
      @unless($q->status === 'draft')@php($actions = array_values(array_filter($actions, fn($a) => $a['label'] !== __('common.edit'))))@endunless
      @canEdit(false)
        @if($q->status === 'draft')
          @if(!in_array(__('common.edit'), array_column($actions,'label'), true))@php($actions[] = ['type'=>'link','label'=>__('common.edit'),'url'=>route('quotation.edit',['id'=>$q->id]),'icon'=>'ri-pencil-line'])@endif
        @endif
      @else
        @php($actions = array_values(array_filter($actions, fn($a) => $a['label'] !== __('common.edit'))))
      @endcanEdit
      <x-m.list-card :card="['id'=>$q->id,'url'=>route('quotation.show',['id'=>$q->id]),'avatar'=>mb_substr($q->quote_number,0,1),'title'=>$q->quote_number,'subtitle'=>$customer,'amount'=>\App\Support\LocalizedNumber::money($q->grand_total),'meta'=>array_values(array_filter([$q->date ? \App\Support\MobileDateTime::date($q->date) : null])),'badge'=>['label'=>__('common.'.$q->status),'tone'=>$q->status==='draft'?'warning':'success'],'actions'=>$actions]" />
    @empty
      <li class="rn-m-empty">{{ __('mobile.list_empty') }}</li>
    @endforelse
  </ul>
  {{ $quotes->links() }}
  <x-m.fab :url="route('quotation.create')" :label="__('common.new_quotation')" />
</section>
@else
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div class="header-title">
          <h4 class="card-title">{{ __('common.quotations') }}</h4>
        </div>
        <a href="{{ route('quotation.create') }}" class="btn btn-primary btn-sm"><i class="las la-plus mr-1"></i>{{ __('common.new_quotation') }}</a>
      </div>
      <div class="card-body">
        <div class="mb-3 table-responsive product-table">
          <table class="table mb-0 table-bordered rounded-0 rn-table-pro">
            <thead>
              <tr>
                <th>#</th>
                <th>{{ __('common.quote_no') }}</th>
                <th>{{ __('common.date') }}</th>
                <th>{{ __('common.customer') }}</th>
                <th class="text-end">{{ __('common.grand_total') }}</th>
                <th>{{ __('common.status') }}</th>
                <th>{{ __('common.actions') }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach($quotes as $q)
              <tr>
                <td>{{ $q->id }}</td>
                <td>{{ $q->quote_number }}</td>
                <td>{{ optional($q->date)->format('Y-m-d') }}</td>
                <td>{{ \App\Models\Customer::displayLabel($q->customer, $q->customer_id, $q->businessId) }}</td>
                <td class="text-end">{{ number_format($q->grand_total,2) }}</td>
                <td><span class="badge badge-secondary">{{ ucfirst($q->status) }}</span></td>
                <td>
                  @if($q->status === 'draft')
                  @canEdit(false)
                  <a class="btn btn-sm btn-warning" href="{{ route('quotation.edit',['id'=>$q->id]) }}"><i class="las la-pen"></i> {{ __('common.edit') }}</a>
                  @endcanEdit
                  @endif
                  <a class="btn btn-sm btn-outline-primary" href="{{ route('quotation.show',['id'=>$q->id]) }}">{{ __('common.view') }}</a>
                  <a class="btn btn-sm btn-outline-secondary" href="{{ route('quotation.print',['id'=>$q->id]) }}" target="_blank">{{ __('common.print') }}</a>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        {{ $quotes->links() }}
      </div>
    </div>
  </div>
</div>
@endmobile
@endsection
