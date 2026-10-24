@extends('include')

@section('title','New RMA')
@section('backTitle'){{ __('common.heading_new_rma') }}@endsection
@section('container')
<div class="col-12 rn-m-rma-page">
    <h4 class="rn-m-rma-title">{{ __('common.create_rma') }}</h4>
    <div class="card rn-m-rma-card">
        <div class="card-body">
            <form method="POST" action="{{ route('rma.store') }}" class="rn-m-form rn-m-rma-form">
                @csrf
                <div class="row g-2 rn-m-form-section rn-m-rma-fields">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('common.customer') }}</label>
                        <select name="customer_id" class="form-control form-control-sm">
                            <option value="">{{ __('common.none') }}</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                        @error('customer_id')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('common.serial') }}</label>
                        <input list="serialsList" name="serial_display" id="serialInput" class="form-control form-control-sm" value="{{ old('serial_display') }}" placeholder="{{ __('common.type_to_search_serial') }}" />
                        <datalist id="serialsList">
                            @foreach($serials as $s)
                                <option data-id="{{ $s->id }}" value="{{ $s->serialNumber ?? $s->serial ?? $s->serial_number }}"></option>
                            @endforeach
                        </datalist>
                        <input type="hidden" name="product_serial_id" id="product_serial_id" value="{{ old('product_serial_id') }}" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('common.status') }}</label>
                        <select name="status" class="form-control form-control-sm">
                            <option value="open">{{ __('common.open') }}</option>
                            <option value="in_progress">{{ __('common.in_progress') }}</option>
                            <option value="resolved">{{ __('common.resolved') }}</option>
                            <option value="closed">{{ __('common.closed') }}</option>
                        </select>
                    </div>
                    <div class="col-12 mt-2">
                        <label class="form-label">{{ __('common.reason') }}</label>
                        <input name="reason" class="form-control form-control-sm" />
                        @error('reason')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="col-12 mt-2">
                        <label class="form-label">{{ __('common.notes') }}</label>
                        <textarea name="notes" class="form-control form-control-sm" rows="4"></textarea>
                        @error('notes')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="rn-m-sticky-actions rn-m-rma-actions">
                        <a href="{{ route('rma.index') }}" class="rn-m-btn rn-m-btn--outline">{{ __('common.cancel') }}</a>
                        <button type="submit" class="rn-m-btn rn-m-btn--primary">{{ __('common.create') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
@section('scripts')
    @parent
    <script>
        (function(){
            // Robust serial input handling: prefer local datalist lookup, fallback to AJAX/fetch.
            try{
                var serialInput = document.getElementById('serialInput');
                var serialHidden = document.getElementById('product_serial_id');
                var datalist = document.getElementById('serialsList');
                var debounceTimer = null;

                function clearHidden(){ if(serialHidden) serialHidden.value = ''; }

                function setHiddenFromDatalist(value){
                    if(!datalist) return false;
                    var opts = datalist.querySelectorAll('option');
                    for(var i=0;i<opts.length;i++){
                        if(opts[i].value === value){
                            var id = opts[i].getAttribute('data-id') || opts[i].getAttribute('data-id');
                            if(id && serialHidden) serialHidden.value = id;
                            return true;
                        }
                    }
                    return false;
                }

                function fetchSerials(q){
                    var url = '{{ url('/ajax/serials') }}?q=' + encodeURIComponent(q);
                    // prefer fetch
                    return fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(function(res){ if(!res.ok) throw new Error('Network response not ok'); return res.json(); });
                }

                function populateDatalist(items){
                    if(!datalist) return;
                    // remove existing options
                    while(datalist.firstChild) datalist.removeChild(datalist.firstChild);
                    items.forEach(function(it){
                        var opt = document.createElement('option');
                        opt.value = it.serialNumber || it.serial || it.serial_number || '';
                        opt.setAttribute('data-id', it.id || '');
                        datalist.appendChild(opt);
                    });
                }

                function onInput(){
                    var v = serialInput.value || '';
                    clearHidden();
                    if(debounceTimer) clearTimeout(debounceTimer);
                    if(!v) return;
                    // If we can find exact match in datalist, use it immediately
                    if(setHiddenFromDatalist(v)) return;

                    debounceTimer = setTimeout(function(){
                        fetchSerials(v).then(function(res){
                            if(Array.isArray(res) && res.length){
                                populateDatalist(res);
                                // try again to set hidden from freshly populated datalist
                                setHiddenFromDatalist(v);
                            }
                        }).catch(function(e){ console.warn('serial lookup failed', e); });
                    }, 250);
                }

                function onChange(){
                    var v = serialInput.value || '';
                    if(!v) return;
                    if(setHiddenFromDatalist(v)) return;
                    fetchSerials(v).then(function(res){
                        if(Array.isArray(res) && res.length){
                            // if exact match exists, set id; otherwise pick first
                            var found = res.find(function(x){ return (x.serialNumber || x.serial || x.serial_number) === v; }) || res[0];
                            if(found && serialHidden) serialHidden.value = found.id;
                            populateDatalist(res);
                        }
                    }).catch(function(e){ console.warn('serial lookup failed', e); });
                }

                // restore hidden + text value from old() after validation redirect
                try{
                    var oldHidden = "{{ old('product_serial_id') }}";
                    var oldSerialText = "{{ old('serial_display') }}";
                    if(oldHidden && serialHidden) serialHidden.value = oldHidden;
                    if(oldSerialText && serialInput) serialInput.value = oldSerialText;
                }catch(e){}

                if(serialInput){
                    serialInput.addEventListener('input', onInput, { passive: true });
                    serialInput.addEventListener('change', onChange);
                }
            }catch(err){ console.warn('rma_create script failed', err); }
        })();
    </script>
@endsection
