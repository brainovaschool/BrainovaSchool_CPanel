{{--
    Compact start/end time editor - two HH:MM dropdown pairs feeding one
    hidden field each. Native <input type="time"> shows AM/PM on some
    systems, so this guarantees plain 24-hour entry instead (same reason
    the old "extra entry" row already did it this way).

    Expects: $prefix (unique per row), $startValue/$endValue ("H:i" strings,
    may be empty for a blank row), $startName/$endName (defaults below).
--}}
@php
    $startName = $startName ?? 'start_time';
    $endName = $endName ?? 'end_time';
    [$startH, $startM] = $startValue ? explode(':', $startValue) : ['', ''];
    [$endH, $endM] = $endValue ? explode(':', $endValue) : ['', ''];
@endphp
<input type="hidden" name="{{ $startName }}" id="{{ $prefix }}Start" value="{{ $startValue }}">
<div class="d-flex align-items-center gap-1">
    <select class="form-select ot-input" style="min-width:68px" onchange="window.combineWorkLogTime('{{ $prefix }}StartHour','{{ $prefix }}StartMin','{{ $prefix }}Start')" id="{{ $prefix }}StartHour">
        <option value="">HH</option>
        @for ($h = 0; $h < 24; $h++)
            <option value="{{ sprintf('%02d', $h) }}" {{ $startH === sprintf('%02d', $h) ? 'selected' : '' }}>{{ sprintf('%02d', $h) }}</option>
        @endfor
    </select>
    <span>:</span>
    <select class="form-select ot-input" style="min-width:68px" onchange="window.combineWorkLogTime('{{ $prefix }}StartHour','{{ $prefix }}StartMin','{{ $prefix }}Start')" id="{{ $prefix }}StartMin">
        <option value="">MM</option>
        @foreach ([0, 15, 30, 45] as $m)
            <option value="{{ sprintf('%02d', $m) }}" {{ $startM === sprintf('%02d', $m) ? 'selected' : '' }}>{{ sprintf('%02d', $m) }}</option>
        @endforeach
    </select>
</div>
<span>–</span>
<input type="hidden" name="{{ $endName }}" id="{{ $prefix }}End" value="{{ $endValue }}">
<div class="d-flex align-items-center gap-1">
    <select class="form-select ot-input" style="min-width:68px" onchange="window.combineWorkLogTime('{{ $prefix }}EndHour','{{ $prefix }}EndMin','{{ $prefix }}End')" id="{{ $prefix }}EndHour">
        <option value="">HH</option>
        @for ($h = 0; $h < 24; $h++)
            <option value="{{ sprintf('%02d', $h) }}" {{ $endH === sprintf('%02d', $h) ? 'selected' : '' }}>{{ sprintf('%02d', $h) }}</option>
        @endfor
    </select>
    <span>:</span>
    <select class="form-select ot-input" style="min-width:68px" onchange="window.combineWorkLogTime('{{ $prefix }}EndHour','{{ $prefix }}EndMin','{{ $prefix }}End')" id="{{ $prefix }}EndMin">
        <option value="">MM</option>
        @foreach ([0, 15, 30, 45] as $m)
            <option value="{{ sprintf('%02d', $m) }}" {{ $endM === sprintf('%02d', $m) ? 'selected' : '' }}>{{ sprintf('%02d', $m) }}</option>
        @endforeach
    </select>
</div>
