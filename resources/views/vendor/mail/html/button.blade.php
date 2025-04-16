@props([
    'url',
    'color' => 'primary',
    'align' => 'center',
])


<p style="text-align: left; margin-bottom: 20px;">
    <a href="{{ $url }}" style="display: inline-block; padding: 10px 20px; background-color: #00ff99; color: #000000; text-decoration: none; border-radius: 5px; font-weight: bold;">{{ $slot }}</a>
</p>