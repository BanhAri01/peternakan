@props(['tone' => 'neutral', 'icon' => null])

<span {{ $attributes->class(['tag', 'tag-' . $tone]) }}>@if($icon)<i class="bi {{ $icon }}"></i>@endif{{ $slot }}</span>
