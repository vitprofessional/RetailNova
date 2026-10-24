@extends('include')

@section('backTitle'){{ __('common.heading_account_transactions') }}@endsection

@section('container')
@mobile
<section class="rn-m-list-page" data-rn-list="account-transactions">
  <form method="GET" action="{{ route('account.transactions') }}" class="rn-m-filter-fields rn-m-list-filters">
    <x-m.field name="start_date" :label="__('common.start_date')" type="date" :value="request('start_date')" />
    <x-m.field name="end_date" :label="__('common.end_date')" type="date" :value="request('end_date')" />
    <x-m.field name="transaction_type" :label="__('common.transaction_type')" :options="['' => __('common.all_types')] + $transactionTypes" :value="request('transaction_type')" />
    <x-m.field name="account_id" :label="__('common.account')" :options="['' => __('common.all_accounts')] + $accounts->mapWithKeys(fn($a) => [$a->id => $a->account_code.' - '.$a->account_name])->all()" :value="request('account_id')" />
    <button class="rn-m-btn rn-m-btn--primary">{{ __('common.filter') }}</button><a class="rn-m-btn rn-m-btn--outline" href="{{ route('account.transactions') }}">{{ __('common.reset') }}</a>
  </form>
  <ul class="rn-m-cards" role="list">
    @forelse($transactions as $transaction)
      <x-m.list-card :card="['id'=>$transaction->id,'url'=>null,'avatar'=>mb_substr($transaction->reference_no,0,1),'title'=>$transaction->reference_no,'subtitle'=>trim(($transaction->debitAccount?->account_name ?? '').' → '.($transaction->creditAccount?->account_name ?? ''),' →'),'amount'=>\App\Support\LocalizedNumber::money($transaction->amount),'meta'=>array_values(array_filter([$transaction->transaction_date ? \App\Support\MobileDateTime::date($transaction->transaction_date) : null,$transaction->description ?: null,$transaction->creator?->name ?: null])),'badge'=>['label'=>__('common.'.$transaction->transaction_type),'tone'=>'muted'],'actions'=>[]]" />
    @empty
      <li class="rn-m-empty">{{ __('common.no_transactions_found') }}</li>
    @endforelse
  </ul>
  {{ $transactions->appends(request()->query())->links() }}
  <x-m.fab :url="route('account.transactions.create')" :label="__('common.new_transaction')" />
</section>
@else
<div class="col-12">
    @include('sweetalert::alert')
</div>
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">{{ __('common.account_transactions') }}</h4>
            <a href="{{ route('account.transactions.create') }}" class="btn btn-primary btn-sm">
                <i class="las la-plus"></i> {{ __('common.new_transaction') }}
            </a>
        </div>

        {{-- Filters --}}
        <form method="GET" action="{{ route('account.transactions') }}">
            <div class="row mb-4">
                                    <div class="col-md-3">
                                        <label>{{ __('common.start_date') }}</label>
                                        <input type="date" class="form-control" name="start_date" value="{{ request('start_date') }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label>{{ __('common.end_date') }}</label>
                                        <input type="date" class="form-control" name="end_date" value="{{ request('end_date') }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label>{{ __('common.transaction_type') }}</label>
                                        <select class="form-control" name="transaction_type">
                                            <option value="">{{ __('common.all_types') }}</option>
                                            @foreach($transactionTypes as $key => $type)
                                            <option value="{{ $key }}" {{ request('transaction_type') == $key ? 'selected' : '' }}>
                                                {{ $type }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label>{{ __('common.account') }}</label>
                                        <select class="form-control" name="account_id">
                                            <option value="">{{ __('common.all_accounts') }}</option>
                                            @foreach($accounts as $account)
                                            <option value="{{ $account->id }}" {{ request('account_id') == $account->id ? 'selected' : '' }}>
                                                {{ $account->account_code }} - {{ $account->account_name }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary"><i class="las la-filter"></i> {{ __('common.filter') }}</button>
                                <a href="{{ route('account.transactions') }}" class="btn btn-secondary"><i class="las la-redo"></i> {{ __('common.reset') }}</a>
                            </form>

                            <hr>

                            {{-- Transactions Table --}}
                            <div class="table-responsive mt-3">
                                <table class="table table-bordered table-hover rn-table-pro">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>{{ __('common.date') }}</th>
                                            <th>{{ __('common.reference') }}</th>
                                            <th>{{ __('common.type') }}</th>
                                            <th>{{ __('common.debit_account') }}</th>
                                            <th>{{ __('common.credit_account') }}</th>
                                            <th class="text-right">{{ __('common.amount') }}</th>
                                            <th>{{ __('common.description') }}</th>
                                            <th>{{ __('common.created_by') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($transactions as $transaction)
                                        <tr>
                                            <td>{{ $transaction->transaction_date->format('d M Y') }}</td>
                                            <td>{{ $transaction->reference_no }}</td>
                                            <td>
                                                <span class="badge badge-info">{{ ucfirst($transaction->transaction_type) }}</span>
                                            </td>
                                            <td>{{ $transaction->debitAccount->account_code }} - {{ $transaction->debitAccount->account_name }}</td>
                                            <td>{{ $transaction->creditAccount->account_code }} - {{ $transaction->creditAccount->account_name }}</td>
                                            <td class="text-right font-weight-bold">{{ number_format($transaction->amount, 2) }}</td>
                                            <td>{{ \Str::limit($transaction->description, 50) }}</td>
                                            <td>{{ $transaction->creator->name ?? 'System' }}</td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="8" class="text-center">{{ __('common.no_transactions_found') }}</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            {{-- Pagination --}}
                            <div class="mt-3">
                                {{ $transactions->appends(request()->query())->links() }}
                            </div>
                        </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endmobile
@endsection
