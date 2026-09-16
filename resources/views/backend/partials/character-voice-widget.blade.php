{{--
    A floating character widget that speaks a real, personalized summary
    using the browser's own text-to-speech (Web Speech API) — no AI call,
    no cost, works even if every AI service the app depends on is down.
    Browsers block audio from autoplaying on page load, so this never
    speaks on its own — it needs one tap, which also doubles as a natural
    stop/replay control.

    Usage: @include('backend.partials.character-voice-widget', [
        'image'     => $lh['greeting_image'] ?? null,
        'name'      => $lh['greeting_name'] ?? 'Kea',
        'speakText' => $speakText,   // a plain sentence, already composed
    ])
--}}
@if (!empty($speakText))
    <div class="bn-voice-widget" id="beVoiceWidget">
        <button type="button" class="bn-voice-widget__btn" id="bnVoiceBtn" aria-label="{{ ___('common.hear_from') }} {{ $name ?? 'Kea' }}">
            @if (!empty($image))
                <img src="{{ $image }}" alt="{{ $name ?? 'Kea' }}">
            @else
                <i class="fa-solid fa-feather"></i>
            @endif
            <span class="bn-voice-widget__pulse"></span>
        </button>
        <div class="bn-voice-widget__bubble" id="bnVoiceBubble">{{ ___('common.tap_to_hear_from') }} {{ $name ?? 'Kea' }}</div>
    </div>

    @push('script')
    <script>
    (function () {
        var text     = @json($speakText);
        var name     = @json($name ?? 'Kea');
        var btn      = document.getElementById('bnVoiceBtn');
        var widget   = document.getElementById('beVoiceWidget');
        var bubble   = document.getElementById('bnVoiceBubble');
        if (!btn || !widget) return;

        if (!('speechSynthesis' in window)) {
            widget.style.display = 'none';
            return;
        }

        var idleText = bubble ? bubble.textContent : '';
        var speaking = false;

        function stop() {
            widget.classList.remove('bn-voice-widget--speaking');
            if (bubble) bubble.textContent = idleText;
            speaking = false;
        }

        btn.addEventListener('click', function () {
            if (speaking) {
                window.speechSynthesis.cancel();
                stop();
                return;
            }

            var utter = new SpeechSynthesisUtterance(text);
            utter.rate = 0.98;
            utter.pitch = 1.15;
            utter.onend = stop;
            utter.onerror = stop;

            window.speechSynthesis.cancel();
            window.speechSynthesis.speak(utter);
            widget.classList.add('bn-voice-widget--speaking');
            if (bubble) bubble.textContent = name + '…';
            speaking = true;
        });
    })();
    </script>
    @endpush
@endif
