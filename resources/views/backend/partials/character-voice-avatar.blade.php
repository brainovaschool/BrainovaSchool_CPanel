{{--
    Compact, inline version of the character voice widget — a round avatar
    (fits inline in a hero/profile card) that speaks a real, personalized
    summary on tap, using the browser's own text-to-speech. Same Web Speech
    API approach as backend.partials.character-voice-widget (the floating
    version), just a different container for a different placement.

    Usage: @include('backend.partials.character-voice-avatar', [
        'image'       => $image,
        'name'        => 'Kea',
        'speakText'   => $speakText,
        'voicePreset' => 'cheerful', // optional — one of StudentAvatarRepository::VOICE_PRESETS, defaults to 'classic'
    ])
--}}
@if (!empty($speakText))
    @php
        $voice = \App\Repositories\LearningEngine\StudentAvatarRepository::VOICE_PRESETS[$voicePreset ?? 'classic']
            ?? \App\Repositories\LearningEngine\StudentAvatarRepository::VOICE_PRESETS['classic'];
    @endphp
    <button type="button" class="bn-voice-avatar" id="bnVoiceAvatarBtn" aria-label="{{ ___('common.tap_to_hear_from') }} {{ $name ?? 'Kea' }}">
        @if (!empty($image))
            <img src="{{ $image }}" alt="{{ $name ?? 'Kea' }}">
        @else
            <i class="fa-solid fa-feather"></i>
        @endif
        <span class="bn-voice-avatar__icon"><i class="fa-solid fa-volume-high"></i></span>
    </button>

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
            btn.classList.remove('bn-voice-avatar--speaking');
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
            btn.classList.add('bn-voice-avatar--speaking');
            speaking = true;
        });
    })();
    </script>
    @endpush
@endif
