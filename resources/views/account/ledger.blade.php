@extends('include')

@section('backTitle'){{ __('common.heading_account_ledger') }}@endsection

@section('container')
@mobile
<section class="rn-m-list-page" data-rn-list="account-ledger">
  <header class="rn-m-list-header"><h2>{{ $account->account_code }} · {{ $account->account_name }}</h2><div class="rn-m-summary-row"><span>{{ __('common.current_balance') }}</span><strong>{{ \App\Support\LocalizedNumber::money($account->current_balance) }}</strong></div></header>
  <form method="GET" action="{{ route('account.ledger', $account->id) }}" class="rn-m-filter-fields rn-m-list-filters">
    <x-m.field name="start_date" :label="__('common.start_date')" type="date" :value="request('start_date')" /><x-m.field name="end_date" :label="__('common.end_date')" type="date" :value="request('end_date')" />
    <button class="rn-m-btn rn-m-btn--primary">{{ __('common.filter') }}</button><a class="rn-m-btn rn-m-btn--outline" href="{{ route('account.ledger', $account->id) }}">{{ __('common.reset') }}</a><a class="rn-m-btn rn-m-btn--outline" href="{{ route('account.chart') }}">{{ __('common.back') }}</a>
  </form>
  <ul class="rn-m-cards" role="list">
    @forelse($ledgerEntries as $entry)
      <x-m.list-card :card="['id'=>sha1(($entry['reference'] ?? '').($entry['date'] ?? '')),'url'=>null,'avatar'=>mb_substr((string)($entry['reference'] ?: '•'),0,1),'title'=>$entry['reference'] ?: __('common.reference'),'subtitle'=>$entry['description'] ?: null,'amount'=>\App\Support\LocalizedNumber::money($entry['balance']),'meta'=>array_values(array_filter([$entry['date'] ? \App\Support\MobileDateTime::date($entry['date']) : null,__('common.debit').': '.\App\Support\LocalizedNumber::money($entry['debit']),__('common.credit').': '.\App\Support\LocalizedNumber::money($entry['credit'])])),'badge'=>null,'actions'=>[]]" />
    @empty
      <li class="rn-m-empty">{{ __('common.no_transactions_in_this_period') }}</li>
    @endforelse
  </ul>
</section>
@else
<div class="col-12">
    @include('sweetalert::alert')
</div>
<div class="card">
    <div class="card-body">
        <div class="mb-4">
            <h4>{{ __('common.account_ledger') }}</h4>
            <p class="mb-1"><strong>{{ $account->account_code }} - {{ $account->account_name }}</strong></p>
            <p class="mb-0">{{ __('common.type') }}: <span class="badge badge-info">{{ ucfirst($account->account_type) }}</span></p>
        </div>

        {{-- Filters --}}
        <form method="GET" action="{{ route('account.ledger', $account->id) }}">
            <div class="row mb-4">
                                    <div class="col-md-4">
                                        <label>{{ __('common.start_date') }}</label>
                                        <input type="date" class="form-control" name="start_date" value="{{ request('start_date') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label>{{ __('common.end_date') }}</label>
                                        <input type="date" class="form-control" name="end_date" value="{{ request('end_date') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label>&nbsp;</label><br>
                                        <button type="submit" class="btn btn-primary"><i class="las la-filter"></i> {{ __('common.filter') }}</button>
                                        <a href="{{ route('account.ledger', $account->id) }}" class="btn btn-secondary"><i class="las la-redo"></i> {{ __('common.reset') }}</a>
                                        <a href="{{ route('account.chart') }}" class="btn btn-secondary"><i class="las la-arrow-left"></i> {{ __('common.back') }}</a>
                                    </div>
                                </div>
                            </form>

                            <hr>

                            {{-- Account Summary --}}
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6>{{ __('common.opening_balance') }}</h6>
                                            <h4>{{ number_format($account->opening_balance, 2) }}</h4>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-success text-white">
                                        <div class="card-body">
                                            <h6>{{ __('common.current_balance') }}</h6>
                                            <h4>{{ number_format($account->current_balance, 2) }}</h4>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-info text-white">
                                        <div class="card-body">
                                            <h6>{{ __('common.total_transactions') }}</h6>
                                            <h4>{{ count($ledgerEntries) }}</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Ledger Table --}}
                            <div class="table-responsive">
                                <table class="table table-bordered rn-table-pro">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th>{{ __('common.date') }}</th>
                                            <th>{{ __('common.reference') }}</th>
                                            <th>{{ __('common.description') }}</th>
                                            <th class="text-right">{{ __('common.debit') }}</th>
                                            <th class="text-right">{{ __('common.credit') }}</th>
                                            <th class="text-right">{{ __('common.balance') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="bg-light font-weight-bold">
                                            <td colspan="5" class="text-right">{{ __('common.opening_balance') }}:</td>
                                            <td class="text-right">{{ number_format($account->opening_balance, 2) }}</td>
                                        </tr>
                                        @forelse($ledgerEntries as $entry)
                                        <tr>
                                            <td>{{ app(\App\Support\MobileView::class)->isMobile() ? \App\Support\MobileDateTime::date($entry['date']) : $entry['date']->format('d M Y') }}</td>
                                            <td>{{ $entry['reference'] }}</td>
                                            <td>{{ $entry['description'] }}</td>
                                            <td class="text-right">{{ $entry['debit'] > 0 ? number_format($entry['debit'], 2) : '-' }}</td>
                                            <td class="text-right">{{ $entry['credit'] > 0 ? number_format($entry['credit'], 2) : '-' }}</td>
                                            <td class="text-right font-weight-bold">{{ number_format($entry['balance'], 2) }}</td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="6" class="text-center">{{ __('common.no_transactions_in_this_period') }}</td>
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
