{{-- Small "at a glance" stat tiles. Expects $counts (from ClassContentModuleRepository::statusCounts()) and $tiles — an array of [status_key_or_null_for_sum, label, icon] to show, in order. --}}
<div class="cc-summary">
    @foreach ($tiles as $tile)
        @php
            [$keys, $label, $icon] = $tile;
            $value = collect((array) $keys)->sum(fn ($k) => $counts[$k] ?? 0);
        @endphp
        <div class="cc-summary__tile">
            <div class="cc-summary__icon"><i class="fa-solid {{ $icon }}"></i></div>
            <div>
                <div class="cc-summary__value">{{ $value }}</div>
                <div class="cc-summary__label">{{ $label }}</div>
            </div>
        </div>
    @endforeach
</div>

@once
    @push('css')
        <style>
            .cc-summary { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 18px; }
            .cc-summary__tile {
                flex: 1 1 140px; display: flex; align-items: center; gap: 12px; background: var(--ot-bg-primary, #fff);
                border: 1px solid var(--ot-border-color, #e2e8f0); border-radius: 12px; padding: 14px 16px;
            }
            .cc-summary__icon {
                width: 38px; height: 38px; border-radius: 10px; background: #eff6ff; color: #2563eb;
                display: flex; align-items: center; justify-content: center; font-size: 15px; flex-shrink: 0;
            }
            .cc-summary__value { font-size: 20px; font-weight: 800; line-height: 1; }
            .cc-summary__label { font-size: 11.5px; color: #64748b; margin-top: 3px; }
        </style>
    @endpush
@endonce
