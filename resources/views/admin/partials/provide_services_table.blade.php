<div>
    <div class="table-responsive">
        <table class="table table-bordered rn-superadmin-missing-desktop">
            <thead>
                <tr>
                    <th>{{ __('common.id') }}</th>
                    <th>{{ __('common.customer') }}</th>
                    <th>{{ __('common.service') }}</th>
                    <th>{{ __('common.amount') }}</th>
                    <th>{{ __('common.qty') }}</th>
                    <th>{{ __('common.rate') }}</th>
                    <th>{{ __('common.note') }}</th>
                    <th>{{ __('common.created') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $r)
                    <tr>
                        <td>{{ $r->id }}</td>
                        <td>{{ $r->customerLabel() }}</td>
                        <td>{{ $r->serviceName }}</td>
                        <td>@currency($r->amount)</td>
                        <td>{{ $r->qty ?? '' }}</td>
                        <td>@currency($r->rate ?? 0)</td>
                        <td>{{ $r->note }}</td>
                        <td>{{ $r->created_at }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center">{{ __('common.no_rows_found') }}</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="rn-superadmin-mobile-list" aria-label="{{ __('common.provide_services_missing_rate_or_qty') }}">
            @forelse($rows as $r)
                <article class="rn-superadmin-card">
                    <div class="rn-superadmin-card__main">
                        <strong>{{ $r->serviceName }}</strong>
                        <span>{{ $r->customerLabel() }} · {{ optional($r->created_at)->format('d/m/Y') }}</span>
                        <span>{{ __('common.amount') }}: @currency($r->amount)</span>
                        <span>{{ __('common.qty') }}: {{ $r->qty ?? '—' }} · {{ __('common.rate') }}: @currency($r->rate ?? 0)</span>
                        @if($r->note)<small>{{ $r->note }}</small>@endif
                    </div>
                </article>
            @empty
                <div class="rn-m-empty-state">{{ __('common.no_rows_found') }}</div>
            @endforelse
        </div>

        <div class="mt-3">
            {{ $rows->links() }}
        </div>
    </div>
</div>
