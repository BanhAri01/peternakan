@props(['icon' => 'bi-inbox', 'title' => 'Belum ada data'])

<div {{ $attributes->class('empty') }}>
    <i class="bi {{ $icon }}"></i>
    <b>{{ $title }}</b>
    @if(trim($slot) !== '')<div>{{ $slot }}</div>@endif
    @isset($action)<div class="mt-3">{{ $action }}</div>@endisset
</div>
