<div>
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Customer</th>
                    <th>Service</th>
                    <th>Amount</th>
                    <th>Qty</th>
                    <th>Rate</th>
                    <th>Note</th>
                    <th>Created</th>
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
                    <tr><td colspan="8" class="text-center">No rows found</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-3">
            {{ $rows->links() }}
        </div>
    </div>
</div>
