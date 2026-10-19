@props([
    'title',
    'align' => 'left', // left | center
])

<h2 @class([
    'title-xl text-4xl sm:text-5xl',
    'text-left' => $align === 'left',
    'text-center' => $align === 'center',
])>{{ $title }}</h2>