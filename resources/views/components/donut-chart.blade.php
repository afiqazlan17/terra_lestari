@props(['slices' => []])

@php
    $palette = ['#2a78d6', '#eb6834', '#1baf7a', '#eda100'];
    $total = array_sum(array_column($slices, 'value'));
    $radius = 60;
    $strokeWidth = 26;
    $circumference = 2 * M_PI * $radius;
    $cumulative = 0;
@endphp

<div class="flex flex-col sm:flex-row items-center gap-6">
    <div class="relative shrink-0" style="width: 160px; height: 160px;">
        <svg viewBox="0 0 160 160" class="w-full h-full -rotate-90">
            <circle cx="80" cy="80" r="{{ $radius }}" fill="none" stroke="#f3f4f6" stroke-width="{{ $strokeWidth }}" />
            @if ($total > 0)
                @foreach ($slices as $i => $slice)
                    @php
                        $pct = $slice['value'] / $total;
                        $dash = max($pct * $circumference - 2, 0);
                        $offset = -$cumulative;
                        $cumulative += $pct * $circumference;
                    @endphp
                    @if ($slice['value'] > 0)
                        <circle cx="80" cy="80" r="{{ $radius }}" fill="none"
                            stroke="{{ $palette[$i % count($palette)] }}" stroke-width="{{ $strokeWidth }}"
                            stroke-linecap="round"
                            stroke-dasharray="{{ $dash }} {{ $circumference - $dash }}"
                            stroke-dashoffset="{{ $offset }}" />
                    @endif
                @endforeach
            @endif
        </svg>
        <div class="absolute inset-0 flex flex-col items-center justify-center">
            <span class="text-xs text-gray-400">Jumlah</span>
            <span class="text-sm font-semibold text-gray-800">RM {{ number_format($total, 0) }}</span>
        </div>
    </div>

    <div class="w-full space-y-2 text-sm">
        @foreach ($slices as $i => $slice)
            @php $pct = $total > 0 ? ($slice['value'] / $total) * 100 : 0; @endphp
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $palette[$i % count($palette)] }}"></span>
                <span class="text-gray-600 flex-1">{{ $slice['label'] }}</span>
                <span class="font-medium text-gray-800">RM {{ number_format($slice['value'], 2) }}</span>
                <span class="text-gray-400 text-xs w-12 text-right">{{ number_format($pct, 1) }}%</span>
            </div>
        @endforeach
    </div>
</div>
