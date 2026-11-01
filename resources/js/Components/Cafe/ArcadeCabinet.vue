<script setup>
// The arcade cabinet that stands on the café floor, drawn flat in the room's
// own warm palette (fixed colours in both themes; no gradients or glows). The
// screen's star blinks slowly, and not at all under reduced motion.
</script>

<template>
  <svg
    viewBox="0 0 64 104"
    class="h-16 w-10"
    fill="none"
    aria-hidden="true"
    focusable="false"
  >
    <!-- body and side panels -->
    <path d="M10 98V12a6 6 0 0 1 6-6h32a6 6 0 0 1 6 6v86Z" fill="#4d3b63" />
    <path d="M6 98V30l4-4v72Z" fill="#3a2b4c" />
    <path d="M58 98V30l-4-4v72Z" fill="#3a2b4c" />
    <!-- marquee -->
    <rect x="14" y="10" width="36" height="10" rx="2" fill="#c9974f" />
    <rect x="17" y="13" width="30" height="4" rx="1" fill="#f3e6d3" />
    <!-- screen -->
    <rect x="14" y="24" width="36" height="28" rx="3" fill="#2a2433" />
    <rect x="17" y="27" width="30" height="22" rx="2" fill="#1f2a2e" />
    <path
      class="animate-pulse motion-reduce:animate-none"
      d="M32 31l2 6 6 2-6 2-2 6-2-6-6-2 6-2Z"
      fill="#f2d98b"
    />
    <!-- control panel -->
    <path d="M12 56h40l4 14H8Z" fill="#6a4c3a" />
    <circle cx="22" cy="63" r="2.5" fill="#2a2433" />
    <rect x="21" y="57" width="2" height="6" rx="1" fill="#c9974f" />
    <circle cx="22" cy="57" r="3" fill="#b8325a" />
    <circle cx="36" cy="63" r="2.5" fill="#f2d98b" />
    <circle cx="44" cy="62" r="2.5" fill="#7cc9a4" />
    <!-- coin slot -->
    <rect x="25" y="76" width="14" height="12" rx="2" fill="#2a2433" />
    <rect x="30" y="79" width="4" height="1.5" rx="0.75" fill="#c9974f" />
    <rect x="29" y="83" width="6" height="3" rx="1" fill="#b8325a" />
    <!-- base -->
    <rect x="6" y="96" width="52" height="6" rx="1" fill="#2a2433" />
  </svg>
</template>
