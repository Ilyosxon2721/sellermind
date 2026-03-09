{{--
    PWA Report Card Component
    Analytics card with mini chart and trend indicator

    @props
    - title: string - Report title
    - value: string - Main metric value
    - subtitle: string - Subtitle/description
    - trend: float|null - Percentage change
    - chartData: array - Data for mini chart [{value: number, label?: string}]
    - href: string|null - Link to detailed report
    - loading: bool - Loading state
    - icon: string - Icon name (optional)
    - color: string - Accent color (blue, green, purple, orange, red)
--}}

@props([
    'title' => 'Отчёт',
    'value' => '0',
    'subtitle' => null,
    'trend' => null,
    'chartData' => [],
    'href' => null,
    'loading' => false,
    'icon' => null,
    'color' => 'blue',
])

@php
$colorClasses = [
    'blue' => [
        'bg' => 'bg-blue-50',
        'text' => 'text-blue-600',
        'chart' => '#3b82f6',
    ],
    'green' => [
        'bg' => 'bg-green-50',
        'text' => 'text-green-600',
        'chart' => '#22c55e',
    ],
    'purple' => [
        'bg' => 'bg-purple-50',
        'text' => 'text-purple-600',
        'chart' => '#a855f7',
    ],
    'orange' => [
        'bg' => 'bg-orange-50',
        'text' => 'text-orange-600',
        'chart' => '#f97316',
    ],
    'red' => [
        'bg' => 'bg-red-50',
        'text' => 'text-red-600',
        'chart' => '#ef4444',
    ],
];

$colors = $colorClasses[$color] ?? $colorClasses['blue'];
$chartId = 'chart-' . uniqid();
@endphp

@if($loading)
{{-- Loading Skeleton --}}
<div {{ $attributes->merge(['class' => 'pwa-only sm-report-card sm-report-card-skeleton']) }}>
    <div class="flex items-center justify-between mb-3">
        <div class="h-4 w-24 bg-gray-200 rounded skeleton"></div>
        <div class="h-4 w-4 bg-gray-200 rounded skeleton"></div>
    </div>
    <div class="flex items-center justify-between mb-2">
        <div class="h-8 w-32 bg-gray-200 rounded skeleton"></div>
        <div class="h-5 w-14 bg-gray-200 rounded skeleton"></div>
    </div>
    <div class="h-12 w-full bg-gray-200 rounded skeleton mb-2"></div>
    <div class="h-3 w-20 bg-gray-200 rounded skeleton"></div>
</div>
@else
{{-- Report Card --}}
<{{ $href ? 'a' : 'div' }}
    @if($href) href="{{ $href }}" @endif
    x-data="{
        chartData: @js($chartData),
        chartColor: '{{ $colors['chart'] }}',
        trend: {{ $trend ?? 'null' }},

        init() {
            if (this.chartData.length > 0) {
                this.$nextTick(() => this.drawChart());
            }
        },

        drawChart() {
            const canvas = this.$refs.canvas;
            if (!canvas) return;

            const ctx = canvas.getContext('2d');
            const rect = canvas.parentElement.getBoundingClientRect();

            // Set canvas size for retina
            canvas.width = rect.width * 2;
            canvas.height = rect.height * 2;
            ctx.scale(2, 2);

            const width = rect.width;
            const height = rect.height;
            const padding = { top: 4, right: 4, bottom: 4, left: 4 };

            const values = this.chartData.map(d => typeof d === 'object' ? d.value : d);
            const max = Math.max(...values) || 1;
            const min = Math.min(...values) || 0;
            const range = max - min || 1;

            // Calculate points
            const points = values.map((v, i) => ({
                x: padding.left + (i / (values.length - 1 || 1)) * (width - padding.left - padding.right),
                y: height - padding.bottom - ((v - min) / range) * (height - padding.top - padding.bottom)
            }));

            // Gradient fill
            const gradient = ctx.createLinearGradient(0, 0, 0, height);
            gradient.addColorStop(0, this.hexToRgba(this.chartColor, 0.3));
            gradient.addColorStop(1, this.hexToRgba(this.chartColor, 0));

            // Draw area
            ctx.beginPath();
            ctx.moveTo(points[0].x, height - padding.bottom);
            points.forEach(p => ctx.lineTo(p.x, p.y));
            ctx.lineTo(points[points.length - 1].x, height - padding.bottom);
            ctx.closePath();
            ctx.fillStyle = gradient;
            ctx.fill();

            // Draw line with smooth curves
            ctx.beginPath();
            ctx.moveTo(points[0].x, points[0].y);
            for (let i = 1; i < points.length; i++) {
                const xc = (points[i].x + points[i - 1].x) / 2;
                const yc = (points[i].y + points[i - 1].y) / 2;
                ctx.quadraticCurveTo(points[i - 1].x, points[i - 1].y, xc, yc);
            }
            ctx.lineTo(points[points.length - 1].x, points[points.length - 1].y);
            ctx.strokeStyle = this.chartColor;
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.stroke();
        },

        hexToRgba(hex, alpha) {
            const r = parseInt(hex.slice(1, 3), 16);
            const g = parseInt(hex.slice(3, 5), 16);
            const b = parseInt(hex.slice(5, 7), 16);
            return `rgba(${r}, ${g}, ${b}, ${alpha})`;
        }
    }"
    {{ $attributes->merge(['class' => 'pwa-only sm-report-card' . ($href ? ' sm-report-card-link' : '')]) }}
