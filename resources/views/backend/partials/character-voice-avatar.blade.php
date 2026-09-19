{{--
    Compact, inline version of the character voice widget — a round avatar
    (fits inline in a hero/profile card) that speaks a real, personalized
    summary on tap, using the browser's own text-to-speech. Same Web Speech
    API approach as backend.partials.character-voice-widget (the floating
    version), just a different container for a different placement.

    Usage: @include('backend.partials.character-voice-avatar', [
        'image'       => $image,   // single image URL — used by the 'avatar' variant, and as a fallback for 'card' if $layers is empty
        'layers'      => $layers,  // optional — ordered layers to stack for the 'card' variant, bottom first, each ['url' =>, 'x' =>, 'y' =>, 'scale' =>, 'rotation' =>] (see StudentAvatarRepository::equippedLayers())
        'name'        => 'Kea',
        'speakText'   => $speakText,
        'voicePreset' => 'cheerful', // optional — one of StudentAvatarRepository::VOICE_PRESETS, defaults to 'classic'
        'variant'     => 'card', // optional — 'avatar' (default, round circle) or 'card' (full row: icon + text + mic, for the Mission Control dashboard card grid)
    ])
--}}
@if (!empty($speakText))
    @php
        $voice = \App\Repositories\LearningEngine\StudentAvatarRepository::VOICE_PRESETS[$voicePreset ?? 'classic']
            ?? \App\Repositories\LearningEngine\StudentAvatarRepository::VOICE_PRESETS['classic'];
    @endphp
    @php $displayName = $name ?? ___('common.my_avatar'); @endphp
    @if (($variant ?? 'avatar') === 'card')
        @php
            $cardLayers = !empty($layers)
                ? $layers
                : (!empty($image) ? [['url' => $image, 'x' => 50, 'y' => 50, 'scale' => 100, 'rotation' => 0]] : []);
        @endphp
        <div class="bn-dv2-card bn-dv2-kea" id="bnVoiceAvatarBtn" role="button" tabindex="0" aria-label="{{ ___('common.tap_to_hear_from') }} {{ $displayName }}">
            @if (!empty($cardLayers))
                <div class="bn-dv2-kea__stack">
                    @foreach ($cardLayers as $layer)
                        <img src="{{ $layer['url'] }}" alt="{{ $displayName }}"
                            style="left:{{ $layer['x'] }}%; top:{{ $layer['y'] }}%; width:{{ $layer['scale'] }}%; transform:translate(-50%,-50%) rotate({{ $layer['rotation'] }}deg);">
                    @endforeach
                </div>
            @else
                <div class="fallback"><i class="fa-solid fa-user"></i></div>
            @endif
            <div>
                <div class="t">{{ ___('common.tap_to_hear_your_update') }}</div>
                <div class="m">{{ $displayName }} {{ ___('common.has_something_to_tell_you') }}</div>
            </div>
            <div class="mic"><i class="fa-solid fa-volume-high"></i></div>
        </div>
    @else
        <button type="button" class="bn-voice-avatar" id="bnVoiceAvatarBtn" aria-label="{{ ___('common.tap_to_hear_from') }} {{ $displayName }}">
            @if (!empty($image))
                <img src="{{ $image }}" alt="{{ $displayName }}">
            @else
                <i class="fa-solid fa-user"></i>
            @endif
            <span class="bn-voice-avatar__icon"><i class="fa-solid fa-volume-high"></i></span>
        </button>
    @endif

    @push('script')
    <script>
    (function () {
        var text = @json($speakText);
        var btn  = document.getElementById('bnVoiceAvatarBtn');
        if (!btn) return;

        if (!('speechSynthesis' in window)) {
            btn.style.display = 'none';
            return;
        }

        var speaking = false;

        function stop() {
            btn.classList.remove('bn-voice-avatar--speaking', 'is-speaking');
            speaking = false;
        }

        btn.addEventListener('click', function () {
            if (speaking) {
                window.speechSynthesis.cancel();
                stop();
                return;
            }

            var utter = new SpeechSynthesisUtterance(text);
            utter.rate = {{ $voice['rate'] }};
            utter.pitch = {{ $voice['pitch'] }};
            utter.onend = stop;
            utter.onerror = stop;

            window.speechSynthesis.cancel();
            window.speechSynthesis.speak(utter);
            btn.classList.add('bn-voice-avatar--speaking', 'is-speaking');
            speaking = true;
        });

        // The 'card' variant is a <div role="button">, not a real <button>,
        // so it needs its own keyboard activation for Enter/Space.
        btn.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                btn.click();
            }
        });
    })();
    </script>
    @endpush
@endif
