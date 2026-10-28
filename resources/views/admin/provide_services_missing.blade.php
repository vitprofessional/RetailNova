@extends('include')

@section('backTitle'){{ __('common.heading_provide_services_missing_data') }}@endsection
@section('container')
<div class="col-12">
    <h4>{{ __('common.provide_services_missing_rate_or_qty') }}</h4>
</div>
<div class="rn-superadmin-page">
    <div class="card">
        <div class="card-body">
            <form id="filterForm" method="GET" action="{{ route('admin.provideServices.missing') }}" class="row align-items-end">
                <div class="col-12 col-md-3 mb-2"><label>{{ __('common.start_date') }}</label><input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}"></div>
                <div class="col-12 col-md-3 mb-2"><label>{{ __('common.end_date') }}</label><input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}"></div>
                <div class="col-12 col-md-4 mb-2"><label>{{ __('common.customer') }}</label><select name="customer_id" class="form-control"><option value="">{{ __('common.all') }}</option>@foreach($customers as $customer)<option value="{{ $customer->id }}" {{ (string)request('customer_id') === (string)$customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>@endforeach</select></div>
                <div class="col-12 col-md-2 mb-2"><button type="submit" class="btn btn-primary w-100">{{ __('common.filter') }}</button></div>
            </form>
            <div class="d-flex justify-content-end my-2"><a class="btn btn-outline-primary" href="{{ route('admin.provideServices.missing.export', request()->query()) }}"><i class="ri-download-line" aria-hidden="true"></i> {{ __('common.export') }}</a></div>
            <div id="reportSpinner" class="d-none" role="status">{{ __('common.loading') }}</div>
            <div id="reportContent">@include('admin.partials.provide_services_table', ['rows' => $rows])</div>
        </div>
    </div>
</div>

@endsection
@section('scripts')
    @parent
    <script>
    // Ensure jQuery-dependent handlers run after jQuery is available
    window.__jqOnReady(function(){
        try{
            (function(){
                var $form = $('#filterForm');
                var $reportContent = $('#reportContent');
                var $spinner = $('#reportSpinner');

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
                    var sameOrigin = href.indexOf(location.origin) === 0 || href.indexOf('/') === 0 || href.indexOf('?') === 0;
                    if(sameOrigin){
                        e.preventDefault();
                        ajaxReplace(href);
                        history.replaceState({}, '', href);
                    }
                });
            })();
        }catch(err){ console.warn('provide_services_missing script failed', err); }
    });
    </script>
@endsection
