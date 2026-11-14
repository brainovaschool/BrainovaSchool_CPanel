@php
    $statusColors = [
        \App\Models\WebsiteSetup\ProgramClass::DRAFT => 'badge-basic-secondary-text',
        \App\Models\WebsiteSetup\ProgramClass::SUBMITTED => 'badge-basic-info-text',
        \App\Models\WebsiteSetup\ProgramClass::CHANGES_REQUESTED => 'badge-basic-warning-text',
        \App\Models\WebsiteSetup\ProgramClass::APPROVED => 'badge-basic-success-text',
    ];
@endphp
<span class="{{ $statusColors[$status] ?? 'badge-basic-secondary-text' }}">{{ \App\Models\WebsiteSetup\ProgramClass::REVIEW_STATUSES[$status] ?? $status }}</span>
