@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-stone-300 focus:border-green-500 focus:ring-green-500 rounded-lg shadow-sm bg-white text-stone-900 placeholder-stone-400 disabled:bg-stone-50 disabled:text-stone-500']) }}>
