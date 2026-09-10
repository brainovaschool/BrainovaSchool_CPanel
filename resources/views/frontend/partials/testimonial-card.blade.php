<div class="bn-tm-card">
    @if ($item->rating)
        <div class="bn-tm-stars" aria-label="{{ $item->rating }} out of 5">{{ str_repeat('★', $item->rating) }}{{ str_repeat('☆', 5 - $item->rating) }}</div>
    @endif
    <p class="bn-tm-quote">“{{ $item->quote }}”</p>
    <div class="bn-tm-who">
        @if ($item->image)
            <img src="{{ $item->image }}" alt="{{ $item->name }}">
        @endif
        <span>
            <span class="bn-tm-name d-block">{{ $item->name }}</span>
            @if ($item->role)<span class="bn-tm-role">{{ $item->role }}</span>@endif
        </span>
    </div>
</div>
