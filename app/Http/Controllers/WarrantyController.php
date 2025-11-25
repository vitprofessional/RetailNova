<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WarrantyController extends Controller
{
    /**
     * Show RMA management page (placeholder).
     */
    public function rma()
    {
        return view('warranty.rma');
    }

    /**
     * Show serials list page.
     */
    public function serialList()
    {
        $query = \App\Models\ProductSerial::query();
        if(request('q')){
            $q = trim(request('q'));
            $query->where('serialNumber', 'like', "%{$q}%");
        }
        $serials = $query->orderBy('id','desc')->paginate(25)->appends(request()->query());
        return view('warranty.serial_list', compact('serials'));
    }

    /**
     * AJAX endpoint: return JSON list of serials matching query
     */
    public function ajaxSerials(Request $request)
    {
        $q = $request->input('q','');
        $items = \App\Models\ProductSerial::when($q, function($qq) use ($q){
            $qq->where('serialNumber','like', "%{$q}%");
        })->orderBy('id','desc')->limit(50)->get(['id','serialNumber']);

        return response()->json($items);
    }

    /**
     * Export serials CSV (respects q filter)
     */
    public function exportSerials(Request $request)
    {
        $q = $request->input('q','');
        $query = \App\Models\ProductSerial::query();
        if($q) $query->where('serialNumber','like', "%{$q}%");
        $rows = $query->orderBy('id','desc')->get();

        $filename = 'serials_' . date('Ymd_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($rows) {
            $out = fopen('php://output','w');
            fputcsv($out, ['id','serialNumber','productName','created_at']);
            foreach($rows as $r){
                fputcsv($out, [
                    $r->id,
                    $r->serialNumber ?? $r->serial ?? $r->serial_number,
                    $r->productName ?? '',
                    optional($r->created_at)->format('Y-m-d H:i:s'),
                ]);
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }
}
