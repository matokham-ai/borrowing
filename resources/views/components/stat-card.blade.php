@props([
    'icon',
    'label',
    'value',
    'delta'     => ['value' => '—', 'direction' => 'neutral'],
    'color'     => 'indigo',
    'sparkline' => '',
])
<div class="stat-card stat-card--{{ $color }}">
    <div class="stat-card-top">
        <div class="stat-icon stat-icon--{{ $color }}">
            <i class="bi {{ $icon }}"></i>
        </div>
        @if($sparkline)
        <div class="stat-sparkline">
            <canvas id="spark-{{ $sparkline }}" width="110" height="44"></canvas>
        </div>
        @endif
    </div>
    <div class="stat-label">{{ $label }}</div>
    <div class="stat-value">{{ $value }}</div>
    @if($delta['direction'] !== 'neutral')
    <div class="stat-delta {{ $delta['direction'] === 'down' ? 'negative' : 'positive' }}">
        <i class="bi {{ $delta['direction'] === 'down' ? 'bi-arrow-up-right' : 'bi-arrow-up-right' }} stat-delta-arrow"></i>
        {{ $delta['value'] }}
        <span>from last month</span>
    </div>
    @else
    <div class="stat-delta neutral">
        <span>No prior data</span>
    </div>
    @endif
</div>
