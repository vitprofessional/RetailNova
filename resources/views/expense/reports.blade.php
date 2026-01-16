@extends('include')

@section('backTitle')Expense Reports @endsection

@section('container')
<div class="col-12">
    @include('sweetalert::alert')
</div>
<div class="card">
    <div class="card-body">
        <h4 class="mb-4">Expense Reports</h4>

        <form method="GET" action="{{ route('expense.reports') }}">
            <div class="row mb-4">
                                    <div class="col-md-3">
                                        <label>Start Date</label>
                                        <input type="date" class="form-control" name="start_date" value="{{ $startDate }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label>End Date</label>
                                        <input type="date" class="form-control" name="end_date" value="{{ $endDate }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label>Group By</label>
                                        <select class="form-control" name="group_by">
                                            <option value="category" {{ $groupBy == 'category' ? 'selected' : '' }}>Category</option>
                                            <option value="payment_method" {{ $groupBy == 'payment_method' ? 'selected' : '' }}>Payment Method</option>
                                            <option value="date" {{ $groupBy == 'date' ? 'selected' : '' }}>Date</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label>&nbsp;</label><br>
                                        <button type="submit" class="btn btn-primary"><i class="las la-chart-bar"></i> Generate</button>
                                        <button type="button" class="btn btn-print" onclick="window.print()"><i class="las la-print"></i> Print</button>
                                    </div>
                                </div>
                            </form>

                            <hr>

                            <div class="alert alert-info">
                                <strong>Total Expenses:</strong> {{ number_format($totalExpense, 2) }}
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="thead-dark">
                                        <tr>
                                            @if($groupBy == 'category')
                                                <th>Category</th>
                                            @elseif($groupBy == 'payment_method')
                                                <th>Payment Method</th>
                                            @elseif($groupBy == 'date')
                                                <th>Date</th>
                                            @endif
                                            <th class="text-right">Total Amount</th>
                                            <th class="text-right">Percentage</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($data as $item)
                                        <tr>
                                            <td>
                                                @if($groupBy == 'category')
                                                    {{ $item->category->name ?? 'Unknown' }}
                                                @elseif($groupBy == 'payment_method')
                                                    {{ ucfirst($item->payment_method) }}
                                                @elseif($groupBy == 'date')
                                                    {{ \Carbon\Carbon::parse($item->expense_date)->format('d M Y') }}
                                                @endif
                                            </td>
                                            <td class="text-right font-weight-bold">{{ number_format($item->total, 2) }}</td>
                                            <td class="text-right">{{ $totalExpense > 0 ? number_format(($item->total / $totalExpense) * 100, 2) : 0 }}%</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="font-weight-bold">
                                        <tr>
                                            <td class="text-right">Total:</td>
                                            <td class="text-right">{{ number_format($totalExpense, 2) }}</td>
                                            <td class="text-right">100%</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            {{-- Chart visualization (optional - requires Chart.js) --}}
                            <div class="mt-4">
                                <canvas id="expenseChart" height="100"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('expenseChart').getContext('2d');
    const expenseChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: [
                @foreach($data as $item)
                    @if($groupBy == 'category')
                        '{{ $item->category->name ?? "Unknown" }}',
                    @elseif($groupBy == 'payment_method')
                        '{{ ucfirst($item->payment_method) }}',
                    @elseif($groupBy == 'date')
                        '{{ \Carbon\Carbon::parse($item->expense_date)->format("d M") }}',
                    @endif
                @endforeach
            ],
            datasets: [{
                label: 'Expense Amount',
                data: [
                    @foreach($data as $item)
                        {{ $item->total }},
                    @endforeach
                ],
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
        </script>
    </div>
</div>
@endsection
