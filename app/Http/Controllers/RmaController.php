<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Rma;
use App\Models\Customer;
use App\Models\ProductSerial;
use Illuminate\Support\Facades\Auth;

class RmaController extends Controller
{
    public function index(Request $request)
    {
        $q = Rma::with(['customer','productSerial']);

        if ($request->filled('status')) {
            $q->where('status', $request->input('status'));
        }
        if ($request->filled('customer_id')) {
            $q->where('customer_id', $request->input('customer_id'));
        }

        $rmas = $q->orderBy('created_at', 'desc')->paginate(25)->appends($request->query());
        $customers = Customer::orderBy('name')->get();

        return view('warranty.rma_index', compact('rmas','customers'));
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        $serials = ProductSerial::orderBy('id','desc')->limit(50)->get();
        return view('warranty.rma_create', compact('customers','serials'));
    }

    public function store(Request $request)
    {
        $validStatuses = ['open','in_progress','resolved','closed'];
        $data = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'product_serial_id' => 'nullable|exists:product_serials,id',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'status' => ['nullable','string','in:' . implode(',', $validStatuses)],
        ]);

        $data['created_by'] = Auth::id() ?: null;

        // If status is resolved/closed but no resolved_at provided, set resolved_at now
        if (isset($data['status']) && in_array($data['status'], ['resolved','closed']) && empty($data['resolved_at'])) {
            $data['resolved_at'] = now();
        }

        $rma = Rma::create($data);

        return redirect()->route('rma.index')->with('success', 'RMA created');
    }

    public function edit($id)
    {
        $rma = Rma::findOrFail($id);
        $customers = Customer::orderBy('name')->get();
        $serials = ProductSerial::orderBy('id','desc')->limit(50)->get();
        return view('warranty.rma_edit', compact('rma','customers','serials'));
    }

    public function update(Request $request, $id)
    {
        $rma = Rma::findOrFail($id);
        $validStatuses = ['open','in_progress','resolved','closed'];
        $data = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'product_serial_id' => 'nullable|exists:product_serials,id',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'status' => ['nullable','string','in:' . implode(',', $validStatuses)],
            'resolved_at' => 'nullable|date',
        ]);

        // If status moved to resolved/closed and resolved_at isn't set, mark resolved_at
        if (isset($data['status']) && in_array($data['status'], ['resolved','closed']) && empty($data['resolved_at'])) {
            $data['resolved_at'] = now();
        }

        $rma->update($data);
        return redirect()->route('rma.index')->with('success', 'RMA updated');
    }

    public function destroy($id)
    {
        $rma = Rma::findOrFail($id);
        $rma->delete();
        return redirect()->route('rma.index')->with('success', 'RMA deleted');
    }

    /**
     * Export RMA list as CSV (respects current filters).
     */
    public function export(Request $request)
    {
        $query = Rma::with(['customer','productSerial']);
        if ($request->filled('status')) $query->where('status', $request->input('status'));
        if ($request->filled('customer_id')) $query->where('customer_id', $request->input('customer_id'));
        $rows = $query->orderBy('created_at','desc')->get();

        $filename = 'rmas_' . date('Ymd_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        $columns = ['id','customer','serial','reason','status','notes','created_at'];

        $callback = function() use ($rows, $columns) {
            $out = fopen('php://output','w');
            fputcsv($out, $columns);
            foreach($rows as $r){
                fputcsv($out, [
                    $r->id,
                    $r->customer->name ?? '',
                    optional($r->productSerial)->serialNumber ?? '',
                    $r->reason,
                    $r->status,
                    $r->notes,
                    optional($r->created_at)->format('Y-m-d H:i:s'),
                ]);
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }
}
