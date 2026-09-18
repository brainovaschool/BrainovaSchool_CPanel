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

.av-hero{ display:flex; align-items:center; gap:18px; flex-wrap:wrap; }
.av-preview{ position:relative; width:88px; height:88px; flex-shrink:0; }
.av-preview .face{
    width:88px; height:88px; border-radius:50%; background:var(--bn-primary-soft); border:3px solid var(--bn-primary);
    display:flex; align-items:center; justify-content:center; overflow:hidden; font-size:2.4rem;
}
.av-preview .face img{ width:100%; height:100%; object-fit:contain; }
.av-preview .acc-badge{
    position:absolute; bottom:-4px; right:-4px; width:34px; height:34px; border-radius:50%; background:#fff;
    border:2px solid #eee; display:flex; align-items:center; justify-content:center; overflow:hidden; font-size:1.1rem;
}
.av-preview .acc-badge img{ width:100%; height:100%; object-fit:contain; }
.av-hero-name{ font-weight:800; font-size:1.2rem; color:var(--bn-ink); }
.av-hero-sub{ color:#7a8790; font-size:.84rem; margin-top:2px; }

.av-name-voice-row{ display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; margin-top:16px; }
.av-name-voice-row .field{ flex:1; min-width:160px; }

.av-shop-grid{ display:grid; grid-template-columns:repeat(auto-fill, minmax(140px, 1fr)); gap:14px; margin-top:12px; }
.av-shop-item{
    text-align:center; padding:14px 10px; border:2px solid rgba(15,41,55,0.08); border-radius:14px; position:relative;
    background:#fbfeff;
}
.av-shop-item.equipped{ border-color:var(--bn-primary); background:var(--bn-primary-soft); }
.av-shop-item img{ width:64px; height:64px; border-radius:12px; object-fit:contain; margin-bottom:6px; background:#f4f6f7; }
.av-shop-item .face-fallback{ width:64px; height:64px; border-radius:50%; background:#eef2f4; margin:0 auto 6px; display:flex; align-items:center; justify-content:center; color:#9aa4ab; }
.av-shop-item .nm{ font-size:.82rem; font-weight:700; color:var(--bn-ink); }
.av-shop-item .price{ font-size:.74rem; color:#92400e; margin-top:3px; }
.av-shop-item form{ margin-top:8px; }
.av-shop-item .btn{ font-size:.74rem; padding:5px 12px; }
.av-shop-item .owned-tag{ font-size:.72rem; color:#7a8790; margin-top:6px; }
.av-current-pip{
    position:absolute; top:8px; right:8px; background:var(--bn-primary); color:#fff; border-radius:50%;
    width:20px; height:20px; font-size:.65rem; display:flex; align-items:center; justify-content:center;
}
</style>
@endpush

@section('content')
<div class="page-content">
    @include('backend.partials.learning-engine-styles')

    <div class="av-page-head">
        <h4 class="mb-0">🐦 My Avatar</h4>
        <div class="d-flex align-items-center gap-3">
            @include('backend.partials.theme-picker', ['onLight' => true])
            <span class="av-coin-pill">🪙 {{ $data['coins'] }} coins</span>
        </div>
    </div>

    <div class="card ot-card mb-4">
        <div class="card-body">
            <div class="av-hero">
                <div class="av-preview">
                    <div class="face">
                        @if (optional($data['profile'])->avatar && $data['profile']->avatar->image)
                            <img src="{{ globalAsset($data['profile']->avatar->image) }}" alt="{{ $data['profile']->avatar->name }}">
                        @else
                            <i class="fa-solid fa-user" style="color:var(--bn-primary);font-size:2.4rem;"></i>
                        @endif
                    </div>
                    @if (optional($data['profile'])->accessory && $data['profile']->accessory->image)
                        <div class="acc-badge"><img src="{{ globalAsset($data['profile']->accessory->image) }}" alt="{{ $data['profile']->accessory->name }}"></div>
                    @endif
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

    <div class="card ot-card mb-4">
        <div class="card-body">
            <h5 class="mb-0">Avatar Looks</h5>
            <p class="text-secondary mb-0">Free looks are yours already. Spend coins to unlock the rest.</p>
            <div class="av-shop-grid">
                @forelse ($data['avatars'] as $item)
                    @php $owned = in_array($item->id, $data['owned'], true); $isCurrent = optional($data['profile'])->avatar_item_id === $item->id; @endphp
                    <div class="av-shop-item {{ $isCurrent ? 'equipped' : '' }}">
                        @if ($isCurrent)<span class="av-current-pip"><i class="fa-solid fa-check"></i></span>@endif
                        @if ($item->image)
                            <img src="{{ globalAsset($item->image) }}" alt="{{ $item->name }}">
                        @else
                            <div class="face-fallback"><i class="fa-solid fa-image"></i></div>
                        @endif
                        <div class="nm">{{ $item->name }}</div>
                        @if ($isCurrent)
                            <div class="owned-tag">Wearing</div>
                        @elseif ($owned)
                            <form action="{{ route('student-panel-avatar.select-avatar') }}" method="post">
                                @csrf
                                <input type="hidden" name="item_id" value="{{ $item->id }}">
                                <button class="btn ot-btn-primary">Wear</button>
                            </form>
                        @else
                            <div class="price">🪙 {{ $item->price_coins }}</div>
                            <form action="{{ route('student-panel-avatar.purchase') }}" method="post">
                                @csrf
                                <input type="hidden" name="item_id" value="{{ $item->id }}">
                                <button class="btn btn-outline-primary" {{ $data['coins'] < $item->price_coins ? 'disabled' : '' }}>Buy</button>
                            </form>
                        @endif
                    </div>
                @empty
                    <p class="text-secondary">No avatar looks have been added yet — ask your school to add some in Website Setup.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="card ot-card">
        <div class="card-body">
            <h5 class="mb-0">Accessories</h5>
            <p class="text-secondary mb-0">A small badge shown on your avatar's corner. Pick one, or none.</p>
            <div class="av-shop-grid">
                <div class="av-shop-item {{ !optional($data['profile'])->accessory_item_id ? 'equipped' : '' }}">
                    @if (!optional($data['profile'])->accessory_item_id)<span class="av-current-pip"><i class="fa-solid fa-check"></i></span>@endif
                    <div class="face-fallback"><i class="fa-solid fa-ban"></i></div>
                    <div class="nm">None</div>
                    @if (optional($data['profile'])->accessory_item_id)
                        <form action="{{ route('student-panel-avatar.select-accessory') }}" method="post">
                            @csrf
                            <button class="btn btn-outline-secondary">Remove</button>
                        </form>
                    @else
                        <div class="owned-tag">Equipped</div>
                    @endif
                </div>
                @forelse ($data['accessories'] as $item)
                    @php $owned = in_array($item->id, $data['owned'], true); $isCurrent = optional($data['profile'])->accessory_item_id === $item->id; @endphp
                    <div class="av-shop-item {{ $isCurrent ? 'equipped' : '' }}">
                        @if ($isCurrent)<span class="av-current-pip"><i class="fa-solid fa-check"></i></span>@endif
                        @if ($item->image)
                            <img src="{{ globalAsset($item->image) }}" alt="{{ $item->name }}">
                        @else
                            <div class="face-fallback"><i class="fa-solid fa-image"></i></div>
                        @endif
                        <div class="nm">{{ $item->name }}</div>
                        @if ($isCurrent)
                            <div class="owned-tag">Equipped</div>
                        @elseif ($owned)
                            <form action="{{ route('student-panel-avatar.select-accessory') }}" method="post">
                                @csrf
                                <input type="hidden" name="item_id" value="{{ $item->id }}">
                                <button class="btn ot-btn-primary">Wear</button>
                            </form>
                        @else
                            <div class="price">🪙 {{ $item->price_coins }}</div>
                            <form action="{{ route('student-panel-avatar.purchase') }}" method="post">
                                @csrf
                                <input type="hidden" name="item_id" value="{{ $item->id }}">
                                <button class="btn btn-outline-primary" {{ $data['coins'] < $item->price_coins ? 'disabled' : '' }}>Buy</button>
                            </form>
                        @endif
                    </div>
                @empty
                    <p class="text-secondary">No accessories have been added yet.</p>
                @endforelse
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
</script>
@endsection
