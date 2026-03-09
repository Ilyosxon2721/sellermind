@props([
    'type' => 'bar',
    'data' => [],
    'height' => 120,
    'title' => null,
    'loading' => false,
])

@php
    $chartId = 'chart-' . uniqid();

    // Цвета маркетплейсов
    $marketplaceColors = [
        'wb' => '#a855f7',
        'wildberries' => '#a855f7',
        'ozon' => '#3b82f6',
        'uzum' => '#7c3aed',
        'yandex' => '#facc15',
    ];

    // Вычисляем максимальное значение для процентов
    $maxValue = collect($data)->max('value') ?: 1;
    $totalValue = collect($data)->sum('value') ?: 1;
@endphp

<div
    class="pwa-only sm-chart-mini"
    x-data="{
        data: @js($data),
        animated: false,
        type: '{{ $type }}',
        maxValue: {{ $maxValue }},
        totalValue: {{ $totalValue }},

        getPercent(value) {
            if (this.type === 'pie' || this.type === 'donut') {
                return Math.round((value / this.totalValue) * 100);
            }
            return Math.round((value / this.maxValue) * 100);
        },

        getBarWidth(value) {
            return this.animated ? this.getPercent(value) + '%' : '0%';
        }
    }"
    x-init="setTimeout(() => animated = true, 100)"
    {{ $attributes }}
