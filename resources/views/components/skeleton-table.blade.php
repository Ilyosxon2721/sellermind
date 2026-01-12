{{-- Skeleton Table Loading Component --}}
@props(['rows' => 5, 'columns' => 4])

<div {{ $attributes->merge(['class' => 'bg-white rounded-2xl border border-gray-100 overflow-hidden']) }}>
    <table class="w-full">
        {{-- Table header --}}
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                @for ($i = 0; $i < $columns; $i++)
                    <th class="px-6 py-3 text-left">
                        <div class="h-4 bg-gray-200 rounded shimmer" style="width: {{ rand(60, 100) }}%;"></div>
                    </th>
                @endfor
            </tr>
        </thead>

        {{-- Table body --}}
        <tbody class="divide-y divide-gray-100">
            @for ($row = 0; $row < $rows; $row++)
                <tr>
                    @for ($col = 0; $col < $columns; $col++)
                        <td class="px-6 py-4">
                            <div class="h-4 bg-gray-200 rounded shimmer" style="width: {{ rand(40, 90) }}%;"></div>
                        </td>
                    @endfor
                </tr>
            @endfor
        </tbody>
    </table>
</div>
