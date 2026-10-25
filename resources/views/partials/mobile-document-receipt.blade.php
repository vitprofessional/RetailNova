@php($receiptItems = collect($receipt['items'] ?? []))
<main class="rn-m-detail-page rn-m-document-receipt" data-mobile-document-receipt @if(!empty($receipt['sale'])) data-mobile-sale-invoice @endif>
    <header class="rn-m-detail-summary">
        <span class="rn-m-detail-summary__status">{{ $receipt['status'] }}</span>
        <strong>{{ $receipt['amount'] }}</strong>
        <small>{{ $receipt['document_label'] }} · {{ $receipt['document_number'] }}</small>
    </header>
    <section class="rn-m-invoice-receipt-card">
        <div class="rn-m-invoice-customer">
            <span>{{ $receipt['party_label'] }}</span>
            <strong>{{ $receipt['party_name'] }}</strong>
            @if(!empty($receipt['party_meta']))<small>{{ $receipt['party_meta'] }}</small>@endif
        </div>
        <div class="rn-m-invoice-lines" data-invoice-lines>
            @forelse($receiptItems as $line)
                <article class="rn-m-invoice-line" data-invoice-product>
                    <div><strong>{{ $line['title'] }}</strong>@if(!empty($line['detail']))<small>{{ $line['detail'] }}</small>@endif</div>
                    <strong>{{ $line['amount'] }}</strong>
                </article>
            @empty
                <p class="rn-m-invoice-empty">{{ $receipt['empty_label'] }}</p>
            @endforelse
        </div>
        <dl class="rn-m-invoice-totals">
            @foreach($receipt['totals'] as $total)
                <div @if(!empty($total['is_total']))class="is-total"@endif><dt>{{ $total['label'] }}</dt><dd>{{ $total['amount'] }}</dd></div>
            @endforeach
        </dl>
    </section>
    <nav class="rn-m-invoice-actions" aria-label="{{ __('common.actions') }}">
        @foreach($receipt['actions'] as $action)
            @if(isset($action['url']))
                <a href="{{ $action['url'] }}" aria-label="{{ $action['label'] }}"><i class="{{ $action['icon'] }}" aria-hidden="true"></i><small>{{ $action['label'] }}</small></a>
            @else
                <button type="button" data-receipt-action="{{ $action['type'] }}" aria-label="{{ $action['label'] }}"><i class="{{ $action['icon'] }}" aria-hidden="true"></i><small>{{ $action['label'] }}</small></button>
            @endif
        @endforeach
    </nav>
    <div class="rn-m-document-receipt-ctas">
        @if(!empty($receipt['primary_url']))<a class="rn-m-invoice-primary" href="{{ $receipt['primary_url'] }}">{{ $receipt['primary_label'] }}<i class="ri-arrow-right-line" aria-hidden="true"></i></a>@endif
        @if(!empty($receipt['secondary_url']))<a class="rn-m-invoice-secondary" href="{{ $receipt['secondary_url'] }}">{{ $receipt['secondary_label'] }}</a>@endif
    </div>
</main>
<script>
(() => {
    const receipt = document.querySelector('[data-mobile-document-receipt]');
    receipt?.querySelector('[data-receipt-action="copy"]')?.addEventListener('click', async () => {
        try { await navigator.clipboard.writeText(window.location.href); }
        catch (_) { window.prompt(@json(__('common.copy')), window.location.href); }
    });
    receipt?.querySelector('[data-receipt-action="share"]')?.addEventListener('click', async () => {
        if (navigator.share) await navigator.share({title: document.title, url: window.location.href});
        else { try { await navigator.clipboard.writeText(window.location.href); } catch (_) {} }
    });
    receipt?.querySelectorAll('[data-receipt-action="print"], [data-receipt-action="pdf"]').forEach(button => button.addEventListener('click', () => window.print()));
})();
</script>
