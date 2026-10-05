@php
    $statusColors = [
        \App\Models\ClassContent\ClassContentModule::DRAFT => 'badge-basic-secondary-text',
        \App\Models\ClassContent\ClassContentModule::SUBMITTED => 'badge-basic-info-text',
        \App\Models\ClassContent\ClassContentModule::CHANGES_REQUESTED => 'badge-basic-warning-text',
        \App\Models\ClassContent\ClassContentModule::COORDINATOR_REVIEWED => 'badge-basic-info-text',
        \App\Models\ClassContent\ClassContentModule::APPROVED => 'badge-basic-success-text',
    ];
@endphp
<span class="{{ $statusColors[$status] ?? 'badge-basic-secondary-text' }}">{{ \App\Models\ClassContent\ClassContentModule::REVIEW_STATUSES[$status] ?? $status }}</span>
