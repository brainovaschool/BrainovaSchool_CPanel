{{--
    One "shop + your items" card, used for all four avatar layer categories
    on the Avatar World page. Left: items not owned yet, buyable with coins.
    Right: the "Your Items" inventory (see _inventory.blade.php).

    Pass 'showInventory' => false when that category's inventory is shown
    somewhere else on the page instead — accessories put theirs up beside
    the avatar, so changes are visible as they're made.

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
    $shopItems = $items->reject(fn ($i) => in_array($i->id, $owned, true))->values();
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

            @if ($showInventory ?? true)
                @include('student-panel.avatar._inventory', [
                    'items'    => $items,
                    'owned'    => $owned,
                    'equipped' => $equipped,
                    'kind'     => $kind,
                ])
            @endif
        </div>
    </div>
</div>
