@props([
    'href' => '#',
    'value' => null,
    'blank' => false,
    'withoutIcon' => false,
    'icon' => 'link',
])
<a href="{{ $href }}"
   {{ $attributes->merge([
        'class' => 'inline-flex items-center gap-1',
   ]) }}
    @if($blank) target="_blank" @endif
>
    @if(!$withoutIcon && $icon)
        <x-moonshine::icon
            :icon="$icon"
            class="flex"
        />
    @endif

    {!! $value ?? $slot !!}
</a>