>
    {{-- Заголовок --}}
    @if($title)
        <div class="sm-chart-mini-title">
            {{ $title }}
        </div>
    @endif

    {{-- Loading State --}}
    @if($loading)
        <div class="sm-chart-mini-skeleton" style="height: {{ $height }}px">
            @if($type === 'bar')
                @for($i = 0; $i < 3; $i++)
                    <div class="sm-chart-mini-skeleton-bar sm-shimmer" style="width: {{ 90 - ($i * 20) }}%"></div>
                @endfor
            @elseif($type === 'pie' || $type === 'donut')
                <div class="sm-chart-mini-skeleton-circle sm-shimmer"></div>
            @else
                <div class="sm-chart-mini-skeleton-line sm-shimmer"></div>
            @endif
        </div>
    @else
        {{-- Bar Chart --}}
        @if($type === 'bar')
            <div class="sm-chart-mini-bars" style="height: {{ $height }}px">
                <template x-for="(item, index) in data" :key="index">
                    <div class="sm-chart-mini-bar-row">
                        <div class="sm-chart-mini-bar-track">
                            <div
                                class="sm-chart-mini-bar-fill"
                                :style="`width: ${getBarWidth(item.value)}; background-color: ${item.color || '#3b82f6'}; transition-delay: ${index * 100}ms`"
                            ></div>
                        </div>
                        <div class="sm-chart-mini-bar-info">
                            <span class="sm-chart-mini-bar-label" x-text="item.label"></span>
                            <span class="sm-chart-mini-bar-value" x-text="getPercent(item.value) + '%'"></span>
                        </div>
                    </div>
                </template>
            </div>

        {{-- Line Chart (простая версия на CSS) --}}
        @elseif($type === 'line')
            <div class="sm-chart-mini-line" style="height: {{ $height }}px" x-ref="lineChart">
                <canvas
                    x-ref="canvas"
                    x-init="$nextTick(() => {
                        const canvas = $refs.canvas;
                        const ctx = canvas.getContext('2d');
                        const rect = canvas.parentElement.getBoundingClientRect();
                        canvas.width = rect.width * 2;
                        canvas.height = rect.height * 2;
                        ctx.scale(2, 2);

                        const width = rect.width;
                        const height = rect.height;
                        const padding = 10;
                        const values = data.map(d => d.value);
                        const max = Math.max(...values) || 1;
                        const min = Math.min(...values) || 0;
                        const range = max - min || 1;

                        const points = values.map((v, i) => ({
                            x: padding + (i / (values.length - 1 || 1)) * (width - padding * 2),
                            y: height - padding - ((v - min) / range) * (height - padding * 2)
                        }));

                        // Градиентная заливка
                        const gradient = ctx.createLinearGradient(0, 0, 0, height);
                        gradient.addColorStop(0, 'rgba(59, 130, 246, 0.3)');
                        gradient.addColorStop(1, 'rgba(59, 130, 246, 0)');

                        // Область под линией
                        ctx.beginPath();
                        ctx.moveTo(points[0].x, height - padding);
                        points.forEach(p => ctx.lineTo(p.x, p.y));
                        ctx.lineTo(points[points.length - 1].x, height - padding);
                        ctx.closePath();
                        ctx.fillStyle = gradient;
                        ctx.fill();

                        // Линия
                        ctx.beginPath();
                        ctx.moveTo(points[0].x, points[0].y);
                        for (let i = 1; i < points.length; i++) {
                            const xc = (points[i].x + points[i - 1].x) / 2;
                            const yc = (points[i].y + points[i - 1].y) / 2;
                            ctx.quadraticCurveTo(points[i - 1].x, points[i - 1].y, xc, yc);
                        }
                        ctx.lineTo(points[points.length - 1].x, points[points.length - 1].y);
                        ctx.strokeStyle = '#3b82f6';
                        ctx.lineWidth = 2;
                        ctx.stroke();

                        // Точки
                        points.forEach((p, i) => {
                            ctx.beginPath();
                            ctx.arc(p.x, p.y, 3, 0, Math.PI * 2);
                            ctx.fillStyle = '#3b82f6';
                            ctx.fill();
                            ctx.strokeStyle = '#fff';
                            ctx.lineWidth = 1.5;
                            ctx.stroke();
                        });
                    })"
                    style="width: 100%; height: 100%;"
                ></canvas>
            </div>

        {{-- Pie Chart --}}
        @elseif($type === 'pie')
            <div class="sm-chart-mini-pie" style="height: {{ $height }}px">
                <canvas
                    x-ref="pieCanvas"
                    x-init="$nextTick(() => {
                        const canvas = $refs.pieCanvas;
                        const ctx = canvas.getContext('2d');
                        const size = Math.min(canvas.parentElement.offsetWidth, canvas.parentElement.offsetHeight);
                        canvas.width = size * 2;
                        canvas.height = size * 2;
                        ctx.scale(2, 2);

                        const centerX = size / 2;
                        const centerY = size / 2;
                        const radius = size / 2 - 5;

                        let startAngle = -Math.PI / 2;

                        data.forEach((item, i) => {
                            const sliceAngle = (item.value / totalValue) * Math.PI * 2;

                            ctx.beginPath();
                            ctx.moveTo(centerX, centerY);
                            ctx.arc(centerX, centerY, radius, startAngle, startAngle + sliceAngle);
                            ctx.closePath();
                            ctx.fillStyle = item.color || '#3b82f6';
                            ctx.fill();

                            startAngle += sliceAngle;
                        });
                    })"
                    style="width: 100%; height: 100%; max-width: {{ $height }}px; margin: 0 auto; display: block;"
                ></canvas>
                <div class="sm-chart-mini-legend">
                    <template x-for="(item, index) in data" :key="index">
                        <div class="sm-chart-mini-legend-item">
                            <span class="sm-chart-mini-legend-dot" :style="`background-color: ${item.color || '#3b82f6'}`"></span>
                            <span class="sm-chart-mini-legend-label" x-text="item.label"></span>
                            <span class="sm-chart-mini-legend-value" x-text="getPercent(item.value) + '%'"></span>
                        </div>
                    </template>
                </div>
            </div>

        {{-- Donut Chart --}}
        @elseif($type === 'donut')
            <div class="sm-chart-mini-donut" style="height: {{ $height }}px">
                <div class="sm-chart-mini-donut-wrapper">
                    <canvas
                        x-ref="donutCanvas"
                        x-init="$nextTick(() => {
                            const canvas = $refs.donutCanvas;
                            const ctx = canvas.getContext('2d');
                            const container = canvas.parentElement;
                            const size = Math.min(container.offsetWidth, container.offsetHeight);
                            canvas.width = size * 2;
                            canvas.height = size * 2;
                            canvas.style.width = size + 'px';
                            canvas.style.height = size + 'px';
                            ctx.scale(2, 2);

                            const centerX = size / 2;
                            const centerY = size / 2;
                            const outerRadius = size / 2 - 5;
                            const innerRadius = outerRadius * 0.6;

                            let startAngle = -Math.PI / 2;

                            data.forEach((item, i) => {
                                const sliceAngle = (item.value / totalValue) * Math.PI * 2;

                                ctx.beginPath();
                                ctx.arc(centerX, centerY, outerRadius, startAngle, startAngle + sliceAngle);
                                ctx.arc(centerX, centerY, innerRadius, startAngle + sliceAngle, startAngle, true);
                                ctx.closePath();
                                ctx.fillStyle = item.color || '#3b82f6';
                                ctx.fill();

                                startAngle += sliceAngle;
                            });
                        })"
                    ></canvas>
                    <div class="sm-chart-mini-donut-center">
                        <span class="sm-chart-mini-donut-total" x-text="totalValue.toLocaleString()"></span>
                    </div>
                </div>
                <div class="sm-chart-mini-legend">
                    <template x-for="(item, index) in data" :key="index">
                        <div class="sm-chart-mini-legend-item">
                            <span class="sm-chart-mini-legend-dot" :style="`background-color: ${item.color || '#3b82f6'}`"></span>
                            <span class="sm-chart-mini-legend-label" x-text="item.label"></span>
                            <span class="sm-chart-mini-legend-value" x-text="getPercent(item.value) + '%'"></span>
                        </div>
                    </template>
                </div>
            </div>
        @endif
    @endif
