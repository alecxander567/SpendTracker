@props([
    'color' => 'var(--magma-core)',
    'size' => '2rem',
    'fullscreen' => false,
])

@if ($fullscreen)
    <div {{ $attributes->merge(['class' => 'loading-spinner-overlay']) }}>
        <div class="spinner-border loading-spinner"
            style="color: {{ $color }}; width: {{ $size }}; height: {{ $size }};" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>
@else
    <div class="spinner-border loading-spinner"
        style="color: {{ $color }}; width: {{ $size }}; height: {{ $size }};" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
@endif
