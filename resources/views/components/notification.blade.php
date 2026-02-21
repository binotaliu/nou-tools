@props([
    'type' => 'info',
    'message',
])

{{-- allow callers to pass extra attributes (e.g. print:hidden) --}}

@php
    // colors for icon based on type
    $iconColor = match ($type) {
        'success' => 'text-green-400',
        'error' => 'text-red-400',
        'warning' => 'text-yellow-400',
        default => 'text-blue-400',
    };
@endphp

{{--
    A global notification region with a single panel instance. Alpine controls
    the show/hide animation, and the panel slides in/out as in the design spec.
--}}

<div
    aria-live="assertive"
    class="pointer-events-none fixed inset-0 z-50 flex items-end px-4 py-6 sm:items-start sm:p-6"
>
    <div class="flex w-full flex-col items-center space-y-4 sm:items-end">
        <!-- Notification panel, dynamically inserted when needed -->
        <div
            {{ $attributes->merge(['class' => 'pointer-events-auto w-full max-w-sm translate-y-0 transform rounded-lg bg-white opacity-100 border border-warm-200 shadow outline-1 -outline-offset-1 outline-white/10 transition duration-300 ease-out sm:translate-x-0 starting:translate-y-2 starting:opacity-0 starting:sm:translate-x-2 starting:sm:translate-y-0 z-50']) }}
            x-data="{ show: true }"
            x-init="setTimeout(() => (show = false), 4000)"
            x-show="show"
            x-cloak
            x-transition:enter="transform transition duration-300 ease-out"
            x-transition:enter-start="translate-y-2 opacity-0 sm:translate-x-2 sm:translate-y-0"
            x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
            x-transition:leave="transition duration-200 ease-in"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        >
            <div class="p-4">
                <div class="flex items-start">
                    <div class="shrink-0">
                        @if ($type === 'success')
                            <x-heroicon-o-check-circle
                                class="size-6 {{ $iconColor }}"
                            />
                        @elseif ($type === 'error')
                            <x-heroicon-o-x-circle
                                class="size-6 {{ $iconColor }}"
                            />
                        @elseif ($type === 'warning')
                            <x-heroicon-o-exclamation-circle
                                class="size-6 {{ $iconColor }}"
                            />
                        @else
                            <x-heroicon-o-information-circle
                                class="size-6 {{ $iconColor }}"
                            />
                        @endif
                    </div>
                    <div class="ml-3 w-0 flex-1 pt-0.5">
                        <p class="text-sm font-medium text-gray-900">
                            {{ $message }}
                        </p>
                        <!-- optional description could be passed via slot if needed -->
                        @if (isset($slot) && trim($slot))
                            <p class="mt-1 text-sm text-gray-500">
                                {{ $slot }}
                            </p>
                        @endif
                    </div>
                    <div class="ml-4 flex shrink-0">
                        <button
                            type="button"
                            class="inline-flex rounded-md text-gray-400 hover:text-black focus:outline-2 focus:outline-offset-2 focus:outline-indigo-500"
                            x-on:click="show = false"
                        >
                            <span class="sr-only">Close</span>
                            <x-heroicon-o-x-mark class="size-5" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
