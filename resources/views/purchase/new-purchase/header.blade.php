<header class="rn-purchase-header">
 <div><nav class="rn-purchase-breadcrumb" aria-label="{{ __('purchases.purchase_navigation') }}"><a href="{{ route('purchaseList') }}">{{ __('navigation.purchase_management') }}</a><span aria-hidden="true">&rsaquo;</span><span aria-current="page">{{ __('navigation.new_purchase') }}</span></nav>
 <h1>{{ __('purchases.create_new_purchase') }}</h1><p>{{ __('purchases.new_purchase_context') }}</p></div>
 <a class="btn btn-outline-secondary" href="{{ route('purchaseList') }}" data-purchase-leave><i class="las la-arrow-left mr-2" aria-hidden="true"></i>{{ __('purchases.back_to_history') }}</a>
</header>
