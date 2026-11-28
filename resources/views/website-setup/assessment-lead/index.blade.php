@extends('backend.master')
@section('title')
    {{ @$data['title'] }}
@endsection
@section('content')
    <div class="page-content">
        <div class="page-header">
            <div class="row">
                <div class="col-sm-6">
                    <h4 class="bradecrumb-title mb-1">{{ $data['title'] }}</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item">{{ $data['title'] }}</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="card ot-card mb-20">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Google Meet for meetings</h4>
                <span class="badge-basic-{{ $data['calendarConnected'] ? 'success' : 'danger' }}-text">{{ $data['calendarConnected'] ? 'Connected' : 'Not connected' }}</span>
            </div>
            <div class="card-body">
                @if ($data['calendarConnected'])
                    <p class="text-secondary mb-3">Connected — every meeting a parent books now gets a real Google Meet link automatically.</p>
                    <form method="post" action="{{ route('assessment-lead.calendar-disconnect') }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-danger">Disconnect</button>
                    </form>
                @else
                    <p class="text-secondary mb-3">Not connected yet — bookings will use a free Jitsi video link instead until this is set up. Paste your Google Cloud OAuth Client ID/Secret below (the same ones used for the Google Drive connection work fine — just reuse them), save, then click Connect and sign in with the Google account that should host these meetings.</p>
                    <form method="post" action="{{ route('assessment-lead.calendar-settings') }}" class="d-flex align-items-end gap-2 flex-wrap mb-3">
                        @csrf
                        <div>
                            <label class="form-label">Google Client ID</label>
                            <input type="text" name="assessment_calendar_client_id" class="form-control" style="min-width:280px" value="{{ $data['calendarClientId'] }}" required>
                        </div>
                        <div>
                            <label class="form-label">Google Client Secret</label>
                            <input type="text" name="assessment_calendar_client_secret" class="form-control" style="min-width:280px" value="{{ $data['calendarClientSecret'] }}" required>
                        </div>
                        <button type="submit" class="btn btn-sm ot-btn-primary">Save</button>
                    </form>
                    <a href="{{ route('assessment-lead.calendar-connect') }}" class="btn btn-sm ot-btn-primary">Connect Google Calendar</a>
                @endif
            </div>
        </div>

        <div class="table-content table-basic mt-20">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">{{ $data['title'] }}</h4>
                    <form method="get" class="d-flex align-items-center gap-2">
                        <select name="status" class="form-control" onchange="this.form.submit()" style="min-width:180px">
                            <option value="">All statuses</option>
                            @foreach ($data['statuses'] as $key => $label)
                                <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
                <div class="card-body">
                    <div class="d-none align-items-center gap-2 mb-3 p-2 border rounded" id="bulkActionsBar">
                        <span class="me-2"><strong id="bulkSelectedCount">0</strong> {{ ___('common.selected') }}</span>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="bulk_resend_leads()">
                            <i class="fa-solid fa-paper-plane"></i> Resend Links
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success" onclick="bulk_whatsapp_leads()">
                            <i class="fa-brands fa-whatsapp"></i> Open WhatsApp
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="bulk_delete()">
                            <i class="fa-solid fa-trash-can"></i> {{ ___('common.delete_selected') }}
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered class-table">
                            <thead class="thead">
                                <tr>
                                    <th style="width:36px"><input type="checkbox" id="bulkSelectAll"></th>
                                    <th class="serial">{{ ___('common.sr_no') }}</th>
                                    <th>Student</th>
                                    <th>Grade</th>
                                    <th>Subject</th>
                                    <th>Syllabus</th>
                                    <th>Slot</th>
                                    <th>Meeting</th>
                                    <th>Assigned assessment</th>
                                    <th>Status</th>
                                    <th>Link sent</th>
                                    <th class="action">{{ ___('common.action') }}</th>
                                </tr>
                            </thead>
                            <tbody class="tbody">
                                @forelse ($data['leads'] as $key => $row)
                                    <tr id="row_{{ $row->id }}">
                                        <td><input type="checkbox" class="bulk-row-checkbox" value="{{ $row->id }}" data-wa="{{ $row->whatsapp_reminder_link ?: $row->whatsapp_invite_link }}"></td>
                                        <td class="serial">{{ ++$key }}</td>
                                        <td>
                                            <b>{{ $row->student_name }}</b>
                                            <div class="text-secondary" style="font-size:12px">{{ $row->guardian_name }} · {{ $row->email }} · {{ $row->whatsapp_number }}</div>
                                        </td>
                                        <td>Grade {{ $row->grade }}</td>
                                        <td>{{ $row->subject }}</td>
                                        <td>{{ $row->syllabus }}</td>
                                        <td>
                                            @if ($row->slot)
                                                {{ $row->slot->slot_date->format('d M Y') }} <br><span class="text-secondary" style="font-size:12px">{{ $row->slot->time_label }}</span>
                                            @elseif ($row->booked_slot_label)
                                                <span class="text-secondary">{{ $row->booked_slot_label }}</span> <span class="badge-basic-warning-text" title="The slot itself was removed by Clean Up Past Slots - this is kept from when it was booked.">past</span>
                                            @endif
                                        </td>
                                        <td style="font-size:12px">
                                            @if ($row->meeting_released_at)
                                                <span class="badge-basic-danger-text">Slot released</span><br>
                                                <span class="text-secondary">no confirmation by {{ $row->meeting_released_at->format('d M, H:i') }}</span>
                                            @elseif ($row->status !== \App\Models\WebsiteSetup\AssessmentLead::STATUS_BOOKED_MEETING)
                                                <span class="text-secondary">—</span>
                                            @elseif ($row->meeting_confirmed_at)
                                                <span class="badge-basic-success-text">Confirmed</span><br>
                                                <span class="text-secondary">{{ $row->meeting_confirmed_at->format('d M, H:i') }}</span>
                                            @elseif ($row->meeting_reminder_sent_at)
                                                <span class="badge-basic-warning-text">Reminded</span><br>
                                                <span class="text-secondary">awaiting confirmation</span>
                                            @else
                                                <span class="text-secondary">Not yet reminded</span>
                                            @endif
                                            @if ($row->meeting_link)
                                                <br><a href="{{ $row->meeting_link }}" target="_blank" rel="noopener">Meeting link</a>
                                            @endif
                                            @if ($row->whatsapp_reminder_link)
                                                <br><a href="{{ $row->whatsapp_reminder_link }}" target="_blank" rel="noopener">Send WhatsApp reminder</a>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($row->assigned_assessment_title)
                                                {{ $row->assigned_assessment_title }}
                                            @else
                                                <span class="badge-basic-danger-text">Unmatched — needs a published assessment for Grade {{ $row->grade }}</span>
                                            @endif
                                        </td>
                                        <td><span class="badge-basic-success-text">{{ $row->status_label }}</span></td>
                                        <td>{{ $row->link_sent_at?->format('d M, H:i') ?? '—' }}</td>
                                        <td class="action">
                                            <div class="d-flex flex-column gap-1">
                                                <form method="post" action="{{ route('assessment-lead.resend', $row->id) }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm ot-btn-primary w-100">Resend link</button>
                                                </form>
                                                @if ($row->report_link)
                                                    <a href="{{ $row->report_link }}" target="_blank" rel="noopener" class="btn btn-sm btn-secondary">View report</a>
                                                @else
                                                    <button type="button" class="btn btn-sm btn-secondary" disabled title="No assessment taken yet">View report</button>
                                                @endif
                                                @if ($row->whatsapp_invite_link)
                                                    <a href="{{ $row->whatsapp_invite_link }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-success">Send WhatsApp invite</a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="100%" class="text-center gray-color">
                                            <img src="{{ asset('images/no_data.svg') }}" alt="" class="mb-primary" width="100">
                                            <p class="mb-0 text-center">{{ ___('common.no_data_available') }}</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="ot-pagination pagination-content d-flex justify-content-end align-content-center py-3">
                        <nav><ul class="pagination justify-content-between">{!! $data['leads']->links() !!}</ul></nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('script')
    <script>
        function bulk_resend_leads() {
            var ids = $('.bulk-row-checkbox:checked').map(function () { return $(this).val(); }).get();
            if (!ids.length) { Swal.fire('Select at least one row first.'); return; }
            $.ajax({
                type: 'POST',
                dataType: 'json',
                data: { ids: ids },
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                url: "{{ route('assessment-lead.bulk-resend') }}",
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
        }

        // Each checked row's own WhatsApp link (whichever one applies -
        // meeting reminder or assessment invite - see data-wa) just gets
        // opened in its own tab, staggered a little so the browser's
        // pop-up blocker is less likely to swallow them all at once.
        // There is no such thing as a single bulk WhatsApp send without
        // a paid, approved WhatsApp Business sender (see the 2026-11-26
        // decision not to pay for Twilio WhatsApp) - this is the free
        // alternative: open every chat with its message already typed,
        // so sending is still just one tap per chat, not one retype.
        function bulk_whatsapp_leads() {
            var links = [];
            $('.bulk-row-checkbox:checked').each(function () {
                var wa = $(this).data('wa');
                if (wa) links.push(wa);
            });
            if (!links.length) {
                Swal.fire('None of the selected leads have a WhatsApp message ready - need a WhatsApp number, and either a booked meeting or an assessment link.');
                return;
            }
            links.forEach(function (url, i) {
                setTimeout(function () { window.open(url, '_blank'); }, i * 400);
            });
            Swal.fire({
                icon: 'info',
                title: 'Opening WhatsApp',
                text: 'Opening ' + links.length + ' WhatsApp chat(s) - allow pop-ups for this site if your browser blocks them, then just tap Send in each one.',
            });
        }
    </script>
@endpush
@include('backend.partials.bulk-actions-ajax', ['bulkRoute' => 'assessment-lead'])
