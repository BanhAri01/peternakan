@props(['title', 'subtitle' => null, 'icon' => null, 'back' => null, 'backLabel' => 'Kembali'])

<div class="page-head">
    <div>
        @if($back)
            <a href="{{ $back }}" class="crumb"><i class="bi bi-arrow-left"></i> {{ $backLabel }}</a>
        @endif
        <h1 class="page-title">
            @if($icon)<span class="icon"><i class="bi {{ $icon }}"></i></span>@endif
            <span>{{ $title }}</span>
        </h1>
        @if($subtitle)
            <p class="page-sub">{{ $subtitle }}</p>
        @endif
    </div>
    @if(trim($slot) !== '')
        <div class="page-actions">{{ $slot }}</div>
    @endif
</div>
