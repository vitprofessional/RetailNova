{{-- Cards of one page of a mobile list: rendered with the page and by the JSON endpoint for "load more". --}}
@php($previousDateGroup = null)
@foreach($cards as $card)
    @if(!empty($card['date_group']) && $previousDateGroup !== ($card['date_group']['key'] ?? null))
        <li class="rn-m-date-heading">{{ $card['date_group']['label'] }}</li>
    @endif
    <x-m.list-card :card="$card" />
    @php($previousDateGroup = $card['date_group']['key'] ?? $previousDateGroup)
@endforeach
