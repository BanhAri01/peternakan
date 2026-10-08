@props(['label', 'value', 'unit' => null, 'hint' => null, 'icon' => 'bi-bar-chart-fill', 'tone' => 'brand', 'alert' => false, 'href' => null])

@php $tag = $href ? 'a' : 'div'; @endphp
<{{ $tag }} @if($href) href="{{ $href }}" @endif {{ $attributes->class(['stat', 'tone-' . $tone, 'is-alert' => $alert, 'text-decoration-none text-reset' => $href]) }}>
    <div class="stat-label">
        <span class="stat-icon"><i class="bi {{ $icon }}"></i></span>
        <span>{{ $label }}</span>
    </div>
    <div class="stat-value">{{ $value }}@if($unit)<span class="unit">{{ $unit }}</span>@endif</div>
    @if($hint || trim($slot) !== '')
        <div class="stat-hint">{{ $hint }}{{ $slot }}</div>
    @endif
</{{ $tag }}>
