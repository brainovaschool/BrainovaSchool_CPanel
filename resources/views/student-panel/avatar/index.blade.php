@extends('student-panel.partials.master')

@section('title')
    {{ @$data['title'] }}
@endsection

@push('css')
<style>
/* The whole page sits on a wash of the chosen theme colour, and every panel
   is tinted from the same brand tokens — so switching theme visibly repaints
   the page rather than recolouring a few icons. */
.av-world{
    background:var(--bn-wash);
    border-radius:18px;
    padding:18px;
}
.av-world .ot-card{
    background:var(--bn-surface);
    border:1px solid var(--bn-surface-line);
    box-shadow:0 1px 2px rgba(20,20,30,.04), 0 10px 26px -18px rgba(20,20,30,.22);
}
.av-world .av-hero-card{ background:var(--bn-banner); }

.av-page-head{ display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:16px; }
.av-page-head h4{ color:var(--bn-primary-strong); }
.av-coin-pill{
    display:inline-flex; align-items:center; gap:6px; background:#fff8e6; color:#92400e; font-weight:800;
    padding:8px 16px; border-radius:20px; font-size:.95rem;
}

.av-hero{ display:flex; align-items:flex-start; gap:22px; flex-wrap:wrap; }
/* The accessories inventory sits in the hero, filling the space beside the
   avatar rather than being buried down in the shop. */
.av-hero .av-inventory{ flex:0 1 300px; max-width:320px; }
.av-hero .av-inventory__list{ max-height:210px; }
.av-preview{ position:relative; width:220px; aspect-ratio:1/1; flex-shrink:0; }
.av-preview .fallback-icon{
    width:100%; height:100%;
    display:flex; align-items:center; justify-content:center; font-size:5rem; color:var(--bn-primary);
}
/* Each layer is placed by the position/size/tilt set for that item in
   Website Setup, anchored on its own centre point. */
