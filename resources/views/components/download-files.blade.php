@props([
    'product',
    'method' => 'download',
    'passProductId' => false,
    'buttonClass' => '',
    'color' => 'info',
    'size' => null,
])

@php
    $files = $product->relationLoaded('files') ? $product->files : $product->files()->get();
    $fileCount = $files->count();
@endphp

@if ($fileCount === 0)
    <span class="text-gray-400 text-sm">فایلی موجود نیست</span>
@elseif ($fileCount === 1)
    @php
        $file = $files->first();
        $click = $passProductId
            ? "{$method}({$product->id}, {$file->id})"
            : "{$method}({$file->id})";
        $buttonAttrs = ['wire:click' => $click, 'color' => $color, 'class' => $buttonClass];
        if ($size) {
            $buttonAttrs['size'] = $size;
        }
    @endphp
    <x-button {{ $attributes->merge($buttonAttrs) }}>
        {{ $slot->isEmpty() ? 'دانلود' : $slot }}
    </x-button>
@else
    <div class="relative w-full" x-data="{ open: false }" @click.outside="open = false">
        <button type="button" @click="open = !open"
            {{ $attributes->merge(['class' => 'bg-[#06CC85] w-full text-white font-bold text-center p-4 rounded-[10px] active:scale-105 flex justify-center items-center gap-3 ' . $buttonClass]) }}>
            {{ $slot->isEmpty() ? 'دانلود فایل‌ها' : $slot }}
            <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>
        <div x-show="open" x-transition
            class="absolute z-20 mt-2 w-full rounded-[10px] bg-white dark:bg-[#1A1A18] shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden"
            style="display: none;">
            @foreach ($files as $file)
                @php
                    $click = $passProductId
                        ? "{$method}({$product->id}, {$file->id})"
                        : "{$method}({$file->id})";
                @endphp
                <button type="button" wire:click="{{ $click }}" @click="open = false"
                    class="w-full text-right px-4 py-3 text-sm hover:bg-gray-100 dark:hover:bg-gray-700 border-b border-gray-100 dark:border-gray-700 last:border-0 break-all">
                    {{ $file->name }}
                    @if ($file->size)
                        <span class="text-xs text-gray-400 block">{{ $file->size }}</span>
                    @endif
                </button>
            @endforeach
        </div>
    </div>
@endif
