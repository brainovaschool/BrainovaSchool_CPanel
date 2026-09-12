{{--
    Shared "select multiple rows" action bar for Website Setup list pages.
    Include once per index view, right above the table, with:
        @include('backend.partials.bulk-actions-bar', ['bulkStatusToggle' => true])
    Pass bulkStatusToggle = false for tables with no status column (none of
    the current modules, but kept as an escape hatch).
--}}
<div class="d-none align-items-center gap-2 mb-3 p-2 border rounded" id="bulkActionsBar">
    <span class="me-2"><strong id="bulkSelectedCount">0</strong> {{ ___('common.selected') }}</span>
    @if ($bulkStatusToggle ?? true)
        <button type="button" class="btn btn-sm btn-outline-success" onclick="bulk_status(1)">
            <i class="fa-solid fa-check"></i> {{ ___('common.activate') }}
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="bulk_status(0)">
            <i class="fa-solid fa-ban"></i> {{ ___('common.deactivate') }}
        </button>
    @endif
    <button type="button" class="btn btn-sm btn-outline-danger" onclick="bulk_delete()">
        <i class="fa-solid fa-trash-can"></i> {{ ___('common.delete_selected') }}
    </button>
</div>
