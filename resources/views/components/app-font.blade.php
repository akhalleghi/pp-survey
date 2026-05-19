@php
    $font = $appFont ?? \App\Support\AppFonts::resolve();
    $textScale = $appTextScale ?? \App\Support\AppTextScale::resolve();
@endphp
<link rel="stylesheet" href="{{ asset($font['css']) }}">
<link rel="stylesheet" href="{{ asset('css/app-appearance.css') }}">
<style>
    :root {
        --app-font-family: {!! \App\Support\AppFonts::stack($font['id'] ?? null) !!};
        --app-root-font-size: {{ $textScale['root'] }};
        --app-scale-factor: {{ $textScale['factor'] }};
    }
    html {
        font-family: var(--app-font-family);
        font-size: var(--app-root-font-size);
    }
    body,
    button,
    input,
    select,
    textarea {
        font-family: inherit;
    }
</style>
