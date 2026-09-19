{{--
    The Base Character picker. Unlike the other layers this shows every
    character in one grid rather than splitting owned from unowned, because
    a student only ever wears one and wants to see the whole line-up with
    prices at a glance.

    Each card lands in one of three states:
      - wearing this one   → highlighted, no button
      - owned, not worn    → Wear
      - not owned          → price + Buy (disabled until they can afford it)

    A character priced 0 is free, so every student owns it from the start.

    Usage: @include('student-panel.avatar._character-grid', [
        'items'    => $data['bodies'],
        'owned'    => $data['owned'],
        'equipped' => optional($data['profile'])->avatar_item_id,
        'coins'    => $data['coins'],
    ])
--}}
<div class="av-char-grid">
    @forelse ($items as $item)
        @php
            $isOwned = in_array($item->id, $owned, true);
            $isWorn  = $equipped === $item->id;
            $isFree  = (int) $item->price_coins === 0;
        @endphp
        <div class="av-char-card {{ $isWorn ? 'worn' : '' }}">
            @if ($isWorn)
                <span class="av-char-pip"><i class="fa-solid fa-check"></i></span>
            @endif

            <div class="av-char-art">
                @if ($item->image)
                    <img src="{{ globalAsset($item->image) }}" alt="{{ $item->name }}">
                @else
                    <i class="fa-solid fa-image"></i>
                @endif
            </div>

            <div class="av-char-name">{{ $item->name }}</div>

            @if ($isWorn)
                <div class="av-char-state">{{ ___('common.wearing') }}</div>
            @elseif ($isOwned)
                @if ($isFree)
                    <div class="av-char-price free">{{ ___('common.free') }}</div>
                @endif
                <form action="{{ route('student-panel-avatar.select-avatar') }}" method="post">
                    @csrf
                    <input type="hidden" name="item_id" value="{{ $item->id }}">
                    <button class="btn ot-btn-primary">Wear</button>
                </form>
            @else
                <div class="av-char-price">🪙 {{ $item->price_coins }}</div>
                <form action="{{ route('student-panel-avatar.purchase') }}" method="post">
                    @csrf
                    <input type="hidden" name="item_id" value="{{ $item->id }}">
                    <button class="btn btn-outline-primary" {{ $coins < $item->price_coins ? 'disabled' : '' }}>Buy</button>
                </form>
                @if ($coins < $item->price_coins)
                    <div class="av-char-locked">{{ $item->price_coins - $coins }} more coins</div>
                @endif
            @endif
        </div>
    @empty
        <p class="text-secondary">No characters have been added yet — ask your school to add some in Website Setup.</p>
    @endforelse
</div>