>
    {{-- Header Row --}}
    <div class="flex items-center justify-between mb-1">
        <span class="native-caption font-medium">{{ $title }}</span>
        @if($href)
        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
        @endif
    </div>

    {{-- Value Row --}}
    <div class="flex items-end justify-between mb-2">
        <span class="text-2xl font-bold text-gray-900">{{ $value }}</span>
        @if($trend !== null)
        <span
            class="flex items-center gap-0.5 text-sm font-semibold px-2 py-0.5 rounded-full"
            :class="trend >= 0 ? 'text-green-600 bg-green-50' : 'text-red-600 bg-red-50'"
        >
            <template x-if="trend >= 0">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                </svg>
            </template>
            <template x-if="trend < 0">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                </svg>
            </template>
            <span x-text="(trend >= 0 ? '+' : '') + trend.toFixed(1) + '%'">{{ $trend >= 0 ? '+' : '' }}{{ number_format($trend, 1) }}%</span>
        </span>
        @endif
    </div>

    {{-- Mini Chart --}}
    @if(count($chartData) > 0)
    <div class="sm-report-chart">
        <canvas x-ref="canvas" style="width: 100%; height: 100%;"></canvas>
    </div>
    @endif

    {{-- Subtitle --}}
    @if($subtitle)
    <p class="native-caption mt-2">{{ $subtitle }}</p>
    @endif

    {{-- Slot for additional content --}}
    @if(isset($slot) && !$slot->isEmpty())
    <div class="mt-2 pt-2 border-t border-gray-100">
        {{ $slot }}
    </div>
    @endif
</{{ $href ? 'a' : 'div' }}>
@endif

<style>
/* Report Card Base */
.pwa-mode .sm-report-card {
    background: white;
    border-radius: 14px;
    padding: 16px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    display: block;
    text-decoration: none;
}

.pwa-mode .sm-report-card-link {
    cursor: pointer;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
    -webkit-tap-highlight-color: transparent;
}

.pwa-mode .sm-report-card-link:active {
    transform: scale(0.98);
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

/* Mini Chart */
.pwa-mode .sm-report-chart {
    height: 48px;
    width: 100%;
    margin: 4px 0;
}

/* Skeleton */
.pwa-mode .sm-report-card-skeleton .skeleton {
    background: linear-gradient(90deg, #e5e7eb 25%, #f3f4f6 50%, #e5e7eb 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
}

@keyframes shimmer {
    0% { background-position: -200% 0; }
    100% { background-position: 200% 0; }
}

/* Dark mode */
@media (prefers-color-scheme: dark) {
    .pwa-mode .sm-report-card {
        background: #1c1c1e;
    }

    .pwa-mode .sm-report-card .text-gray-900 {
        color: #fff;
    }

    .pwa-mode .sm-report-card-skeleton .skeleton {
        background: linear-gradient(90deg, #2c2c2e 25%, #3a3a3c 50%, #2c2c2e 75%);
        background-size: 200% 100%;
    }
}
</style>
