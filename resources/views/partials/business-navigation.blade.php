@php $navigation=app(\App\Services\BusinessNavigation::class)->groups(); @endphp
<ul id="iq-sidebar-toggle" class="iq-menu rn-business-navigation">
@foreach($navigation as $group)
@php
    $active=collect($group['items'])->contains(fn($item)=>request()->routeIs(...$item['active']));
    $single=count($group['items'])===1 && $group['key']===$group['items'][0]['key'];
@endphp
<li class="{{ $active ? 'active' : '' }}">
@if($single)
<a href="{{ route($group['items'][0]['route']) }}" @if($active) aria-current="page" @endif><i class="las {{ $group['icon'] }}" aria-hidden="true"></i><span>{{ __('navigation.'.$group['key']) }}</span></a>
@else
<a href="#{{ $group['id'] }}" class="{{ $active ? '' : 'collapsed' }}" data-toggle="collapse" aria-controls="{{ $group['id'] }}" aria-expanded="{{ $active ? 'true':'false' }}">
<i class="las {{ $group['icon'] }}" aria-hidden="true"></i><span>{{ __('navigation.'.$group['key']) }}</span><i class="las la-angle-down rn-nav-chevron" aria-hidden="true"></i></a>
<ul id="{{ $group['id'] }}" class="iq-submenu collapse{{ $active ? ' show':'' }}" data-parent="#iq-sidebar-toggle">
@foreach($group['items'] as $item)
@php $itemActive=request()->routeIs(...$item['active']); @endphp
<li class="{{ $itemActive ? 'active':'' }}"><a href="{{ route($item['route']) }}" @if($itemActive) aria-current="page" @endif><i class="las {{ $item['icon'] }}" aria-hidden="true"></i><span>{{ __('navigation.'.$item['key']) }}</span></a></li>
@endforeach
</ul>
@endif
</li>
@endforeach
</ul>
<style>
.rn-business-navigation a{display:flex!important;align-items:center;gap:10px;padding:11px 14px!important;white-space:normal!important;min-width:0}
.rn-business-navigation a>i{flex:0 0 20px;font-size:20px;text-align:center;margin:0!important}
.rn-business-navigation a>span{flex:1;min-width:0;line-height:1.45;overflow-wrap:anywhere;font-size:14px;margin:0!important}
.rn-business-navigation .rn-nav-chevron{flex-basis:12px;font-size:14px;transition:transform .2s}
.rn-business-navigation a[aria-expanded="true"]>.rn-nav-chevron{transform:rotate(180deg)}
.rn-business-navigation .iq-submenu a{padding:9px 14px 9px 25px!important}
.rn-business-navigation .active>a{font-weight:600}
.rn-business-navigation>li{margin-bottom:3px}
</style>
