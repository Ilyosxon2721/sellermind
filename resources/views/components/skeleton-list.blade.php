{{-- Skeleton List Loading Component --}}
@props(['items' => 5, 'withAvatar' => false])

<div {{ $attributes->merge(['class' => 'space-y-3']) }}>
    @for ($i = 0; $i < $items; $i++)
        <div class="flex items-center space-x-4 p-4 bg-white rounded-xl border border-gray-100">
            @if($withAvatar)
                {{-- Avatar skeleton --}}
                <div class="flex-shrink-0 w-12 h-12 bg-gray-200 rounded-full shimmer"></div>
            @endif

            {{-- Content --}}
            <div class="flex-1 space-y-2">
                <div class="h-4 bg-gray-200 rounded shimmer" style="width: {{ rand(60, 90) }}%;"></div>
                <div class="h-3 bg-gray-200 rounded shimmer" style="width: {{ rand(40, 70) }}%;"></div>
            </div>

            {{-- Action skeleton --}}
            <div class="flex-shrink-0">
                <div class="h-8 w-8 bg-gray-200 rounded shimmer"></div>
            </div>
        </div>
    @endfor
</div>
