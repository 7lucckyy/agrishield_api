@props([
    'src',
    'alt',
    'credit',
    'source',
    'location',
    'position' => 'center',
])

<figure {{ $attributes->class(['gs-field-photo', "gs-field-photo--{$position}"]) }}>
    <img src="{{ asset($src) }}" alt="{{ $alt }}" loading="lazy" decoding="async">
    <figcaption>
        <span>{{ $location }}</span>
        <span>
            Photo:
            {{ $credit }}
            ·
            <a href="{{ $source }}" target="_blank" rel="noreferrer">Wikimedia Commons</a>
            ·
            <a href="https://creativecommons.org/licenses/by-sa/4.0/" target="_blank" rel="noreferrer">CC BY-SA 4.0</a>
            · cropped for display
        </span>
    </figcaption>
</figure>
