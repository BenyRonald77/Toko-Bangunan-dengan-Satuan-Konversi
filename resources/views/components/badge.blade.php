@props(['variant' => 'neutral'])

@php
$variants = [
    'neutral' => 'bg-slate-100 text-slate-700',
    'success' => 'bg-emerald-100 text-emerald-800',
    'warning' => 'bg-amber-100 text-amber-800',
    'danger' => 'bg-red-100 text-red-800',
];
$classes = 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium '.($variants[$variant] ?? $variants['neutral']);
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</span>
