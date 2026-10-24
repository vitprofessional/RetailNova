{{-- A titled group of form fields on a mobile form (UI-2). --}}
@props(['title', 'description' => null])
<section {{ $attributes->merge(['class' => 'rn-m-form-section']) }}>
    <h2 class="rn-m-form-section-title">{{ $title }}</h2>
    @if($description)<p class="rn-m-form-section-text">{{ $description }}</p>@endif
    <div class="rn-m-form-section-body">{{ $slot }}</div>
</section>
