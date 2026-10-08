@props(['title' => null, 'subtitle' => null, 'icon' => null, 'tone' => null, 'flush' => false, 'step' => null])

<section {{ $attributes->class(['panel', 'tone-' . $tone => $tone]) }}>
    @if($title || isset($actions))
        <div class="panel-head">
            <div>
                @if($title)
                    <h2 class="panel-title">
                        @if($step)<span class="step-no">{{ $step }}</span>@elseif($icon)<i class="bi {{ $icon }}"></i>@endif
                        {{ $title }}
                    </h2>
                @endif
                @if($subtitle)<p class="panel-sub">{{ $subtitle }}</p>@endif
            </div>
            @isset($actions)
                <div class="d-flex flex-wrap gap-2">{{ $actions }}</div>
            @endisset
        </div>
    @endif

    <div class="panel-body {{ $flush ? 'flush' : '' }}">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="panel-foot">{{ $footer }}</div>
    @endisset
</section>