</div>

<style>
/* Mini Chart Base */
.pwa-mode .sm-chart-mini {
    background: var(--sm-bg-secondary);
    border-radius: var(--sm-radius-lg);
    padding: var(--sm-space-lg);
}

.pwa-mode .sm-chart-mini-title {
    font-size: 14px;
    font-weight: 600;
    color: var(--sm-text-primary);
    margin-bottom: var(--sm-space-md);
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Bar Chart */
.pwa-mode .sm-chart-mini-bars {
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 12px;
}

.pwa-mode .sm-chart-mini-bar-row {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.pwa-mode .sm-chart-mini-bar-track {
    height: 8px;
    background: var(--sm-bg-tertiary);
    border-radius: 4px;
    overflow: hidden;
}

.pwa-mode .sm-chart-mini-bar-fill {
    height: 100%;
    border-radius: 4px;
    width: 0;
    transition: width 0.5s ease-out;
}

.pwa-mode .sm-chart-mini-bar-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.pwa-mode .sm-chart-mini-bar-label {
    font-size: 13px;
    color: var(--sm-text-secondary);
}

.pwa-mode .sm-chart-mini-bar-value {
    font-size: 13px;
    font-weight: 600;
    color: var(--sm-text-primary);
}

/* Line Chart */
.pwa-mode .sm-chart-mini-line {
    position: relative;
}

/* Pie/Donut Chart */
.pwa-mode .sm-chart-mini-pie,
.pwa-mode .sm-chart-mini-donut {
    display: flex;
    align-items: center;
    gap: var(--sm-space-lg);
}

.pwa-mode .sm-chart-mini-donut-wrapper {
    position: relative;
    flex-shrink: 0;
}

.pwa-mode .sm-chart-mini-donut-center {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
}

.pwa-mode .sm-chart-mini-donut-total {
    font-size: 16px;
    font-weight: 700;
    color: var(--sm-text-primary);
}

/* Legend */
.pwa-mode .sm-chart-mini-legend {
    display: flex;
    flex-direction: column;
    gap: 8px;
    flex: 1;
    min-width: 0;
}

.pwa-mode .sm-chart-mini-legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
}

.pwa-mode .sm-chart-mini-legend-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}

.pwa-mode .sm-chart-mini-legend-label {
    font-size: 12px;
    color: var(--sm-text-secondary);
    flex: 1;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.pwa-mode .sm-chart-mini-legend-value {
    font-size: 12px;
    font-weight: 600;
    color: var(--sm-text-primary);
}

/* Skeleton States */
.pwa-mode .sm-chart-mini-skeleton {
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 12px;
}

.pwa-mode .sm-chart-mini-skeleton-bar {
    height: 8px;
    border-radius: 4px;
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: sm-shimmer 1.5s infinite;
}

.pwa-mode .sm-chart-mini-skeleton-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    margin: 0 auto;
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: sm-shimmer 1.5s infinite;
}

.pwa-mode .sm-chart-mini-skeleton-line {
    height: 100%;
    border-radius: var(--sm-radius-md);
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: sm-shimmer 1.5s infinite;
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .pwa-mode .sm-chart-mini-skeleton-bar,
    .pwa-mode .sm-chart-mini-skeleton-circle,
    .pwa-mode .sm-chart-mini-skeleton-line {
        background: linear-gradient(90deg, #2c2c2e 25%, #3a3a3c 50%, #2c2c2e 75%);
        background-size: 200% 100%;
    }
}
</style>
