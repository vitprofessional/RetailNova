<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use OwenIt\Auditing\Models\Audit;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        if (!Gate::allows('viewAudits')) {
            abort(403,'Unauthorized');
        }

        $query = Audit::query()->orderByDesc('id');

        // Filters
        if ($event = $request->get('event')) {
            $query->where('event', $event);
        }
        if ($userId = $request->get('user_id')) {
            $query->where('user_id', $userId);
        }
        if ($model = $request->get('model')) {
            $query->where('auditable_type', $model);
        }
        if ($from = $request->get('date_from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->get('date_to')) {
            $query->whereDate('created_at', '<=', $to);
        }
        if ($search = trim((string)$request->get('search'))) {
            $query->where(function($q) use ($search){
                $q->where('old_values','LIKE',"%$search%");
                $q->orWhere('new_values','LIKE',"%$search%");
            });
        }

        $perPage = (int)($request->get('per_page', 25));
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 25;

        $audits = $query->paginate($perPage)->appends($request->query());

        // Distinct lists for quick selection
        $distinctEvents = Audit::select('event')->distinct()->pluck('event')->sort()->values();
        $distinctModels = Audit::select('auditable_type')->distinct()->pluck('auditable_type')->sort()->values();

        return view('audits.index', [
            'audits' => $audits,
            'events' => $distinctEvents,
            'models' => $distinctModels,
        ]);
    }

    /**
     * Export audits matching the current filters as CSV.
     */
    public function export(Request $request)
    {
        if (!Gate::allows('viewAudits')) {
            abort(403,'Unauthorized');
        }

        $query = Audit::query()->orderByDesc('id');

        // apply same filters as index
        if ($event = $request->get('event')) $query->where('event', $event);
        if ($userId = $request->get('user_id')) $query->where('user_id', $userId);
        if ($model = $request->get('model')) $query->where('auditable_type', $model);
        if ($from = $request->get('date_from')) $query->whereDate('created_at', '>=', $from);
        if ($to = $request->get('date_to')) $query->whereDate('created_at', '<=', $to);
        if ($search = trim((string)$request->get('search'))) {
            $query->where(function($q) use ($search){
                $q->where('old_values','LIKE',"%$search%");
                $q->orWhere('new_values','LIKE',"%$search%");
            });
        }

        $fileName = 'audits-'.now()->format('Ymd-His').'.csv';

        $callback = function() use ($query) {
            $handle = fopen('php://output', 'w');
            // header row
            fputcsv($handle, ['id','event','auditable_type','user_id','old_values','new_values','url','ip_address','created_at']);

            $query->chunk(200, function($rows) use ($handle) {
                foreach ($rows as $r) {
                    $old = json_encode($r->getOldValues(), JSON_UNESCAPED_UNICODE);
                    $new = json_encode($r->getNewValues(), JSON_UNESCAPED_UNICODE);
                    fputcsv($handle, [
                        $r->id,
                        $r->event,
                        $r->auditable_type,
                        $r->user_id,
                        $old,
                        $new,
                        $r->url,
                        $r->ip_address,
                        $r->created_at,
                    ]);
                }
            });

            fclose($handle);
        };

        return response()->streamDownload($callback, $fileName, [
            'Content-Type' => 'text/csv; charset=utf-8',
        ]);
    }
}
