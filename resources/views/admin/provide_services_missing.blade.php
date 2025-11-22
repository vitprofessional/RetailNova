@extends('include')

@section('backTitle')Provide Services - Missing Data @endsection
@section('container')
<div class="col-12">
    <h4>Provide Services - Missing Rate or Qty</h4>
</div>
<div class="card">
    <div class="card-body">
        <form method="GET" id="filterForm" class="mb-3">
            <div class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label">Start date</label>
                    <input type="date" name="start_date" value="{{ request('start_date') }}" class="form-control form-control-sm" />
                </div>
                <div class="col-auto">
                    <label class="form-label">End date</label>
                    <input type="date" name="end_date" value="{{ request('end_date') }}" class="form-control form-control-sm" />
                </div>
                <div class="col-auto">
                    <label class="form-label">Customer</label>
                    <select name="customer_id" class="form-control form-control-sm">
                        <option value="">All</option>
                        @if(!empty($customers))
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" @if(request('customer_id') == $c->id) selected @endif>{{ $c->name }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary btn-sm">Filter</button>
                    <a href="{{ route('admin.provideServices.missing') }}" class="btn btn-secondary btn-sm">Reset</a>
                    <a href="{{ route('admin.provideServices.missing.export', request()->all()) }}" class="btn btn-sm btn-success">Export CSV</a>
                </div>
            </div>
        </form>
        <div id="reportContent">
            @include('admin.partials.provide_services_table')
        </div>
        <div id="reportSpinner" class="d-none rn-report-spinner">
            <div class="rn-spinner-inner">
                <div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>
            </div>
        </div>
    </div>
</div>
<script>
    (function(){
        const $form = $('#filterForm');
        const $reportContent = $('#reportContent');
        const $spinner = $('#reportSpinner');

        function showSpinner(){ $spinner.removeClass('d-none').css('display','flex'); }
        function hideSpinner(){ $spinner.addClass('d-none').css('display','none'); }

        function ajaxReplace(url){
            showSpinner();
            $.ajax({
                url: url,
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                dataType: 'html'
            }).done(function(html){
                $reportContent.html(html);
            }).fail(function(xhr, status, err){
                console.error('Failed to load report:', status, err);
            }).always(function(){
                hideSpinner();
            });
        }

        $form.on('submit', function(e){
            e.preventDefault();
            var query = $form.serialize();
            var url = location.pathname + (query ? ('?' + query) : '');
            ajaxReplace(url);
            history.replaceState({}, '', url);
        });

        // auto-submit on date change
        $form.find('input[type=date], select[name=customer_id]').on('change', function(){ $form.trigger('submit'); });

        // delegate pagination links inside reportContent
        $reportContent.on('click', 'a', function(e){
            var href = $(this).attr('href');
            if(!href) return;
            // only intercept internal links (same path or query)
            var sameOrigin = href.indexOf(location.origin) === 0 || href.indexOf('/') === 0 || href.indexOf('?') === 0;
            if(sameOrigin){
                e.preventDefault();
                ajaxReplace(href);
                history.replaceState({}, '', href);
            }
        });
    })();
</script>
@endsection
