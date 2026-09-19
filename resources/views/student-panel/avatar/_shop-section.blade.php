{{--
    One "shop + your items" card, used for all four avatar layer categories
    on the Avatar World page. Left side: unowned items, buyable with coins.
    Right side: a scrollable inventory of everything already owned — click
    an item to wear it.

    Three equip behaviours, picked automatically from $kind:
      - 'avatar'    single-select, never clearable (a body is always worn)
      - 'outfit'/'hat'  single-select, clearable (worn row's form omits
                    item_id to go back to "nothing in this slot")
      - 'accessory' multi-select — every row always posts its own item_id
                    to the same toggle route, which flips it on/off, so
                    several can be worn at once

    Usage: @include('student-panel.avatar._shop-section', [
        'title'    => 'Accessories',
        'hint'     => 'Small extras you can wear several of at once.',
        'items'    => $data['accessories'],           // Collection of AvatarItem
        'owned'    => $data['owned'],                  // array of owned item ids
        'equipped' => $data['equippedAccessories'],    // array (accessory) or single id/null (others)
        'kind'     => 'accessory',
        'coins'    => $data['coins'],
    ])
--}}
@php
    $multi     = $kind === 'accessory';
    $wearRoute = match ($kind) {
        'avatar'    => route('student-panel-avatar.select-avatar'),
        'outfit'    => route('student-panel-avatar.select-outfit'),
        'hat'       => route('student-panel-avatar.select-hat'),
        'accessory' => route('student-panel-avatar.toggle-accessory'),
    };
    $equippedIds = $multi ? (array) $equipped : [];
    $ownedItems  = $items->filter(fn ($i) => in_array($i->id, $owned, true))->values();
    $shopItems   = $items->reject(fn ($i) => in_array($i->id, $owned, true))->values();
@endphp
<div class="card ot-card mb-4">
    <div class="card-body">
        <h5 class="mb-0">{{ $title }}</h5>
        <p class="text-secondary mb-0">{{ $hint }}</p>

        <div class="av-shop-layout">
            <div class="av-shop-grid">
                @forelse ($shopItems as $item)
                    <div class="av-shop-item">
                        @if ($item->image)
                            <img src="{{ globalAsset($item->image) }}" alt="{{ $item->name }}">
                        @else
                            <div class="face-fallback"><i class="fa-solid fa-image"></i></div>
                        @endif
                        <div class="nm">{{ $item->name }}</div>
                        <div class="price">🪙 {{ $item->price_coins }}</div>
                        <form action="{{ route('student-panel-avatar.purchase') }}" method="post">
                            @csrf
                            <input type="hidden" name="item_id" value="{{ $item->id }}">
                            <button class="btn btn-outline-primary" {{ $coins < $item->price_coins ? 'disabled' : '' }}>Buy</button>
                        </form>
                    </div>
                @empty
                    <p class="text-secondary">
                        {{ $items->isEmpty() ? 'Nothing has been added yet — ask your school to add some in Website Setup.' : 'Everything here is already unlocked!' }}
                    </p>
                @endforelse
            </div>

            <div class="av-inventory">
                <div class="av-inventory__title"><i class="fa-solid fa-box-open"></i> {{ ___('common.your_items') }} ({{ $ownedItems->count() }})</div>
                <div class="av-inventory__list">
                    @forelse ($ownedItems as $item)
                        @php $isWorn = $multi ? in_array($item->id, $equippedIds, true) : $equipped === $item->id; @endphp

                        @if ($isWorn && $kind === 'avatar')
                            <div class="av-inv-row worn">
                                <img src="{{ globalAsset($item->image) }}" alt="{{ $item->name }}">
                                <span class="nm">{{ $item->name }}</span>
                                <i class="fa-solid fa-check worn-check"></i>
                            </div>
                        @elseif ($isWorn)
                            <form method="post" action="{{ $wearRoute }}" class="av-inv-row worn">
                                @csrf
                                @if ($multi)
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
                            <form method="post" action="{{ $wearRoute }}">
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
                        <p class="av-inventory__empty">Nothing owned yet — buy one from the left to start your collection.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
