{{--
    One labelled input of a mobile form (UI-2): text-like inputs, a select (pass "options"
    value => label) or a textarea. Shows the old input and the validation error of "name".
    Any other attribute (inputmode, step, autocomplete, …) goes to the control.
--}}
@props(['name', 'label', 'type' => 'text', 'value' => null, 'options' => null, 'required' => false, 'hint' => null, 'id' => null])
@php
    $id ??= 'rnField-'.trim(preg_replace('/[^A-Za-z0-9_-]+/', '-', $name), '-');
    $value = old($name, $value);
    $error = $errors->first($name);
    $describedBy = trim(($hint ? $id.'-hint ' : '').($error ? $id.'-error' : ''));
@endphp
<div class="rn-m-field{{ $error ? ' has-error' : '' }}">
    <label for="{{ $id }}">{{ $label }}@if($required)<span class="rn-m-required" aria-hidden="true"> *</span>@endif</label>
    @if($options !== null)
        <select id="{{ $id }}" name="{{ $name }}" class="rn-m-input" @required($required) @if($describedBy) aria-describedby="{{ $describedBy }}" @endif @if($error) aria-invalid="true" @endif {{ $attributes }}>
            @foreach($options as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" @selected((string) $optionValue === (string) $value)>{{ $optionLabel }}</option>
            @endforeach
        </select>
    @elseif($type === 'textarea')
        <textarea id="{{ $id }}" name="{{ $name }}" class="rn-m-input rn-m-textarea" @required($required) @if($describedBy) aria-describedby="{{ $describedBy }}" @endif @if($error) aria-invalid="true" @endif {{ $attributes }}>{{ $value }}</textarea>
    @else
        <input id="{{ $id }}" type="{{ $type }}" name="{{ $name }}" value="{{ $value }}" class="rn-m-input" @required($required) @if($describedBy) aria-describedby="{{ $describedBy }}" @endif @if($error) aria-invalid="true" @endif {{ $attributes }}>
    @endif
    @if($hint)<p id="{{ $id }}-hint" class="rn-m-field-hint">{{ $hint }}</p>@endif
    @if($error)<p id="{{ $id }}-error" class="rn-m-field-error" role="alert">{{ $error }}</p>@endif
</div>
