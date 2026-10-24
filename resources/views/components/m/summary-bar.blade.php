{{-- Three figures above a mobile list, e.g. sales · received · due (UI-2). Items: [label, value, tone?]. --}}
@props(['items' => []])
@if($items)
    <dl {{ $attributes->merge(['class' => 'rn-m-summary']) }}>
        @foreach($items as $item)
            <div class="rn-m-summary-item">
                <dt>{{ $item['label'] }}</dt>
                <dd class="is-{{ $item['tone'] ?? 'ink' }}">{{ $item['value'] }}</dd>
            </div>
        @endforeach
    </dl>
@endif
