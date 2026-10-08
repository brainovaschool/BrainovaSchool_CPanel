{{-- Visual progress stepper for a module's review pipeline. Expects $status (a ClassContentModule::review_status value). --}}
@php
    use App\Models\ClassContent\ClassContentModule as CCM;
    $stages = [
        ['key' => CCM::DRAFT, 'label' => 'Draft', 'icon' => 'fa-pencil'],
        ['key' => CCM::SUBMITTED, 'label' => 'Submitted', 'icon' => 'fa-paper-plane'],
        ['key' => CCM::COORDINATOR_REVIEWED, 'label' => 'Coordinator Reviewed', 'icon' => 'fa-user-check'],
        ['key' => CCM::APPROVED, 'label' => 'Approved', 'icon' => 'fa-circle-check'],
    ];
    $order = [CCM::DRAFT => 0, CCM::SUBMITTED => 1, CCM::CHANGES_REQUESTED => 1, CCM::COORDINATOR_REVIEWED => 2, CCM::APPROVED => 3];
    $currentIndex = $order[$status] ?? 0;
    $isReturned = $status === CCM::CHANGES_REQUESTED;
@endphp
<div class="cc-stepper">
    @foreach ($stages as $i => $stage)
        @php
            $state = $i < $currentIndex ? 'done' : ($i === $currentIndex ? 'current' : 'upcoming');
            if ($isReturned && $i === 1) {
                $state = 'returned';
            }
        @endphp
        <div class="cc-step cc-step--{{ $state }}">
            <div class="cc-step__dot">
                <i class="fa-solid {{ $state === 'done' ? 'fa-check' : ($state === 'returned' ? 'fa-rotate-left' : $stage['icon']) }}"></i>
            </div>
            <div class="cc-step__label">{{ $stage['label'] }}@if ($state === 'returned')<br><span class="cc-step__sub">sent back for changes</span>@endif</div>
        </div>
        @if (!$loop->last)
            <div class="cc-step__line cc-step__line--{{ $i < $currentIndex ? 'done' : 'upcoming' }}"></div>
        @endif
    @endforeach
</div>

@once
    @push('css')
        <style>
            .cc-stepper { display: flex; align-items: flex-start; gap: 0; padding: 4px 0 10px; }
            .cc-step { display: flex; flex-direction: column; align-items: center; min-width: 76px; text-align: center; }
            .cc-step__dot {
                width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
                font-size: 13px; border: 2px solid var(--ot-border-color, #e2e8f0); color: #94a3b8; background: var(--ot-bg-primary, #fff);
                flex-shrink: 0;
            }
            .cc-step--done .cc-step__dot { background: #10b981; border-color: #10b981; color: #fff; }
            .cc-step--current .cc-step__dot { background: #2563eb; border-color: #2563eb; color: #fff; box-shadow: 0 0 0 4px rgba(37,99,235,.15); }
            .cc-step--returned .cc-step__dot { background: #f59e0b; border-color: #f59e0b; color: #fff; }
            .cc-step__label { font-size: 11px; font-weight: 600; margin-top: 6px; color: #64748b; line-height: 1.3; max-width: 90px; }
            .cc-step--done .cc-step__label, .cc-step--current .cc-step__label, .cc-step--returned .cc-step__label { color: #1e293b; }
            .cc-step__sub { font-weight: 500; color: #f59e0b; font-size: 10px; }
            .cc-step__line { flex: 1; height: 2px; background: #e2e8f0; margin-top: 17px; min-width: 20px; }
            .cc-step__line--done { background: #10b981; }
            @media (max-width: 560px) {
                .cc-stepper { overflow-x: auto; }
                .cc-step { min-width: 64px; }
                .cc-step__label { font-size: 10px; }
            }
        </style>
    @endpush
@endonce
