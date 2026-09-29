@props(['disabled' => false])

<select @disabled($disabled) {{ $attributes->merge(['class' => 'border-slate-300 focus:border-amber-600 focus:ring-amber-600 rounded-md shadow-sm text-slate-900']) }}>
    {{ $slot }}
</select>
