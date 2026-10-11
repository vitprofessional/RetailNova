@extends('include')
@section('backTitle'){{ __('common.heading_quotations') }}@endsection
@section('container')
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
                <td>{{ optional($q->customer)->name ?? 'Walking Customer' }}</td>
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
@endsection