.av-preview .layer{ position:absolute; height:auto; }
.av-hero-name{ font-weight:800; font-size:1.2rem; color:var(--bn-ink); }
.av-hero-sub{ color:#7a8790; font-size:.84rem; margin-top:2px; }

.av-name-voice-row{ display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; margin-top:16px; }
.av-name-voice-row .field{ flex:1; min-width:160px; }

/* Base Character line-up — every character in one grid, each with its own
   price and the right action for its state. */
.av-char-grid{ display:grid; grid-template-columns:repeat(auto-fill, minmax(132px, 1fr)); gap:12px; margin-top:12px; }
.av-char-card{
    position:relative; text-align:center; padding:12px 10px; border-radius:14px;
    border:2px solid var(--bn-surface-line); background:#fff;
}
.av-char-card.worn{ border-color:var(--bn-primary); background:var(--bn-primary-soft); }
.av-char-art{ width:100%; aspect-ratio:1/1; display:flex; align-items:center; justify-content:center; color:#9aa4ab; font-size:1.6rem; }
.av-char-art img{ width:100%; height:100%; object-fit:contain; }
.av-char-name{ font-size:.84rem; font-weight:700; color:var(--bn-ink); margin-top:4px; }
.av-char-price{ font-size:.78rem; font-weight:700; color:#92400e; margin-top:3px; }
.av-char-price.free{ color:#15803d; }
.av-char-state{ font-size:.76rem; font-weight:800; color:var(--bn-primary); margin-top:6px; }
.av-char-locked{ font-size:.7rem; color:#9aa4ab; margin-top:4px; }
.av-char-card form{ margin-top:8px; }
.av-char-card .btn{ font-size:.75rem; padding:5px 14px; }
.av-char-pip{
    position:absolute; top:8px; right:8px; width:22px; height:22px; border-radius:50%;
    background:var(--bn-primary); color:#fff; font-size:.65rem;
    display:flex; align-items:center; justify-content:center;
}

.av-shop-layout{ display:flex; gap:18px; margin-top:12px; flex-wrap:wrap; align-items:flex-start; }

.av-shop-grid{ flex:2 1 300px; display:grid; grid-template-columns:repeat(auto-fill, minmax(112px, 1fr)); gap:10px; align-content:start; }
.av-shop-item{
    text-align:center; padding:10px 8px; border:2px solid var(--bn-surface-line); border-radius:12px;
    background:#fff;
}
.av-shop-item img{ width:56px; height:56px; border-radius:10px; object-fit:contain; margin-bottom:4px; background:#f4f6f7; }
.av-shop-item .face-fallback{ width:56px; height:56px; border-radius:10px; background:#eef2f4; margin:0 auto 4px; display:flex; align-items:center; justify-content:center; color:#9aa4ab; }
.av-shop-item .nm{ font-size:.82rem; font-weight:700; color:var(--bn-ink); }
.av-shop-item .price{ font-size:.74rem; color:#92400e; margin-top:3px; }
.av-shop-item form{ margin-top:6px; }
.av-shop-item .btn{ font-size:.72rem; padding:4px 10px; }

.av-inventory{ flex:1 1 220px; max-width:280px; border:1px solid var(--bn-surface-line); border-radius:14px; padding:12px; background:#fff; align-self:flex-start; }
.av-inventory__title{ font-size:.8rem; font-weight:800; color:var(--bn-ink); margin-bottom:8px; display:flex; align-items:center; gap:6px; }
.av-inventory__title i{ color:var(--bn-primary); }
.av-inventory__list{ display:flex; flex-direction:column; gap:6px; max-height:230px; overflow-y:auto; padding-right:4px; }
.av-inventory__empty{ font-size:.78rem; color:#7a8790; margin:0; }

.av-inv-row{
    display:flex; align-items:center; gap:10px; padding:8px 10px; border-radius:10px;
    border:1px solid var(--bn-surface-line); background:#fff;
}
.av-inv-row.worn{ border-color:var(--bn-primary); background:var(--bn-primary-soft); }
button.av-inv-row{ width:100%; text-align:left; cursor:pointer; font:inherit; -webkit-appearance:none; appearance:none; }
button.av-inv-row:hover{ background:#f4f8fa; }
.av-inv-row img{ width:32px; height:32px; object-fit:contain; flex-shrink:0; }
.av-inv-row .face-fallback{ width:32px; height:32px; border-radius:8px; background:#eef2f4; display:flex; align-items:center; justify-content:center; color:#9aa4ab; flex-shrink:0; }
.av-inv-row .nm{ font-size:.82rem; font-weight:600; flex:1; color:var(--bn-ink); }
.av-inv-row .worn-check{ color:var(--bn-primary); font-size:.85rem; flex-shrink:0; }
.remove-x{
    width:22px; height:22px; border-radius:50%; background:#e11d48; color:#fff; font-size:.65rem;
    display:flex; align-items:center; justify-content:center; flex-shrink:0; border:none; cursor:pointer; margin-left:auto;
}
.remove-x:hover{ background:#be123c; }

/* Shop / My Island tabs */
.av-tabs{ display:flex; gap:4px; background:var(--bn-surface); border:1px solid var(--bn-surface-line); padding:4px; border-radius:12px; width:fit-content; margin-bottom:16px; }
.av-tabs button{
    font:inherit; font-weight:700; font-size:.86rem; padding:8px 18px; border-radius:9px; border:none;
    background:transparent; color:var(--bn-ink); opacity:.6; cursor:pointer;
}
.av-tabs button[aria-selected="true"]{ background:var(--bn-primary); color:#fff; opacity:1; }

/* My Island — the banner is locked to the same 1920:1080 canvas the
   placement editor previews against, so a hub or building's saved
   position always lands in the same spot it showed in Website Setup.
   object-fit:contain means the picture is never cropped, only
   letterboxed if it isn't uploaded at that exact ratio. */
.av-island-banner{
    position:relative; width:100%; aspect-ratio:1920/1080; border-radius:16px;
    overflow:hidden; background:var(--bn-primary-soft);
}
.av-island-banner img{ width:100%; height:100%; display:block; object-fit:contain; }
.av-island-banner .empty{
    position:absolute; inset:0; display:flex; align-items:center; justify-content:center;
    padding:20px; text-align:center; color:var(--bn-ink); opacity:.6; font-size:.9rem;
}
.av-island-spot{
    position:absolute; height:auto; border:none; background:none; padding:0; cursor:default;
}
.av-island-spot img{ width:100%; height:auto; display:block; filter:drop-shadow(0 4px 10px rgba(20,20,30,.25)); }
.av-island-layout{ display:flex; gap:16px; margin-top:16px; align-items:stretch; flex-wrap:wrap; }
.av-island-side{
    flex:1 1 200px; background:#fff; border:1px dashed var(--bn-surface-line); border-radius:14px;
    padding:16px; min-height:220px; display:flex; align-items:center; justify-content:center;
    text-align:center; color:#9aa4ab; font-size:.82rem;
}
.av-island-center{ flex:2 1 320px; min-height:220px; }
</style>
@endpush

@section('content')
<div class="page-content">
    @include('backend.partials.learning-engine-styles')

    <div class="av-world">
    <div class="av-page-head">
        <h4 class="mb-0">My Learning Island</h4>
        <div class="d-flex align-items-center gap-3">
            @include('backend.partials.theme-picker', ['onLight' => true])
            <span class="av-coin-pill">🪙 {{ $data['coins'] }} coins</span>
        </div>
    </div>

    <div class="av-tabs" role="tablist">
        <button type="button" id="avTabShop" role="tab" aria-selected="true">Shop</button>
        <button type="button" id="avTabIsland" role="tab" aria-selected="false">My Island</button>
    </div>

    <div id="avPaneShop">

    <div class="card ot-card av-hero-card mb-4">
        <div class="card-body">
            <div class="av-hero">
                <div class="av-preview">
                    @forelse ($data['layers'] as $layer)
                        <img class="layer" src="{{ globalAsset($layer['image']) }}" alt=""
                            style="left:{{ $layer['x'] }}%; top:{{ $layer['y'] }}%; width:{{ $layer['scale'] }}%; transform:translate(-50%,-50%) rotate({{ $layer['rotation'] }}deg);">
                    @empty
                        <div class="fallback-icon"><i class="fa-solid fa-user"></i></div>
                    @endforelse
                </div>
                <div style="flex:1;min-width:200px;">
                    <div class="av-hero-name">{{ optional($data['profile'])->avatar_name ?: ___('common.my_avatar') }}</div>
                    <div class="av-hero-sub">Your avatar — speaks to you from the dashboard</div>
                </div>

                {{-- Accessories live up here rather than down in their shop
                     section, so taking one on or off shows on the avatar
                     right beside it. --}}
                @if (App\Models\LearningEngine\AvatarItem::sectionEnabled('accessory'))
                    @include('student-panel.avatar._inventory', [
                        'items'    => $data['accessories'],
                        'owned'    => $data['owned'],
                        'equipped' => $data['equippedAccessories'],
                        'kind'     => 'accessory',
                        'invTitle' => 'My Accessories',
                    ])
                @endif
            </div>

            <form action="{{ route('student-panel-avatar.save-profile') }}" method="post" class="av-name-voice-row">
                @csrf
                <div class="field">
                    <label class="form-label">Avatar's name</label>
                    <input type="text" name="avatar_name" class="form-control ot-input" maxlength="40"
                        value="{{ old('avatar_name', optional($data['profile'])->avatar_name) }}" placeholder="e.g. Kea, Sunny, Blaze...">
                </div>
                <div class="field">
                    <label class="form-label">Voice</label>
                    <select name="voice_preset" id="voicePresetSelect" class="form-select">
                        @foreach ($data['voices'] as $key => $voice)
                            <option value="{{ $key }}" {{ optional($data['profile'])->voice_preset === $key ? 'selected' : '' }}>{{ $voice['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="button" id="previewVoiceBtn" class="btn btn-outline-secondary"><i class="fa-solid fa-volume-high"></i> Preview</button>
                <button type="submit" class="btn ot-btn-primary">Save</button>
            </form>
        </div>
    </div>

    <div class="card ot-card mb-4">
        <div class="card-body">
            <h5 class="mb-0">Outfit</h5>
            <p class="text-secondary mb-0">The free ones are already yours. Earn coins to unlock the rest.</p>
            @include('student-panel.avatar._character-grid', [
                'items'    => $data['bodies'],
                'owned'    => $data['owned'],
                'equipped' => optional($data['profile'])->avatar_item_id,
                'coins'    => $data['coins'],
            ])
        </div>
    </div>

    @if (App\Models\LearningEngine\AvatarItem::sectionEnabled('outfit'))
        @include('student-panel.avatar._shop-section', [
            'title'    => 'Clothing Layer',
            'hint'     => 'A clothing layer worn over your outfit. Optional.',
            'items'    => $data['outfits'],
            'owned'    => $data['owned'],
            'equipped' => optional($data['profile'])->outfit_item_id,
            'kind'     => 'outfit',
            'coins'    => $data['coins'],
        ])
    @endif

    @if (App\Models\LearningEngine\AvatarItem::sectionEnabled('hat'))
        @include('student-panel.avatar._shop-section', [
            'title'    => 'Hat',
            'hint'     => 'Headwear worn on top of everything else. Optional.',
            'items'    => $data['hats'],
            'owned'    => $data['owned'],
            'equipped' => optional($data['profile'])->hat_item_id,
            'kind'     => 'hat',
            'coins'    => $data['coins'],
        ])
    @endif

    @if (App\Models\LearningEngine\AvatarItem::sectionEnabled('accessory'))
        @include('student-panel.avatar._shop-section', [
            'title'    => 'Accessories',
            'hint'     => 'Small extras you can wear several of at once. Everything you own is up beside your avatar.',
            'items'    => $data['accessories'],
            'owned'    => $data['owned'],
            'equipped' => $data['equippedAccessories'],
            'kind'     => 'accessory',
            'coins'    => $data['coins'],
            'showInventory' => false,
        ])
    @endif
    </div>

    <div id="avPaneIsland" hidden>
        <div class="av-island-banner" id="islandBanner">
            @if (setting('island_top_image'))
                <img src="{{ globalAsset(setting('island_top_image')) }}" alt="My Learning Island" id="islandBannerImg">
            @else
                <div class="empty">Your school hasn't added an island picture yet.</div>
            @endif

            @foreach ($data['hubs'] as $hub)
                @if ($hub->image)
                    <span class="av-island-spot" title="{{ $hub->name }}"
                        style="left:{{ $hub->pos_x }}%; top:{{ $hub->pos_y }}%; width:{{ $hub->scale }}%; transform:translate(-50%,-50%) rotate({{ $hub->rotation }}deg);">
                        <img src="{{ globalAsset($hub->image) }}" alt="{{ $hub->name }}">
                    </span>
                @endif
                @foreach ($hub->children as $building)
                    @if ($building->image)
                        <span class="av-island-spot" title="{{ $building->name }}"
                            style="left:{{ $building->pos_x }}%; top:{{ $building->pos_y }}%; width:{{ $building->scale }}%; transform:translate(-50%,-50%) rotate({{ $building->rotation }}deg);">
                            <img src="{{ globalAsset($building->image) }}" alt="{{ $building->name }}">
                        </span>
                    @endif
                @endforeach
            @endforeach
        </div>
        <div class="av-island-layout">
            <div class="av-island-side">More coming here soon.</div>
            <div class="av-island-center"></div>
            <div class="av-island-side">More coming here soon.</div>
        </div>
    </div>
    </div>
</div>

<script>
(function () {
    var voices = @json($data['voices']);
    var select = document.getElementById('voicePresetSelect');
    var btn    = document.getElementById('previewVoiceBtn');
    if (!btn || !('speechSynthesis' in window)) { if (btn) btn.style.display = 'none'; return; }

    btn.addEventListener('click', function () {
        var preset = voices[select.value] || voices.cheerful;
        var utter  = new SpeechSynthesisUtterance('Hi! This is how I sound.');
        utter.rate  = preset.rate;
        utter.pitch = preset.pitch;
        window.speechSynthesis.cancel();
        window.speechSynthesis.speak(utter);
    });
})();

(function () {
    var tabShop   = document.getElementById('avTabShop');
    var tabIsland = document.getElementById('avTabIsland');
    var paneShop  = document.getElementById('avPaneShop');
    var paneIsland = document.getElementById('avPaneIsland');
    if (!tabShop || !tabIsland) return;

    function show(which) {
        var onShop = which === 'shop';
        tabShop.setAttribute('aria-selected', onShop ? 'true' : 'false');
        tabIsland.setAttribute('aria-selected', onShop ? 'false' : 'true');
        paneShop.hidden = !onShop;
        paneIsland.hidden = onShop;
    }

    tabShop.addEventListener('click', function () { show('shop'); });
    tabIsland.addEventListener('click', function () { show('island'); });
})();

(function () {
    // Whatever shape the uploaded banner actually is, match the box to it
    // exactly so it always fills edge-to-edge — no cropping, no blank
    // letterbox strips down the sides.
    var banner = document.getElementById('islandBanner');
    var img    = document.getElementById('islandBannerImg');
    if (!banner || !img) return;

    function applyRatio() {
        if (img.naturalWidth && img.naturalHeight) {
            banner.style.aspectRatio = img.naturalWidth + '/' + img.naturalHeight;
        }
    }

    if (img.complete) {
        applyRatio();
    } else {
        img.addEventListener('load', applyRatio);
    }
})();
</script>
@endsection
