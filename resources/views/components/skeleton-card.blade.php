{{-- Skeleton Card Loading Component --}}
@props(['rows' => 3])

<div {{ $attributes->merge(['class' => 'bg-white rounded-2xl p-6 border border-gray-100 shadow-sm']) }}>
    {{-- Title skeleton --}}
    <div class="h-6 bg-gray-200 rounded shimmer mb-4" style="width: 60%;"></div>

    {{-- Content rows --}}
    @for ($i = 0; $i < $rows; $i++)
        <div class="space-y-3 mb-4">
            <div class="h-4 bg-gray-200 rounded shimmer" style="width: {{ rand(70, 100) }}%;"></div>
            <div class="h-4 bg-gray-200 rounded shimmer" style="width: {{ rand(50, 90) }}%;"></div>
        </div>
    @endfor

    {{-- Footer action skeleton --}}
    <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-100">
        <div class="h-8 bg-gray-200 rounded shimmer" style="width: 80px;"></div>
        <div class="h-8 bg-gray-200 rounded shimmer" style="width: 100px;"></div>
    </div>
</div>
