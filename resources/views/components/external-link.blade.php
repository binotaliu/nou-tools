@props([
  'href',
])

<a
  href="{{ $href }}"
  target="_blank"
  rel="noopener noreferrer"
  {{ $attributes->merge(['class' => 'inline-flex items-center px-3 py-2 text-sm bg-white border border-warm-200 text-warm-700 rounded hover:bg-warm-50 gap-2']) }}
>
  @isset($icon)
    {{ $icon }}
  @endisset

  {{ $slot }}
</a>
