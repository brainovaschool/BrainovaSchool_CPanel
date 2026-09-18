@extends('student-panel.partials.master')

@section('title')
    {{ @$data['title'] }}
@endsection

@push('css')
<style>
.av-coin-badge{ display:inline-flex; align-items:center; gap:6px; background:#fff8e6; color:#92400e; font-weight:700; padding:8px 14px; border-radius:20px; font-size:.95rem; }
.av-grid{ display:grid; grid-template-columns:repeat(auto-fill, minmax(130px, 1fr)); gap:14px; margin-top:10px; }
.av-item{ text-align:center; padding:12px 8px; border:2px solid #eee; border-radius:14px; position:relative; }
.av-item.owned{ border-color:#e0e0e0; }
.av-item.selected{ border-color:#0097b2; background:#eaf7f9; }
.av-item img{ width:64px; height:64px; border-radius:50%; object-fit:cover; margin-bottom:6px; }
.av-item .nm{ font-size:.8rem; font-weight:700; }
.av-item .price{ font-size:.72rem; color:#92400e; margin-top:2px; }
.av-item form{ margin-top:8px; }
.av-item .btn{ font-size:.72rem; padding:4px 10px; }
.av-current-pill{ position:absolute; top:6px; right:6px; background:#0097b2; color:#fff; border-radius:50%; width:20px; height:20px; font-size:.65rem; display:flex; align-items:center; justify-content:center; }
</style>
@endpush

@section('content')
<div class="page-content">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <h4 class="mb-0">🐦 My Avatar</h4>
        <span class="av-coin-badge">🪙 {{ $data['coins'] }} coins</span>
    </div>

    <div class="card ot-card mb-4">
        <div class="card-body">
            <h5 class="mb-1">{{ optional($data['profile'])->avatar_name ?: 'Kea' }}</h5>
            <p class="text-secondary mb-3">Give your avatar a name and a voice — she'll use them the next time she talks to you on your dashboard.</p>
            <form action="{{ route('student-panel-avatar.save-profile') }}" method="post" class="row g-3">
                @csrf
                <div class="col-md-6">
                    <label class="form-label">Avatar's name</label>
                    <input type="text" name="avatar_name" class="form-control ot-input" maxlength="40"
                        value="{{ old('avatar_name', optional($data['profile'])->avatar_name) }}" placeholder="e.g. Kea, Sunny, Blaze...">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Voice</label>
                    <select name="voice_preset" id="voicePresetSelect" class="form-select">
                        @foreach ($data['voices'] as $key => $voice)
                            <option value="{{ $key }}" {{ optional($data['profile'])->voice_preset === $key ? 'selected' : '' }}>{{ $voice['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" id="previewVoiceBtn" class="btn btn-outline-secondary w-100"><i class="fa-solid fa-volume-high"></i> Preview</button>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn ot-btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card ot-card mb-4">
        <div class="card-body">
            <h5 class="mb-0">Avatar Looks</h5>
            <p class="text-secondary mb-0">Pick the look you already own, or spend coins to unlock a new one.</p>
            <div class="av-grid">
                @forelse ($data['avatars'] as $item)
                    @php $owned = in_array($item->id, $data['owned'], true); @endphp
                    <div class="av-item {{ $owned ? 'owned' : '' }} {{ optional($data['profile'])->avatar_item_id === $item->id ? 'selected' : '' }}">
                        @if (optional($data['profile'])->avatar_item_id === $item->id)
                            <span class="av-current-pill"><i class="fa-solid fa-check"></i></span>
                        @endif
                        @if ($item->image)
                            <img src="{{ globalAsset($item->image) }}" alt="{{ $item->name }}">
                        @endif
                        <div class="nm">{{ $item->name }}</div>
                        @if (!$owned)
                            <div class="price">🪙 {{ $item->price_coins }}</div>
                            <form action="{{ route('student-panel-avatar.purchase') }}" method="post">
                                @csrf
                                <input type="hidden" name="item_id" value="{{ $item->id }}">
                                <button class="btn btn-outline-primary" {{ $data['coins'] < $item->price_coins ? 'disabled' : '' }}>Buy</button>
                            </form>
                        @elseif (optional($data['profile'])->avatar_item_id !== $item->id)
                            <form action="{{ route('student-panel-avatar.select-avatar') }}" method="post">
                                @csrf
                                <input type="hidden" name="item_id" value="{{ $item->id }}">
                                <button class="btn ot-btn-primary">Wear</button>
                            </form>
                        @else
                            <div class="price">Wearing</div>
                        @endif
                    </div>
                @empty
                    <p class="text-secondary">No avatar looks have been added yet — check back later.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="card ot-card">
        <div class="card-body">
            <h5 class="mb-0">Accessories</h5>
            <p class="text-secondary mb-0">A small badge shown on your avatar's corner. Optional — pick one or none.</p>
            <div class="av-grid">
                @if (optional($data['profile'])->accessory_item_id)
                    <div class="av-item">
                        <div class="nm">No accessory</div>
                        <form action="{{ route('student-panel-avatar.select-accessory') }}" method="post">
                            @csrf
                            <button class="btn btn-outline-secondary">Remove</button>
                        </form>
                    </div>
                @endif
                @forelse ($data['accessories'] as $item)
                    @php $owned = in_array($item->id, $data['owned'], true); @endphp
                    <div class="av-item {{ $owned ? 'owned' : '' }} {{ optional($data['profile'])->accessory_item_id === $item->id ? 'selected' : '' }}">
                        @if (optional($data['profile'])->accessory_item_id === $item->id)
                            <span class="av-current-pill"><i class="fa-solid fa-check"></i></span>
                        @endif
                        @if ($item->image)
                            <img src="{{ globalAsset($item->image) }}" alt="{{ $item->name }}">
                        @endif
                        <div class="nm">{{ $item->name }}</div>
                        @if (!$owned)
                            <div class="price">🪙 {{ $item->price_coins }}</div>
                            <form action="{{ route('student-panel-avatar.purchase') }}" method="post">
                                @csrf
                                <input type="hidden" name="item_id" value="{{ $item->id }}">
                                <button class="btn btn-outline-primary" {{ $data['coins'] < $item->price_coins ? 'disabled' : '' }}>Buy</button>
                            </form>
                        @elseif (optional($data['profile'])->accessory_item_id !== $item->id)
                            <form action="{{ route('student-panel-avatar.select-accessory') }}" method="post">
                                @csrf
                                <input type="hidden" name="item_id" value="{{ $item->id }}">
                                <button class="btn ot-btn-primary">Wear</button>
                            </form>
                        @else
                            <div class="price">Wearing</div>
                        @endif
                    </div>
                @empty
                    <p class="text-secondary">No accessories have been added yet — check back later.</p>
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
