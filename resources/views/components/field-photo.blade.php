@props([
    'src',
    'alt',
    'position' => 'center',
])

<figure {{ $attributes->class(['gs-field-photo', "gs-field-photo--{$position}"]) }}>
    <img src="{{ asset($src) }}" alt="{{ $alt }}" loading="lazy" decoding="async">
</figure>
