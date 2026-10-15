<header class="rn-sale-header">
<div><nav class="rn-sale-breadcrumb" aria-label="{{ __('sales.sale_navigation') }}"><a href="{{ route('saleList') }}">{{ __('navigation.sales_management') }}</a><span aria-hidden="true">/</span><span aria-current="page">{{ __('sales.new_sale') }}</span></nav>
<h1><i class="las la-shopping-cart rn-sale-title-icon" aria-hidden="true"></i>{{ __('sales.new_sale') }}</h1><p>{{ __('sales.new_sale_context') }}</p></div>
<a class="btn btn-outline-secondary" data-sale-back href="{{ route('saleList') }}">{{ __('sales.back_to_sales_list') }}</a>
</header>
