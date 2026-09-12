{{--
    Shared "select multiple rows" JS for Website Setup list pages. Works
    together with backend.partials.bulk-actions-bar and backend.partials.delete-ajax
    (for the confirm-dialog hidden inputs #alert_title etc, already on every page).

    Include once per index view with the module's route prefix:
        @include('backend.partials.bulk-actions-ajax', ['bulkRoute' => 'program'])
    Requires:
      - a "select all" checkbox with id="bulkSelectAll" in the table head
      - a checkbox with class="bulk-row-checkbox" and value="{{ $row->id }}"
        in each <tr id="row_{{ $row->id }}">
--}}
@push('script')
<script>
(function () {
    var bulkRoute = @json($bulkRoute);

    function bulkSelectedIds() {
        return $('.bulk-row-checkbox:checked').map(function () { return $(this).val(); }).get();
    }

    function bulkToggleBar() {
        var n = bulkSelectedIds().length;
        $('#bulkSelectedCount').text(n);
        if (n > 0) {
            $('#bulkActionsBar').removeClass('d-none').addClass('d-flex');
        } else {
            $('#bulkActionsBar').addClass('d-none').removeClass('d-flex');
        }
    }

    $(document).on('change', '.bulk-row-checkbox', function () {
        var all = $('.bulk-row-checkbox').length === $('.bulk-row-checkbox:checked').length;
        $('#bulkSelectAll').prop('checked', all);
        bulkToggleBar();
    });

    $(document).on('change', '#bulkSelectAll', function () {
        $('.bulk-row-checkbox').prop('checked', this.checked);
        bulkToggleBar();
    });

    window.bulk_delete = function () {
        var ids = bulkSelectedIds();
        if (!ids.length) return;

        Swal.fire({
            title: $('#alert_title').val(),
            text: $('#alert_subtitle').val(),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: $('#alert_yes_btn').val(),
            cancelButtonText: $('#alert_cancel_btn').val(),
        }).then(function (confirmed) {
            if (!confirmed.isConfirmed) return;

            $.ajax({
                type: 'POST',
                dataType: 'json',
                data: { ids: ids },
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                url: "{{ url('') }}" + '/' + bulkRoute + '/bulk-delete',
            }).done(function (response) {
                Swal.fire({
                    icon: response[1],
                    title: response[2],
                    text: response[0],
                    showCloseButton: true,
                    confirmButtonText: response[3],
                });
                if (response[1] !== 'error') {
                    ids.forEach(function (id) {
                        $('#row_' + id).fadeOut(500, function () { $(this).remove(); });
                    });
                    $('#bulkSelectAll').prop('checked', false);
                    bulkToggleBar();
                }
            }).fail(function () {
                Swal.fire('{{ ___('common.opps') }}...', '{{ ___('common.something_went_wrong_with_ajax') }}', 'error');
            });
        });
    };

    window.bulk_status = function (status) {
        var ids = bulkSelectedIds();
        if (!ids.length) return;

        $.ajax({
            type: 'POST',
            dataType: 'json',
            data: { ids: ids, status: status },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            url: "{{ url('') }}" + '/' + bulkRoute + '/bulk-status',
        }).done(function (response) {
            Swal.fire({
                icon: response[1],
                title: response[2],
                text: response[0],
                showCloseButton: true,
                confirmButtonText: response[3],
            });
            if (response[1] !== 'error') {
                setTimeout(function () { location.reload(); }, 900);
            }
        }).fail(function () {
            Swal.fire('{{ ___('common.opps') }}...', '{{ ___('common.something_went_wrong_with_ajax') }}', 'error');
        });
    };
})();
</script>
@endpush
