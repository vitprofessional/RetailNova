<section class="rn-m-master-cards" aria-label="{{ $title }}">
    @forelse($records as $record)
        <article class="rn-m-master-card">
            <div class="rn-m-master-card__copy">
                <strong>{{ $record['title'] }}</strong>
                @if(!empty($record['detail']))<small>{{ $record['detail'] }}</small>@endif
                @if(isset($record['amount']))<span class="rn-m-master-card__amount">{{ $record['amount'] }}</span>@endif
            </div>
            <details class="rn-m-master-menu">
                <summary aria-label="{{ __('mobile.actions') }}">⋮</summary>
                <div class="rn-m-master-menu__items">
                    <a href="{{ $record['edit'] }}">{{ __('mobile.edit') }}</a>
                    <form action="{{ $record['delete'] }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" data-confirm="{{ $record['confirm'] }}">{{ __('mobile.delete') }}</button>
                    </form>
                </div>
            </details>
        </article>
    @empty
        <div class="rn-m-empty-state">{{ __('mobile.list_empty') }}</div>
    @endforelse
    <a class="rn-m-master-fab" href="#rnMobileMasterForm"><span aria-hidden="true">+</span> {{ __('mobile.add_new') }}</a>
</section>
