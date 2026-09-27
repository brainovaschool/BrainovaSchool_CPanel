{{-- Loaded as an AJAX fragment into #roomModal on the My Island tab —
     see the fetch() call in student-panel/avatar/index.blade.php. --}}
<div class="modal-content">
    <div class="modal-header modal-header-image">
        <h5 class="modal-title">
            <i class="fa-solid fa-door-open"></i>
            {{ optional($data['building'])->name ?? 'Building' }}
        </h5>
        <button type="button" class="m-0 btn-close d-flex justify-content-center align-items-center" data-bs-dismiss="modal" aria-label="Close">
            <i class="fa fa-times text-white"></i>
        </button>
    </div>
    <div class="modal-body p-4">
        @if (!$data['building'])
            <div class="alert alert-warning mb-0">This building couldn't be found.</div>
        @elseif (!$data['room'])
            <div class="text-center py-4">
                <i class="fa-solid fa-map" style="font-size:2rem;color:#9aa4ab;"></i>
                <p class="text-secondary mt-3 mb-0">This building's story hasn't been written yet — check back soon!</p>
            </div>
        @else
            @php
                $mission = $data['room']['mission'];
                $percent = $data['room']['percent'];
                $revealed = $data['room']['revealed'];
                $complete = $data['room']['complete'];
                $activity = $data['room']['activity'];
                $clues = $mission->clue_lines ?? [];
            @endphp
            <div class="d-flex align-items-start gap-3 mb-3">
                @if (\App\Support\Character::image($mission->character))
                    <img src="{{ asset(\App\Support\Character::image($mission->character)) }}" alt="" style="width:56px;height:56px;border-radius:50%;object-fit:cover;flex-shrink:0;">
                @endif
                <div>
                    <div class="fw-bold">{{ \App\Support\Character::name($mission->character) }}</div>
                    <p class="mb-0 text-secondary" style="font-size:.92rem;">{{ $mission->intro_line }}</p>
                </div>
            </div>

            <h6 class="mb-2">{{ $mission->title }}</h6>

            @if ($percent === null)
                <p class="text-secondary" style="font-size:.85rem;">This mission isn't linked to any work yet — nothing to do here right now.</p>
            @else
                <div class="progress mb-2" style="height:8px;">
                    <div class="progress-bar" role="progressbar" style="width:{{ $percent }}%; background:var(--bn-primary,#0e8f81);"></div>
                </div>
                <p class="text-secondary mb-3" style="font-size:.8rem;">{{ $percent }}% there — {{ \App\Repositories\LearningEngine\StudentMissionRepository::MASTERY_THRESHOLD }}% finishes the mission.</p>
            @endif

            <ul class="list-unstyled mb-3" style="font-size:.9rem;">
                @foreach ($clues as $i => $clueText)
                    <li class="mb-2 d-flex gap-2">
                        <span>{{ $i < $revealed ? '🔓' : '🔒' }}</span>
                        <span class="{{ $i < $revealed ? '' : 'text-secondary' }}">
                            {{ $i < $revealed ? $clueText : 'Clue ' . ($i + 1) . ' — not found yet' }}
                        </span>
                    </li>
                @endforeach
            </ul>

            @if ($complete)
                <div class="alert alert-success">
                    <strong>Mission complete!</strong> {{ $mission->ending_line }}
                    @if ($mission->reward_note)
                        <div class="mt-1" style="font-size:.85rem;">Unlocked by: {{ $mission->reward_note }}</div>
                    @endif
                </div>
            @elseif ($activity)
                <a href="{{ $activity['url'] }}" class="btn ot-btn-primary">{{ $activity['label'] }}</a>
            @endif
        @endif
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary py-2 px-4" data-bs-dismiss="modal">Close</button>
    </div>
</div>
