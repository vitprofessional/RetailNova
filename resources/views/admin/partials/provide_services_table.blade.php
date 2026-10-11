<div>
    <div class="table-responsive">
        <table class="table table-bordered">
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
                        <td>{{ $r->customer_name ?? $r->customerName }}</td>
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

        <div class="mt-3">
            {{ $rows->links() }}
        </div>
    </div>
</div>
