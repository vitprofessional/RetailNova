@php
    $imageFieldId = $imageFieldId ?? 'businessImage';
    $imageRecord = $imageRecord ?? null;
    $imageType = $imageType ?? 'products';
    $imagePreview = $imageRecord?->image_url;
@endphp
<div class="rn-business-image-field" data-business-image-field>
    <label class="rn-business-image-field__label" for="{{ $imageFieldId }}">{{ __('mobile.image') }}</label>
    <div class="rn-business-image-field__row">
        <label class="rn-business-image-field__pick" for="{{ $imageFieldId }}" aria-label="{{ __('mobile.add_file') }}">
            @if($imagePreview)<img src="{{ $imagePreview }}" alt="{{ __('mobile.image_preview') }}" data-image-preview>@else<i class="ri-camera-line" aria-hidden="true" data-image-placeholder></i>@endif
            <span class="sr-only">{{ __('mobile.add_file') }}</span>
        </label>
        <div class="rn-business-image-field__details">
            <input type="file" id="{{ $imageFieldId }}" name="image" accept="image/*" class="rn-business-image-field__input" data-image-input>
            <label class="rn-m-btn rn-m-btn--outline" for="{{ $imageFieldId }}">{{ __('mobile.add_file') }}</label>
            <span class="rn-business-image-field__name" data-image-name>{{ $imageRecord?->image ? basename($imageRecord->image) : __('mobile.no_image_selected') }}</span>
            @if($imageRecord?->image)
                <label class="rn-business-image-field__remove"><input type="checkbox" name="remove_image" value="1"> {{ __('mobile.remove_image') }}</label>
            @endif
            <small>{{ __('mobile.image_file_rules') }}</small>
            @error('image')<small class="text-danger" role="alert">{{ $message }}</small>@enderror
        </div>
    </div>
</div>
@once
<script>
document.addEventListener('change', function (event) {
    const input = event.target.closest('[data-image-input]');
    if (!input) return;
    const field = input.closest('[data-business-image-field]');
    const file = input.files && input.files[0];
    const name = field.querySelector('[data-image-name]');
    if (name) name.textContent = file ? file.name : @json(__('mobile.no_image_selected'));
    if (!file) return;
    const preview = field.querySelector('[data-image-preview]');
    const placeholder = field.querySelector('[data-image-placeholder]');
    if (placeholder) placeholder.remove();
    let image = preview;
    if (!image) {
        image = document.createElement('img');
        image.alt = @json(__('mobile.image_preview'));
        image.dataset.imagePreview = '';
        field.querySelector('.rn-business-image-field__pick').appendChild(image);
    }
    image.src = URL.createObjectURL(file);
});
</script>
@endonce
