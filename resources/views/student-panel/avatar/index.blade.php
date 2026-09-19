@extends('student-panel.partials.master')

@section('title')
    {{ @$data['title'] }}
@endsection

@push('css')
<style>
.av-page-head{ display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:16px; }
.av-coin-pill{
    display:inline-flex; align-items:center; gap:6px; background:#fff8e6; color:#92400e; font-weight:800;
    padding:8px 16px; border-radius:20px; font-size:.95rem;
}

.av-hero{ display:flex; align-items:center; gap:22px; flex-wrap:wrap; }
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

.av-shop-layout{ display:flex; gap:18px; margin-top:12px; flex-wrap:wrap; align-items:flex-start; }

.av-shop-grid{ flex:2 1 300px; display:grid; grid-template-columns:repeat(auto-fill, minmax(130px, 1fr)); gap:14px; align-content:start; }
.av-shop-item{
    text-align:center; padding:14px 10px; border:2px solid rgba(15,41,55,0.08); border-radius:14px;
    background:#fbfeff;
}
.av-shop-item img{ width:64px; height:64px; border-radius:12px; object-fit:contain; margin-bottom:6px; background:#f4f6f7; }
.av-shop-item .face-fallback{ width:64px; height:64px; border-radius:12px; background:#eef2f4; margin:0 auto 6px; display:flex; align-items:center; justify-content:center; color:#9aa4ab; }
.av-shop-item .nm{ font-size:.82rem; font-weight:700; color:var(--bn-ink); }
.av-shop-item .price{ font-size:.74rem; color:#92400e; margin-top:3px; }
.av-shop-item form{ margin-top:8px; }
.av-shop-item .btn{ font-size:.74rem; padding:5px 12px; }

.av-inventory{ flex:1 1 220px; max-width:280px; border:1px solid rgba(15,41,55,.08); border-radius:14px; padding:14px; background:#fbfeff; align-self:flex-start; }
.av-inventory__title{ font-size:.8rem; font-weight:800; color:var(--bn-ink); margin-bottom:10px; display:flex; align-items:center; gap:6px; }
.av-inventory__title i{ color:var(--bn-primary); }
.av-inventory__list{ display:flex; flex-direction:column; gap:8px; max-height:280px; overflow-y:auto; padding-right:4px; }
.av-inventory__empty{ font-size:.78rem; color:#7a8790; margin:0; }

.av-inv-row{
    display:flex; align-items:center; gap:10px; padding:8px 10px; border-radius:10px;
    border:1px solid rgba(15,41,55,.08); background:#fff;
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
</style>
@endpush

@section('content')
<div class="page-content">
    @include('backend.partials.learning-engine-styles')

    <div class="av-page-head">
        <h4 class="mb-0">Avatar World</h4>
        <div class="d-flex align-items-center gap-3">
            @include('backend.partials.theme-picker', ['onLight' => true])
            <span class="av-coin-pill">🪙 {{ $data['coins'] }} coins</span>
        </div>
    </div>

    <div class="card ot-card mb-4">
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

    @include('student-panel.avatar._shop-section', [
        'title'    => 'Base Character',
        'hint'     => 'Free looks are yours already. Spend coins to unlock the rest.',
        'items'    => $data['bodies'],
        'owned'    => $data['owned'],
        'equipped' => optional($data['profile'])->avatar_item_id,
        'kind'     => 'avatar',
        'coins'    => $data['coins'],
    ])

    @include('student-panel.avatar._shop-section', [
        'title'    => 'Outfit',
        'hint'     => 'A clothing layer worn over your base character. Optional.',
        'items'    => $data['outfits'],
        'owned'    => $data['owned'],
        'equipped' => optional($data['profile'])->outfit_item_id,
        'kind'     => 'outfit',
        'coins'    => $data['coins'],
    ])

    @include('student-panel.avatar._shop-section', [
        'title'    => 'Hat',
        'hint'     => 'Headwear worn on top of everything else. Optional.',
        'items'    => $data['hats'],
        'owned'    => $data['owned'],
        'equipped' => optional($data['profile'])->hat_item_id,
        'kind'     => 'hat',
        'coins'    => $data['coins'],
    ])

    @include('student-panel.avatar._shop-section', [
        'title'    => 'Accessories',
        'hint'     => 'Small extras you can wear several of at once.',
        'items'    => $data['accessories'],
        'owned'    => $data['owned'],
        'equipped' => $data['equippedAccessories'],
        'kind'     => 'accessory',
        'coins'    => $data['coins'],
    ])
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
</script>
@endsection
