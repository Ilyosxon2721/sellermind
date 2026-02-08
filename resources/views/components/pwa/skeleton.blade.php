@props(['type' => 'card', 'count' => 3])

@if($type === 'card')
    @for($i = 0; $i < $count; $i++)
        <div class="sm-skeleton-card">
            <div class="sm-skeleton-avatar sm-shimmer"></div>
            <div class="sm-skeleton-content">
                <div class="sm-skeleton-line sm-shimmer" style="width: 70%"></div>
                <div class="sm-skeleton-line sm-shimmer" style="width: 50%"></div>
                <div class="sm-skeleton-line sm-shimmer" style="width: 30%"></div>
            </div>
        </div>
    @endfor
@elseif($type === 'metric')
    @for($i = 0; $i < $count; $i++)
        <div class="sm-skeleton-metric">
            <div class="sm-skeleton-icon sm-shimmer"></div>
            <div class="sm-skeleton-value sm-shimmer"></div>
            <div class="sm-skeleton-label sm-shimmer"></div>
        </div>
    @endfor
@elseif($type === 'list')
    @for($i = 0; $i < $count; $i++)
        <div class="sm-skeleton-list-item sm-shimmer"></div>
    @endfor
@endif
