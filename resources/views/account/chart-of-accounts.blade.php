@extends('include')

@section('backTitle'){{ __('common.heading_chart_of_accounts') }}@endsection

@section('container')
@mobile
<section class="rn-m-list-page" data-rn-list="accounts">
  <ul class="rn-m-cards" role="list">
    @forelse($accounts as $account)
      <x-m.list-card :card="['id'=>$account->id,'url'=>route('account.ledger',$account->id),'avatar'=>\App\Support\LocalizedNumber::digits(mb_substr($account->account_code,0,1)),'title'=>$account->account_name,'subtitle'=>$account->account_code.' · '.ucfirst($account->account_type),'amount'=>\App\Support\LocalizedNumber::money($account->current_balance),'meta'=>array_values(array_filter([$account->parentAccount?->account_name])),'badge'=>['label'=>$account->is_active?__('common.active'):__('common.inactive'),'tone'=>$account->is_active?'success':'muted'],'actions'=>[['type'=>'link','label'=>__('common.view_ledger'),'url'=>route('account.ledger',$account->id),'icon'=>'ri-book-2-line'],['type'=>'link','label'=>__('common.edit'),'url'=>route('account.edit',$account->id),'icon'=>'ri-pencil-line'],['type'=>'link','label'=>__('common.delete'),'url'=>route('account.delete',$account->id),'icon'=>'ri-delete-bin-line']]]" />
    @empty
      <li class="rn-m-empty">{{ __('common.no_accounts_found') }} <a href="{{ route('account.create') }}">{{ __('common.create_your_first_account') }}</a></li>
    @endforelse
  </ul>
  <x-m.fab :url="route('account.create')" :label="__('common.add_new_account')" />
  <section class="rn-m-summary-panel">
    <h2>{{ __('common.account_summary_by_type') }}</h2>
    @foreach([['total_assets','asset'],['total_liabilities','liability'],['total_equity','equity']] as [$label,$type])
      <div class="rn-m-summary-row"><span>{{ __('common.'.$label) }}</span><strong>{{ \App\Support\LocalizedNumber::money($accounts->where('account_type',$type)->sum('current_balance')) }}</strong></div>
    @endforeach
  </section>
</section>
@else
<div class="col-12">
    @include('sweetalert::alert')
</div>
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">{{ __('common.chart_of_accounts') }}</h4>
            <a href="{{ route('account.create') }}" class="btn btn-primary btn-sm">
                <i class="las la-plus"></i> {{ __('common.add_new_account') }}
            </a>
        </div>

        {{-- Filters --}}
        <div class="row mb-4">
            <div class="col-md-4">
                <label>{{ __('common.account_type') }}</label>
                <select class="form-control" id="filter-type">
                    <option value="">{{ __('common.all_types') }}</option>
                    <option value="asset">{{ __('common.asset') }}</option>
                    <option value="liability">{{ __('common.liability') }}</option>
                    <option value="equity">{{ __('common.equity') }}</option>
                    <option value="revenue">{{ __('common.revenue') }}</option>
                    <option value="expense">{{ __('common.expense') }}</option>
                </select>
            </div>
            <div class="col-md-4">
                <label>{{ __('common.status') }}</label>
                <select class="form-control" id="filter-status">
                    <option value="">{{ __('common.all_status') }}</option>
                    <option value="1">{{ __('common.active') }}</option>
                    <option value="0">{{ __('common.inactive') }}</option>
                </select>
            </div>
        </div>

        {{-- Accounts Table --}}
        <div class="table-responsive">
                        <table class="data-tables table mb-0 table-bordered rn-table-pro" id="accounts-table">
                            <thead class="bg-white text-uppercase">
                                <tr>
                                    <th>{{ __('common.code') }}</th>
                                    <th>{{ __('common.account_name') }}</th>
                                    <th>{{ __('common.type') }}</th>
                                    <th>{{ __('common.parent_account') }}</th>
                                    <th>{{ __('common.opening_balance') }}</th>
                                    <th>{{ __('common.current_balance') }}</th>
                                    <th>{{ __('common.status') }}</th>
                                    <th>{{ __('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($accounts as $account)
                                <tr>
                                    <td>{{ $account->account_code }}</td>
                                    <td>
                                        @if($account->parent_account_id)
                                            <span class="ml-3">└─</span>
                                        @endif
                                        {{ $account->account_name }}
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $account->account_type == 'asset' ? 'success' : ($account->account_type == 'liability' ? 'warning' : ($account->account_type == 'equity' ? 'info' : ($account->account_type == 'revenue' ? 'primary' : 'danger'))) }}">
                                            {{ ucfirst($account->account_type) }}
                                        </span>
                                    </td>
                                    <td>{{ $account->parentAccount->account_name ?? '-' }}</td>
                                    <td class="text-right">{{ number_format($account->opening_balance, 2) }}</td>
                                    <td class="text-right">{{ number_format($account->current_balance, 2) }}</td>
                                    <td>
                                        <span class="badge badge-{{ $account->is_active ? 'success' : 'secondary' }}">
                                            {{ $account->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('account.ledger', $account->id) }}"
                                               class="btn btn-sm btn-info"
                                               title="{{ __('common.view_ledger') }}">
                                                <i class="las la-book"></i>
                                            </a>
                                            <a href="{{ route('account.edit', $account->id) }}"
                                               class="btn btn-sm btn-primary"
                                               title="{{ __('common.edit') }}">
                                                <i class="las la-edit"></i>
                                            </a>
                                            <a href="{{ route('account.delete', $account->id) }}"
                                               class="btn btn-sm btn-danger"
                                               title="{{ __('common.delete') }}"
                                               onclick="return confirm('Are you sure you want to delete this account?')">
                                                <i class="las la-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center">{{ __('common.no_accounts_found') }} <a href="{{ route('account.create') }}">{{ __('common.create_your_first_account') }}</a></td>
                                </tr>
                                @endforelse
                            </tbody>
                </table>
            </div>

            {{-- Summary Statistics --}}
            <div class="row mt-4">
                <div class="col-md-12">
                    <h5>{{ __('common.account_summary_by_type') }}</h5>
                </div>
                <div class="col-md-4">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h6>{{ __('common.total_assets') }}</h6>
                            <h3>{{ number_format($accounts->where('account_type', 'asset')->sum('current_balance'), 2) }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h6>{{ __('common.total_liabilities') }}</h6>
                            <h3>{{ number_format($accounts->where('account_type', 'liability')->sum('current_balance'), 2) }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h6>{{ __('common.total_equity') }}</h6>
                            <h3>{{ number_format($accounts->where('account_type', 'equity')->sum('current_balance'), 2) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endmobile
@endsection
