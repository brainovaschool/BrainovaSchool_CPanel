@extends('backend.master')
@section('title')
{{ @$pt }}
@endsection

@section('content')
    <div class="page-content">

        {{-- bradecrumb Area S t a r t --}}
        <div class="page-header">
            <div class="row">
                <div class="col-sm-6">
                    <h4 class="bradecrumb-title mb-1">{{ @$pt  }}</h1>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item">{{ @$pt  }}</li>
                    </ol>
                </div>
            </div>
        </div>
        {{-- bradecrumb Area E n d --}}

        <div class="card ot-card mb-24">
            <div class="card-header">
                <h4>Manage a Person's Notifications</h4>
            </div>
            <div class="card-body">
                <form method="get" action="{{ route('settings.notification-settings') }}" class="d-flex gap-2 align-items-end flex-wrap mb-3">
                    <div style="min-width:280px">
                        <label class="form-label">Person</label>
                        <select name="user_id" class="form-control ot-input" onchange="this.form.submit()">
                            <option value="">Choose a person…</option>
                            @foreach ($notifiedUsers as $u)
                                <option value="{{ $u->id }}" {{ (int) $selectedUserId === $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    <noscript><button type="submit" class="btn ot-btn-primary">Load</button></noscript>
                </form>

                @if ($selectedUserId)
                    @if ($selectedUserNotifications->isEmpty())
                        <p class="text-secondary mb-0">This person has no notifications.</p>
                    @else
                        <form method="post" action="{{ route('settings.notification-settings.clear', $selectedUserId) }}" class="mb-3"
                              onsubmit="return confirm('Delete all notifications for this person? This cannot be undone.');">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-danger">Clear All for This Person</button>
                        </form>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Message</th>
                                        <th>Date</th>
                                        <th>Read</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($selectedUserNotifications as $n)
                                        <tr>
                                            <td>{{ $n->title }}</td>
                                            <td>{{ $n->message }}</td>
                                            <td>{{ \Carbon\Carbon::parse($n->created_at)->format('d M Y, h:i A') }}</td>
                                            <td>{{ $n->is_read ? 'Yes' : 'No' }}</td>
                                            <td>
                                                <form method="post" action="{{ route('settings.notification-settings.delete', $n->id) }}"
                                                      onsubmit="return confirm('Delete this notification?');">
                                                    @csrf
                                                    <input type="hidden" name="user_id" value="{{ $selectedUserId }}">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                @endif
            </div>
        </div>

        <div class="card ot-card">
            <div class="card-header">
                <h4>{{ @$pt  }}</h4>
            </div>
            <div class="card-body">

                <div class="table-responsive">
                    <table class="table table-bordered role-table">
                        <thead class="thead">
                            <tr>
                                <th>{{___('settings.event')}}</th>
                                <th>{{___('settings.host')}}</th>
                                <th>{{___('settings.reciever')}}</th>
                            </tr>
                        </thead>
                        <tbody class="tbody">
                            @foreach ($notificationSettings as $data)

                                <input type="hidden" id="dataId" value="{{ $data->id }}">
                                <tr>
                                    <td>
                                        <input type="hidden" name="event" value="1">
                                        {{ str_replace('_', ' ', $data->event) }}
                                    </td>
                                    <td>
                                        @foreach ($data->host as $key => $destination)
                                            <div class="col-lg-12 d-flex align-items-center">
                                                <input type="checkbox"
                                                    id="destination{{ $loop->index }}{{ $data->id }}"
                                                    class="form-check-input destinationCheckbox"
                                                    {{ $destination == 1 ? 'checked' : '' }} value="{{ $key }}"
                                                    name="destination{{ $loop->index }}{{ $data->id }}"
                                                    data-id="{{ $data->id }}">
                                                <label class="form-check-label ps-2 pe-5"
                                                    for="destination{{ $loop->index }}{{ $data->id }}">{{ Str::ucfirst($key) }}</label>
                                            </div>
                                        @endforeach
                                    </td>
                                    <td>
                                        <div class="d-flex recipientCards p-2 gap-4">
                                            @foreach ($data->reciever as $key => $recipient)
                                                <div class="white-box w-100">
                                                    <div
                                                        class="d-flex align-items-center justify-content-between">
                                                        <div class="d-flex align-items-center">
                                                            <input type="checkbox"
                                                            id="recipient{{ $loop->index }}{{ $data->id }}"
                                                            class="form-check-input recipientCheckbox"
                                                            {{ $recipient == 1 ? 'checked' : '' }}
                                                            data-id="{{ $data->id }}"
                                                            value="{{ $key }}"
                                                            name="recipient{{ $loop->index }}{{ $data->id }}">
                                                        <label class="form-check-label ps-2 pe-5"
                                                            for="recipient{{ $loop->index }}{{ $data->id }}"><b>{{ $key }}</b></label>
                                                        </div>
                                                        <a class="btn btn-md ot-btn-primary modalLink"
                                                            title="{{ str_replace('_', ' ', $data->event) }}[{{ $key }}]"
                                                            data-modal-size="modal-lg"
                                                            href="{{ route('settings.notification_event_modal', [$data->id, $key]) }}"><i class="fa-solid fa-pen-to-square"></i></a>
                                                    </div>
                                                    <p class="recipientCard">
                                                        @isset($data->shortcode)
                                                            @foreach ($data->shortcode as $role => $short)
                                                                @if ($key == $role)
                                                                    {{$short}}
                                                                @endif
                                                            @endforeach
                                                        @endisset
                                                    </p>
                                                </div>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>

    <!--  Start Modal Area -->
    <div class="has-modal modal fade" id="showDetaildModal">
        <div class="modal-dialog modal-dialog-centered modal-lg" id="modalSize">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header">
                    <h4 class="modal-title" id="showDetaildModalTile"></h4>
                    <button type="button" class="close icons" data-bs-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body" id="showDetaildModalBody">

                </div>
            </div>
        </div>
    </div>


@endsection

@push('script')
    <script>
        $(document).on('click', '.destinationCheckbox', function(e) {
            let id = $(this).data('id');
            let destination = $(this).val();
            let type = 'destination';
            if ($(this).is(':checked')) {
                var status = 1;
            } else {
                var status = 0;
            }
            let formData = {
                id: id,
                host: destination,
                status: status,
                type: type,
            }

            statusUpdate(formData);
        });
        $(document).on('click', '.recipientCheckbox', function(e) {
            let id = $(this).data('id');
            let recipient = $(this).val();
            let type = 'recipient-status';
            if ($(this).is(':checked')) {
                var status = 1;
            } else {
                var status = 0;
            }
            let formData = {
                id: id,
                reciever: recipient,
                status: status,
                type: type,
            }
            statusUpdate(formData);
        });
        $(document).on('click', '.updateNotificationModal', function(e) {
            let id = $('#id').val();
            let key = $('#key').val();
            let email_body = $('#email_body').val();
            let app_body = $('#app_body').val();
            let sms_body = $('#sms_body').val();
            let web_body = $('#web_body').val();
            let subject = $('#subject').val();
            let type = 'recipient';
            let formData = {
                id: id,
                key: key,
                subject: subject,
                email_body: email_body,
                app_body: app_body,
                sms_body: sms_body,
                web_body: web_body,
                status: status,
                type: type,
            }
            statusUpdate(formData);
                $('.modal').modal('hide');
        })

        function statusUpdate(formData) {
            $.ajax({
                type: "POST",
                data: formData,
                headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                url:  "{{route('settings.notification-settings.update')}}",
                dataType: "json",
                success: function(response) {
                    toastr.success('Operation Successfully', 'Success');
                },
                error: function(error) {
                    toastr.error('Operation failed', 'Error');
                }
            });
        }

        $(document).ready(function() {
        $("body").on("click", ".modalLink", function(e) {
            e.preventDefault();
            $(".modal-backdrop").show();
            $("#showDetaildModal").show();
            $("div.modal-dialog").removeClass("modal-md");
            $("div.modal-dialog").removeClass("modal-lg");
            $("div.modal-dialog").removeClass("modal-bg");
            var modal_size = $(this).attr("data-modal-size");
            if (
                modal_size !== "" &&
                typeof modal_size !== typeof undefined &&
                modal_size !== false
            ) {
                $("#modalSize").addClass(modal_size);
            } else {
                $("#modalSize").addClass("modal-md");
            }
            var title = $(this).attr("title");
            $("#showDetaildModalTile").text(title);
            var data_title = $(this).attr("data-original-title");
            $("#showDetaildModalTile").text(data_title);
            $("#showDetaildModal").modal("show");
            $("div.ajaxLoader").show();
            $.ajax({
                type: "GET",
                url: $(this).attr("href"),
                success: function(data) {
                    $("#showDetaildModalBody").html(data);
                    $("#showDetaildModal").modal("show");
                },
            });
        });
    });

    </script>
@endpush
