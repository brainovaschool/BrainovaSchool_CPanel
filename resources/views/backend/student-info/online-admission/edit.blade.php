@extends('backend.master')
@section('title')
    {{ @$data['title'] }}
@endsection
@section('content')
    <div class="page-content">

        {{-- bradecrumb Area S t a r t --}}
        <div class="page-header">
            <div class="row">
                <div class="col-sm-6">
                    <h4 class="bradecrumb-title mb-1">{{ $data['title'] }}</h1>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('online-admissions.index') }}">{{ ___('student_info.online_admission') }}</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $data['title'] }}</li>
                    </ol>
                </div>
            </div>
        </div>
        {{-- bradecrumb Area E n d --}}

        <div class="card ot-card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                <h4 class="mb-0">{{ $data['title'] }}</h4>
                <div class="d-flex gap-2">
                    <a href="{{ route('online-admissions.index') }}" class="btn btn-lg ot-btn-primary">
                        <span><i class="fa-solid fa-arrow-left"></i></span> Back
                    </a>
                    @if (hasPermission('student_delete'))
                        <a href="javascript:void(0);"
                            onclick="delete_row('online-admissions/delete', {{ $data['student']->id }})"
                            class="btn btn-lg btn-danger">
                            <span><i class="fa-solid fa-trash-can"></i></span> {{ ___('common.delete') }}
                        </a>
                    @endif
                </div>
            </div>

            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Student Name</label>
                        <p class="form-control ot-input bg-light mb-0">
                            {{ trim(@$data['student']->first_name . ' ' . @$data['student']->last_name) ?: '—' }}
                        </p>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Student Age</label>
                        <p class="form-control ot-input bg-light mb-0">{{ @$data['student']->dob ?: '—' }}</p>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Student Class</label>
                        <p class="form-control ot-input bg-light mb-0">
                            {{ @$data['student']->nationality ?: (@$data['student']->class->name ?? '—') }}
                        </p>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Parent Name</label>
                        <p class="form-control ot-input bg-light mb-0">{{ @$data['student']->guardian_name ?: '—' }}</p>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Parent Email Address</label>
                        <p class="form-control ot-input bg-light mb-0">{{ @$data['student']->email ?: '—' }}</p>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Parent Phone Number</label>
                        <p class="form-control ot-input bg-light mb-0">{{ @$data['student']->guardian_phone ?: '—' }}</p>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Program</label>
                        <p class="form-control ot-input bg-light mb-0">{{ @$data['student']->spoken_lang_at_home ?: '—' }}</p>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Submitted At</label>
                        <p class="form-control ot-input bg-light mb-0">{{ dateFormat(@$data['student']->created_at) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    @include('backend.partials.delete-ajax')

    <script type="text/javascript">
        function delete_row(route, row_id, reload = true) {
            var table_row = '#row_' + row_id;
            var url = "{{ url('') }}" + '/' + route + '/' + row_id;
            Swal.fire({
                title: $('#alert_title').val(),
                text: $('#alert_subtitle').val(),
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: $('#alert_yes_btn').val(),
                cancelButtonText: $('#alert_cancel_btn').val(),
            }).then((confirmed) => {
                if (confirmed.isConfirmed) {
                    $.ajax({
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            id: row_id,
                            _method: 'DELETE'
                        },
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        url: url,
                    })
                    .done(function(response) {
                        Swal.fire({
                            icon: response[1],
                            title: response[2],
                            text: response[0],
                            showCloseButton: true,
                            confirmButtonText: response[3],
                        });
                        if (response[1] != 'error' && reload) {
                            setTimeout(function() {
                                window.location.href = "{{ route('online-admissions.index') }}";
                            }, 500);
                        }
                    });
                }
            });
        }
    </script>
@endpush
