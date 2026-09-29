@props(['disabled' => false])

<select @disabled($disabled) {{ $attributes->merge(['class' => 'border-slate-300 focus:border-amber-500 focus:ring-amber-500 rounded-md shadow-sm text-slate-900']) }}>
    {{ $slot }}
</select>
