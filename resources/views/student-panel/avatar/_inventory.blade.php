{{--
    The "Your Items" panel — everything the student already owns in one
    category, as a scrollable list. Click a row to wear it; the row you're
    wearing is highlighted and carries an X to take it off again.

    Three equip behaviours, picked automatically from $kind:
      - 'avatar'    single-select, never removable (a body is always worn)
      - 'outfit'/'hat'  single-select, removable (the worn row's form omits
                    item_id, which clears that slot)
      - 'accessory' multi-select — every row posts its own item_id to the
                    toggle route, which flips it on or off, so several can
                    be worn at once

    Usage: @include('student-panel.avatar._inventory', [
        'items'    => $data['accessories'],        // Collection of AvatarItem
        'owned'    => $data['owned'],               // array of owned item ids
        'equipped' => $data['equippedAccessories'], // array (accessory) or single id/null (others)
        'kind'     => 'accessory',
    ])
--}}
@php
    $invMulti     = $kind === 'accessory';
    $invWearRoute = match ($kind) {
        'avatar'    => route('student-panel-avatar.select-avatar'),
        'outfit'    => route('student-panel-avatar.select-outfit'),
        'hat'       => route('student-panel-avatar.select-hat'),
        'accessory' => route('student-panel-avatar.toggle-accessory'),
    };
    $invEquippedIds = $invMulti ? (array) $equipped : [];
    $invOwned       = $items->filter(fn ($i) => in_array($i->id, $owned, true))->values();
@endphp
<div class="av-inventory">
    <div class="av-inventory__title"><i class="fa-solid fa-box-open"></i> {{ $invTitle ?? ___('common.your_items') }} ({{ $invOwned->count() }})</div>
    <div class="av-inventory__list">
        @forelse ($invOwned as $item)
            @php $isWorn = $invMulti ? in_array($item->id, $invEquippedIds, true) : $equipped === $item->id; @endphp

            @if ($isWorn && $kind === 'avatar')
                <div class="av-inv-row worn">
                    @if ($item->image)
                        <img src="{{ globalAsset($item->image) }}" alt="{{ $item->name }}">
                    @else
                        <div class="face-fallback"><i class="fa-solid fa-image"></i></div>
                    @endif
                    <span class="nm">{{ $item->name }}</span>
                    <i class="fa-solid fa-check worn-check"></i>
                </div>
            @elseif ($isWorn)
                <form method="post" action="{{ $invWearRoute }}" class="av-inv-row worn">
                    @csrf
                    @if ($invMulti)
                        <input type="hidden" name="item_id" value="{{ $item->id }}">
                    @endif
                    @if ($item->image)
                        <img src="{{ globalAsset($item->image) }}" alt="{{ $item->name }}">
                    @else
                        <div class="face-fallback"><i class="fa-solid fa-image"></i></div>
                    @endif
                    <span class="nm">{{ $item->name }}</span>
                    <button type="submit" class="remove-x" title="{{ ___('common.remove') }}"><i class="fa-solid fa-xmark"></i></button>
                </form>
            @else
                <form method="post" action="{{ $invWearRoute }}">
                    @csrf
                    <input type="hidden" name="item_id" value="{{ $item->id }}">
                    <button type="submit" class="av-inv-row" title="{{ ___('common.click_to_wear') }}">
                        @if ($item->image)
                            <img src="{{ globalAsset($item->image) }}" alt="{{ $item->name }}">
                        @else
                            <div class="face-fallback"><i class="fa-solid fa-image"></i></div>
                        @endif
                        <span class="nm">{{ $item->name }}</span>
                    </button>
                </form>
            @endif
        @empty
            <p class="av-inventory__empty">Nothing owned yet — buy one below to start your collection.</p>
        @endforelse
    </div>
</div>
