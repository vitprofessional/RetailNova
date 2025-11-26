@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1 class="h4 mb-3">Audit Logs</h1>

    @cannot('viewAudits')
        <div class="alert alert-danger">You are not authorized to view audits.</div>
    @endcannot

    <form method="GET" action="{{ route('audits.index') }}" class="card p-3 mb-3">
        <div class="row g-2">
            <div class="col-md-2">
                <label class="form-label">Event</label>
                <select name="event" class="form-select">
                    <option value="">All</option>
                    @foreach($events as $ev)
                        <option value="{{ $ev }}" @selected(request('event')===$ev)>{{ $ev }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Model</label>
                <select name="model" class="form-select">
                    <option value="">All</option>
                    @foreach($models as $m)
                        <option value="{{ $m }}" @selected(request('model')===$m)>{{ class_basename($m) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">User ID</label>
                <input type="text" name="user_id" value="{{ request('user_id') }}" class="form-control" />
            </div>
            <div class="col-md-2">
                <label class="form-label">Date From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control" />
            </div>
            <div class="col-md-2">
                <label class="form-label">Date To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control" />
            </div>
            <div class="col-md-2">
                <label class="form-label">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Text search" />
            </div>
            <div class="col-md-2">
                <label class="form-label">Per Page</label>
                <select name="per_page" class="form-select">
                    @foreach([15,25,50,100] as $pp)
                        <option value="{{ $pp }}" @selected(request('per_page',15)==$pp)>{{ $pp }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100">Filter</button>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <a href="{{ route('audits.index') }}" class="btn btn-secondary w-100">Reset</a>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <a href="{{ route('audits.export', request()->query()) }}" class="btn btn-outline-success w-100">Export CSV</a>
            </div>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-sm table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Event</th>
                    <th>Model</th>
                    <th>User</th>
                    <th>Old</th>
                    <th>New</th>
                    <th>URL</th>
                    <th>IP</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                @forelse($audits as $audit)
                    <tr>
                        <td>{{ $audit->id }}</td>
                        <td><span class="badge bg-info text-dark">{{ $audit->event }}</span></td>
                        <td>{{ class_basename($audit->auditable_type) }}</td>
                        <td>{{ $audit->user_id ?? '—' }}</td>
                                <td style="max-width:200px;">
                                    @php($old = $audit->getOldValues())
                                    @if(empty($old))
                                        <em class="text-muted">∅</em>
                                    @else
                                        @php($oldJson = json_encode($old, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE))
                                        <div class="audit-preview" data-full='@json($oldJson)'>
                                            <pre class="small mb-0 preview-text">{{ Str::limit($oldJson, 200) }}</pre>
                                            @if(strlen($oldJson) > 200)
                                                <a href="#" class="small toggle-audit">Show more</a>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td style="max-width:200px;">
                                    @php($new = $audit->getNewValues())
                                    @if(empty($new))
                                        <em class="text-muted">∅</em>
                                    @else
                                        @php($newJson = json_encode($new, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE))
                                        <div class="audit-preview" data-full='@json($newJson)'>
                                            <pre class="small mb-0 preview-text">{{ Str::limit($newJson, 200) }}</pre>
                                            @if(strlen($newJson) > 200)
                                                <a href="#" class="small toggle-audit">Show more</a>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                        <td class="text-truncate" style="max-width:120px;" title="{{ $audit->url }}">{{ $audit->url }}</td>
                        <td>{{ $audit->ip_address }}</td>
                        <td>{{ $audit->created_at }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted">No audits found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $audits->withQueryString()->links() }}
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('click', function(e){
    if(e.target && e.target.classList && e.target.classList.contains('toggle-audit')){
        e.preventDefault();
        var container = e.target.closest('.audit-preview');
        if(!container) return;
        var full = container.getAttribute('data-full') || '';
        var pre = container.querySelector('.preview-text');
        if(!pre) return;
        if(e.target.textContent.trim().toLowerCase().includes('show')){
            pre.textContent = full;
            e.target.textContent = 'Show less';
        } else {
            pre.textContent = full.substring(0,200) + (full.length>200? '...':'');
            e.target.textContent = 'Show more';
        }
    }
});
</script>
@endpush
</div>
@endsection