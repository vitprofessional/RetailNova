@extends('include')

@section('backTitle')New RMA @endsection
@section('container')
<div class="col-12">
    <h4>Create RMA</h4>
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('rma.store') }}">
                @csrf
                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label">Customer</label>
                        <select name="customer_id" class="form-control form-control-sm">
                            <option value="">(none)</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Serial</label>
                        <input list="serialsList" id="serialInput" class="form-control form-control-sm" placeholder="Type to search serial..." />
                        <datalist id="serialsList">
                            @foreach($serials as $s)
                                <option data-id="{{ $s->id }}" value="{{ $s->serialNumber ?? $s->serial ?? $s->serial_number }}"></option>
                            @endforeach
                        </datalist>
                        <input type="hidden" name="product_serial_id" id="product_serial_id" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control form-control-sm">
                            <option value="open">Open</option>
                            <option value="in_progress">In Progress</option>
                            <option value="resolved">Resolved</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                    <div class="col-12 mt-2">
                        <label class="form-label">Reason</label>
                        <input name="reason" class="form-control form-control-sm" />
                    </div>
                    <div class="col-12 mt-2">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control form-control-sm" rows="4"></textarea>
                    </div>
                    <div class="col-12 mt-3">
                        <button class="btn btn-primary">Create</button>
                        <a href="{{ route('rma.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    // wire the datalist selection to set hidden product_serial_id via AJAX lookup
    $(function(){
        var $input = $('#serialInput');
        var $hidden = $('#product_serial_id');
        var timer = null;
        $input.on('input', function(){
            var v = $(this).val();
            // clear hidden id unless set by click
            $hidden.val('');
            if(timer) clearTimeout(timer);
            timer = setTimeout(function(){
                if(!v) return;
                $.get('{{ url('/ajax/serials') }}', { q: v }, function(res){
                    var list = $('#serialsList'); list.empty();
                    if(res && res.length){
                        res.forEach(function(it){
                            var opt = $('<option/>').attr('data-id', it.id).val(it.serialNumber);
                            list.append(opt);
                        });
                    }
                });
            }, 250);
        });

        // when a value is selected from datalist, resolve to id via ajax lookup
        $input.on('change', function(){
            var v = $(this).val();
            if(!v) return;
            $.get('{{ url('/ajax/serials') }}', { q: v }, function(res){
                if(res && res.length){
                    // if exact match found, set the first id
                    var found = res.find(function(x){ return x.serialNumber === v; }) || res[0];
                    if(found) $hidden.val(found.id);
                }
            });
        });
    });
</script>
@endsection
