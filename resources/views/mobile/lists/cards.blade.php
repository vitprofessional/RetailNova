{{-- Cards of one page of a mobile list: rendered with the page and by the JSON endpoint for "load more". --}}
@foreach($cards as $card)
    <x-m.list-card :card="$card" />
@endforeach
