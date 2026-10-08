{{-- Shows Coordinator's and Admin's latest notes side by side, correctly attributed — expects $item (a ClassContentModule). Renders nothing if neither exists. --}}
@if ($item->coordinator_feedback || $item->admin_feedback)
    <div class="cc-feedback-panel">
        @if ($item->coordinator_feedback)
            <div class="cc-feedback cc-feedback--coordinator">
                <div class="cc-feedback__head">
                    <i class="fa-solid fa-user-check"></i>
                    <span>Coordinator's note</span>
                    @if ($item->coordinator)
                        <span class="cc-feedback__meta">— {{ trim($item->coordinator->first_name . ' ' . $item->coordinator->last_name) }}@if ($item->coordinator_reviewed_at), {{ $item->coordinator_reviewed_at->format('d M Y') }}@endif</span>
                    @endif
                </div>
                <p class="cc-feedback__body">{{ $item->coordinator_feedback }}</p>
            </div>
        @endif
        @if ($item->admin_feedback)
            <div class="cc-feedback cc-feedback--admin">
                <div class="cc-feedback__head">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span>Admin's note</span>
                    @if ($item->approver && $item->isApproved())
                        <span class="cc-feedback__meta">— {{ trim($item->approver->first_name . ' ' . $item->approver->last_name) }}@if ($item->approved_at), {{ $item->approved_at->format('d M Y') }}@endif</span>
                    @endif
                </div>
                <p class="cc-feedback__body">{{ $item->admin_feedback }}</p>
            </div>
        @endif
    </div>

    @once
        @push('css')
            <style>
                .cc-feedback-panel { display: flex; flex-direction: column; gap: 10px; }
                .cc-feedback { border-radius: 10px; padding: 12px 14px; border: 1px solid transparent; }
                .cc-feedback--coordinator { background: #eff6ff; border-color: #bfdbfe; }
                .cc-feedback--admin { background: #fff7ed; border-color: #fed7aa; }
                .cc-feedback__head { display: flex; align-items: center; gap: 7px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; }
                .cc-feedback--coordinator .cc-feedback__head { color: #1d4ed8; }
                .cc-feedback--admin .cc-feedback__head { color: #c2410c; }
                .cc-feedback__meta { font-weight: 500; text-transform: none; letter-spacing: 0; color: #64748b; font-size: 11.5px; }
                .cc-feedback__body { margin: 6px 0 0; font-size: 13.5px; color: #334155; white-space: pre-wrap; }
            </style>
        @endpush
    @endonce
@endif
