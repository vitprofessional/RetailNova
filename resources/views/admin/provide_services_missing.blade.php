@extends('include')

@section('backTitle')Provide Services - Missing Data @endsection
@section('container')
<div class="col-12">
    <h4>Provide Services - Missing Rate or Qty</h4>
</div>
<div class="card">
    <div class="card-body">
        {{-- report content is loaded into #reportContent via AJAX; keep script in scripts section --}}
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
