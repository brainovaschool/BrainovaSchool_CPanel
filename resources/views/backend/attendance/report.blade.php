@extends('backend.master')

@section('title')
    {{ ___('common.Online Class Routine 2023') }}
@endsection

<style>
    body {
        font-family: 'Poppins', sans-serif;
        font-size: 14px;
        margin: 0;
        padding: 0;
        -webkit-print-color-adjust: exact !important;
    }

    table {
        border-collapse: collapse;
    }

    h1,
    h2,
    h3,
    h4,
    h5,
    h6 {
        margin: 0;
        color: #000;
    }

    .routine_wrapper {
        max-width: 100%;
        margin: auto;
        background: #fff;
        padding: 20px;
        border-radius: 5px;
    }

    .table {
        width: 100%;
        margin-bottom: 1rem;
        color: #212529;
    }

    .border_none {
        border: 0px solid transparent;
        border-top: 0px solid transparent !important;
    }

    .routine_part_iner {
        background-color: #fff;
    }

    .routine_part_iner h4 {
        font-size: 30px;
        font-weight: 500;
        margin-bottom: 40px;

    }

    .routine_part_iner h3 {
        font-size: 25px;
        font-weight: 500;
        margin-bottom: 5px;

    }

    .table_border thead {
        background-color: #F6F8FA;
    }

    .table td,
    .table th {
        padding: 0px 0;
        vertical-align: top;
        border-top: 0 solid transparent;
        color: #000;
    }

    .table_border tr {
        border-bottom: 1px solid #000 !important;
    }

    th p span,
    td p span {
        color: #212E40;
    }

    .table th {
        color: #000;
        font-weight: 300;
        border-bottom: 1px solid #000 !important;
        background-color: #fff;
    }

    p {
        font-size: 14px;
        color: #000;
        font-weight: 400;
    }

    h5 {
        font-size: 12px;
        font-weight: 500;
    }

    h6 {
        font-size: 10px;
        font-weight: 300;
    }

    .mt_40 {
        margin-top: 40px;
    }

    .table_style th,
    .table_style td {
        padding: 20px;
    }

    .routine_info_table td {
        font-size: 10px;
        padding: 0px;
    }

    .routine_info_table td h6 {
        color: #6D6D6D;
        font-weight: 400;
    }

    .text_right {
        text-align: right;
    }

    .virtical_middle {
        vertical-align: middle !important;
    }

    .border_bottom {
        border-bottom: 1px solid #000;
    }

    .line_grid {
        display: grid;
        grid-template-columns: 100px auto;
        grid-gap: 10px;
    }

    .line_grid span {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    p {
        margin: 0;
        color: #000;
    }

    .font_18 {
        font-size: 18px;
    }

    .mb-0 {
        margin-bottom: 0;
    }

    .mb_30 {
        margin-bottom: 30px !important;
    }

    .mb_40 {
        margin-bottom: 40px !important;
    }

    .mb_10 {
        margin-bottom: 10px !important;
    }

    .mb_20 {
        margin-bottom: 20px !important;
    }

    .bold_text {
        font-weight: 600;
    }

    .border_table {
        /* border: 1px solid #000; */
    }

    .title_header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin: 40px 0 15px 0;
    }

    .border_table tr:nth-of-type(n) {
        border: 1px solid #000;
    }

    .border_table tfoot tr:first-of-type {
        border: 0;
    }

    .border_table tfoot tr:first-of-type td {
        border: 0;
    }

    .border_table tbody tr:nth-of-type(odd) {
        background: #F0F3F5;
    }

    .routine_header {}

    .routine_header h3 {
        font-size: 24px;
        font-weight: 500;
    }

    .routine_header p {
        font-size: 14px;
        font-weight: 400;
        margin-bottom: 15px !important;
    }

    /* border_table  */
    .border_table {}

    .border_table thead {}

    .border_table thead tr th {
        padding: 5px;
        border-right: 1px solid #EAEAEA;
        border-color: #EAEAEA !important;
        font-weight: 700;
        font-size: 16px;
        color: #1A1A21;
        padding: 19.75px 16px;
        text-align: left;
        white-space: nowrap;
        vertical-align: middle;
        border-top: 1px solid;
    }

    .border_table tbody tr td,
    .border_table tfoot tr td {
        border-bottom: 1px solid #EAEAEA;
        text-align: left;
        padding: 30.5px 16px;
        border-right: 1px solid #EAEAEA;
        font-weight: 500;
        font-size: 18px;
        line-height: 22px;
        color: #424242;
        white-space: nowrap;
    }

    .border_table tr:nth-of-type(n) {
        border: 1px solid #EAEAEA;
        border-top: 0;
    }

    .border_table tbody tr th {
        background: #EAEAEA;
        border: 1px solid #FFFFFF;
        font-weight: 700;
        font-size: 18px;
        line-height: 30px;
        border-color: #fff !important;
        color: #424242;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 140px;
    }

    .classBox_wiz {
        min-height: 129px;
        vertical-align: middle;
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 15px;
    }

    .classBox_wiz h5 {
        font-weight: 600;
        font-size: 18px;
        line-height: 22px;
        color: #424242;
        margin: 0 0 5px 0;
        white-space: nowrap
    }

    .classBox_wiz p {
        font-weight: 500;
        font-size: 14px;
        line-height: 18px;
        color: #6B6B6B;
        margin: 0 0 5px 0;
    }

    .marked_bg {
        background: #6B6B6B !important;
        color: #FFFFFF !important;
    }

    .break_text {
        min-height: 129px;
        vertical-align: middle;
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 15px;
    }

    .break_text h5 {
        font-weight: 600;
        font-size: 18px;
        line-height: 22px;
        color: #424242;
        transform: rotate(-30deg);
    }

    .download_print_btns {
        display: flex;
        align-items: center;
        justify-content: center;
        grid-gap: 12px;
    }

    .student_info {
        display: flex;
        align-items: center;
        grid-gap: 16px;
    }

    .student_info img {
        border-radius: 4px;
        width: 24px;
        height: 24px;
    }

    .student_info h5 {
        font-weight: 500;
        font-size: 18px;
        line-height: 22px;
        color: #424242;
        margin: 0;
    }

    /* routine_wrapper_header  */
    .routine_wrapper_header {
        background: #392C7D;
        padding: 32px 36px;
        border-radius: 16px 16px 0 0;
        margin-bottom: 0;
        flex-wrap: wrap;
        grid-gap: 20px;
        margin-bottom: 20px;
        justify-content: center;
    }

    .routine_wrapper_header h3 {
        font-weight: 500;
        font-size: 36px;
        line-height: 40px;
        color: #FFFFFF;
        margin: 0;
    }

    .routine_wrapper_header h4 {
        font-size: 24px;
        color: #FF5170;
        font-weight: 500;
        margin: 7px 0 7px 0;
    }

    .routine_wrapper_header p {
        font-weight: 500;
        font-size: 18px;
        line-height: 30px;
        color: #FFFFFF;
        margin: 0;
    }

    .routine_wrapper_header_logo .header_logo {
        max-width: 193px;
    }

    .routine_wrapper_header {
        display: flex;
        align-items: center;
    }

    .routine_wrapper_header {
        padding: 30px 20px;
    }

    .routine_wrapper_header h3 {
        font-size: 24px;
    }

    .print_copyright_text {
        flex-direction: column;
        align-items: center;
        justify-content: center;
        grid-gap: 10px;
        margin: 20px 0;
        display: flex;
        align-items: center;
        padding-bottom: 10px;

    }

    .download_print_btns {
        display: flex;
        align-items: center;
        justify-content: start;
        grid-gap: 12px;
        background: #F3F3F3;
        padding: 20px;
        flex-wrap: wrap;
    }

    .vertical_seperator {
        border-right: 1px solid #FFFFFF;
        height: 93px;
        margin: 0 30px 0 40px;
    }

    .td-text-center {
        text-align: center !important;
    }

    @media (max-width: 768px) {
        .student_info_single {
            width: 100%;
        }

        .vertical_seperator {
            display: none !important;
        }

        .routine_wrapper {
            width: 100%;
        }

        .routine_wrapper_body {
            padding: 0;
        }

        .student_info_single {
            flex-wrap: wrap;
        }

        .download_print_btns {
            margin-top: 30px;
        }

        .routine_wrapper_header {
            padding: 20px 20px;
        }

        .routine_wrapper_header h3 {
            font-size: 24px;
        }
    }
</style>

@section('content')
    <div class="page-content">
        {{-- bradecrumb Area S t a r t --}}
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
        {{-- bradecrumb Area E n d --}}

        <div class="row">
            <div class="col-12">
                <div class="card ot-card mb-24 position-relative z_1">
                    <form action="{{ route('attendance.report-search') }}" enctype="multipart/form-data" method="post">
                        @csrf
                        <div class="card-header d-flex align-items-center gap-4 flex-wrap">
                            <h3 class="mb-0">{{ ___('common.Filtering') }}</h3>

                            <div
                                class="card_header_right d-flex align-items-end gap-3 flex-nowrap overflow-auto justify-content-end">
                                <!-- table_searchBox -->

                                <div class="single_large_selectBox">
                                    <select class="nice-select niceSelect bordered_style wide @error('department') is-invalid @enderror"
                                        name="department">
                                        <option value="">{{ ___('common.All') }}</option>
                                        @foreach ($data['departments'] as $item)
                                            <option
                                                {{ old('department', @$data['request']->department) == $item->id ? 'selected' : '' }}
                                                value="{{ $item->id }}">{{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('department')
                                        <div id="validationServer04Feedback" class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                                <div class="single_large_selectBox">
                                    <select id="getSections"
                                        class="class nice-select niceSelect bordered_style wide @error('class') is-invalid @enderror"
                                        name="class">
                                        <option value="">{{ ___('common.All') }}</option>
                                        @foreach ($data['classes'] as $item)
                                            <option
                                                {{ old('class', @$data['request']->class) == $item->id ? 'selected' : '' }}
                                                value="{{ $item->class->id }}">{{ $item->class->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('class')
                                        <div id="validationServer04Feedback" class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                                <div class="single_large_selectBox">
                                    <select id="getSubjects"
                                        class="sections section nice-select niceSelect bordered_style wide @error('section') is-invalid @enderror"
                                        name="section">
                                        <option value="">{{ ___('common.All') }}</option>
                                        @foreach ($data['sections'] as $item)
                                            <option
                                                {{ old('section', @$data['request']->section) == $item->section->id ? 'selected' : '' }}
                                                value="{{ $item->section->id }}">{{ $item->section->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('section')
                                        <div id="validationServer04Feedback" class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                                <div class="single_large_selectBox">
                                    <select
                                        class="subjects nice-select niceSelect bordered_style wide @error('subject') is-invalid @enderror"
                                        name="subject">
                                        <option value="">{{ ___('common.All') }}</option>
                                        @foreach ($data['subjects'] as $item)
                                            <option
                                                {{ old('subject', @$data['request']->subject) == $item->subject->id ? 'selected' : '' }}
                                                value="{{ $item->subject->id }}">{{ $item->subject->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('subject')
                                        <div id="validationServer04Feedback" class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                                <div class="single_large_selectBox">
                                    <select
                                        class="students nice-select niceSelect bordered_style wide @error('student') is-invalid @enderror"
                                        name="student">
                                        <option value="">{{ ___('common.All') }}</option>
                                        @foreach ($data['studentOptions'] as $item)
                                            <option
                                                {{ old('student', @$data['request']->student) == $item->student_id ? 'selected' : '' }}
                                                value="{{ $item->student_id }}">{{ $item->student->first_name }} {{ $item->student->last_name }}</option>
                                        @endforeach
                                    </select>
                                    @error('student')
                                        <div id="validationServer04Feedback" class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="single_large_selectBox">
                                    <label class="form-label d-block mb-1">{{ ___('common.Start Date') }}</label>
                                    <input value="{{ old('date_from', @$data['request']->date_from) }}" name="date_from"
                                        class="form-control ot-input @error('date_from') is-invalid @enderror" type="date">
                                    @error('date_from')
                                        <div id="validationServer04Feedback" class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="single_large_selectBox">
                                    <label class="form-label d-block mb-1">{{ ___('common.End Date') }}</label>
                                    <input value="{{ old('date_to', @$data['request']->date_to) }}" name="date_to"
                                        class="form-control ot-input @error('date_to') is-invalid @enderror" type="date">
                                    @error('date_to')
                                        <div id="validationServer04Feedback" class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <button class="btn btn-lg ot-btn-primary text-nowrap">
                                    {{ ___('common.Search') }}
                                </button>
                            </div>


                        </div>
                    </form>
                </div>
            </div>
        </div>


        @if (@$data['students'] != null)
            @if (count($data['students']) > 0)
                <div class="download_print_btns">
                    <button class="btn btn-lg ot-btn-primary" onclick="printDiv('printableArea')">
                        {{ ___('common.print_now') }}
                        <span><i class="fa-solid fa-print"></i></span>
                    </button>
                    <form action="{{ route('report-attendance.pdf-generate') }}" method="post">
                        @csrf
                        <input type="hidden" name="view" value="{{ $data['request']->view }}">
                        <input type="hidden" name="class" value="{{ $data['request']->class }}">
                        <input type="hidden" name="section" value="{{ $data['request']->section }}">
                        <input type="hidden" name="month" value="{{ $data['request']->month }}">
                        <input type="hidden" name="date" value="{{ $data['request']->date }}">
                        <input type="hidden" name="roll" value="{{ $data['request']->roll }}">
                        <button class="btn btn-lg ot-btn-primary mt-3" type="submit">{{ ___('common.pdf_download') }}
                            <span><i class="fa-brands fa-dochub"></i></span></button>
                    </form>
                    <form action="{{ route('attendance.export') }}" method="post">
                        @csrf
                        <input type="hidden" name="department" value="{{ $data['request']->department }}">
                        <input type="hidden" name="class" value="{{ $data['request']->class }}">
                        <input type="hidden" name="section" value="{{ $data['request']->section }}">
                        <input type="hidden" name="subject" value="{{ $data['request']->subject }}">
                        <input type="hidden" name="student" value="{{ $data['request']->student }}">
                        <input type="hidden" name="date_from" value="{{ $data['request']->date_from }}">
                        <input type="hidden" name="date_to" value="{{ $data['request']->date_to }}">
                        <button class="btn btn-lg ot-btn-primary mt-3" type="submit">{{ ___('common.export_excel') }}
                            <span><i class="fa-solid fa-file-excel"></i></span></button>
                    </form>
                </div>
            @endif
            <div class="routine_wrapper" id="printableArea">
                <div class="routine_wrapper_header">
                    <div class="routine_wrapper_header_logo">
                        <img class="header_logo" src="{{ @globalAsset(setting('light_logo'), '154X38.webp') }}"
                            alt="{{ __('light logo') }}">
                    </div>
                    <div class="vertical_seperator"></div>
                    <div class="routine_wrapper_header_content">
                        <h4>{{ setting('application_name') }}</h4>
                            <p>{{ setting('address') }}</p>
                    </div>
                </div>
                @if ((@$data['effective_view'] ?? @$data['request']->view) == '0')
                    <div class="text-end">
                        <strong>
                            <span class="text-success">{{ ___('attendance.Present') }} = {{ ___('common.P') }}</span>
                            <span class="text-warning">{{ ___('attendance.Late') }} = {{ ___('common.L') }}</span>
                            <span class="text-danger">{{ ___('attendance.Absent') }} = {{ ___('common.A') }}</span>
                            <span class="text-primary">{{ ___('attendance.half_day') }} = {{ ___('common.F') }}</span>
                            <span>{{ ___('attendance.Holiday') }} = {{ ___('common.H') }}</span>
                        </strong>
                    </div>
                @endif
                <div class="table-responsive">
                    @if ((@$data['effective_view'] ?? @$data['request']->view) == '0')
                        <table class="table">
                            <thead>
                                <tr>
                                    <th class="purchase">{{ ___('common.name') }}</th>
                                    <th class="purchase">{{ ___('student_info.roll_no') }}</th>
                                    @foreach ($data['days'] as $day => $date)
                                        <th>{{ ++$day }}</th>
                                    @endforeach
                                    <th class="purchase text-success">{{ ___('common.P') }}</th>
                                    <th class="purchase text-warning">{{ ___('common.L') }}</th>
                                    <th class="purchase text-danger">{{ ___('common.A') }}</th>
                                    <th class="purchase text-primary">{{ ___('common.F') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach (@$data['students'] as $item)
                                    <tr>
                                        <td>{{ @$item->student->first_name }} {{ @$item->student->last_name }}</td>
                                        <td>{{ @$item->roll }}</td>
                                        @php
                                            $p = 0;
                                            $l = 0;
                                            $a = 0;
                                            $f = 0;
                                        @endphp
                                        @foreach ($data['days'] as $day => $date)
                                            @php
                                                $i = ++$day;
                                            @endphp
                                            <td>
                                                @foreach ($data['attendances'] as $item2)
                                                    @if ($item->student_id == $item2->student_id && (int) substr($item2->date, -2) == $i)
                                                        @if (@$item2->attendance == App\Enums\AttendanceType::PRESENT)
                                                            <span class="text-success">{{ ___('common.P') }}</span>
                                                            @php
                                                                ++$p;
                                                            @endphp
                                                        @elseif(@$item2->attendance == App\Enums\AttendanceType::LATE)
                                                            <span class="text-warning">{{ ___('common.L') }}</span>
                                                            @php
                                                                ++$l;
                                                            @endphp
                                                        @elseif(@$item2->attendance == App\Enums\AttendanceType::ABSENT)
                                                            <span class="text-danger">{{ ___('common.A') }}</span>
                                                            @php
                                                                ++$a;
                                                            @endphp
                                                        @elseif(@$item2->attendance == App\Enums\AttendanceType::HALFDAY)
                                                            <span class="text-primary">{{ ___('common.F') }}</span>
                                                            @php
                                                                ++$f;
                                                            @endphp
                                                        @else
                                                            <span>{{ ___('common.H') }}</span>
                                                        @endif
                                                    @endif
                                                @endforeach
                                            </td>
                                        @endforeach
                                        <td><span class="text-success">{{ $p }}</span></td>
                                        <td><span class="text-warning">{{ $l }}</span></td>
                                        <td><span class="text-danger">{{ $a }}</span></td>
                                        <td><span class="text-primary">{{ $f }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <table class="table border_table mb_30">
                            <thead>
                                <tr>
                                    <th class="purchase">{{ ___('student_info.student_name') }}</th>
                                    <th class="purchase">{{ ___('student_info.roll_no') }}</th>
                                    <th class="purchase">{{ ___('academic.class') }} ({{ ___('academic.section') }})</th>
                                     <th class="purchase">{{ ___('attendance.Attendance') }}</th>
                                    <th class="purchase">{{ ___('common.date') }}</th>

                                    <th class="purchase">{{ ___('attendance.Note') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($data['students'] as $item)
                                    <tr id="document-file">
                                        <td>{{ @$item->student->first_name }} {{ @$item->student->last_name }}</td>
                                        <td>{{ @$item->roll }}</td>
                                        <td>{{ @$item->class->name }} ({{ @$item->section->name }})</td>
                                        <td>
                                            @if (@$item->attendance == App\Enums\AttendanceType::PRESENT)
                                                <span
                                                    class="badge-basic-success-text">{{ ___('attendance.Present') }}</span>
                                            @elseif(@$item->attendance == App\Enums\AttendanceType::LATE)
                                                <span class="badge-basic-warning-text">{{ ___('attendance.Late') }}</span>
                                            @elseif(@$item->attendance == App\Enums\AttendanceType::ABSENT)
                                                <span
                                                    class="badge-basic-danger-text">{{ ___('attendance.Absent') }}</span>
                                            @elseif(@$item->attendance == App\Enums\AttendanceType::HALFDAY)
                                                <span
                                                    class="badge-basic-primary-text">{{ ___('attendance.half_day') }}</span>
                                            @endif
                                        </td>
                                        <td>{{ dateFormat(@$item->date) }}</td>
                                        <td>
                                            {{ old('note', @$item->note) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="100%" class="text-center gray-color">
                                            <img src="{{ asset('images/no_data.svg') }}" alt=""
                                                class="mb-primary" width="100">
                                            <p class="mb-0 text-center">{{ ___('common.no_data_available') }}</p>
                                            <p class="mb-0 text-center text-secondary font-size-90">
                                                {{ ___('common.please_add_new_entity_regarding_this_table') }}</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    @endif
                </div>
                <!--  pagination start -->

            </div>
            <div class="ot-pagination pagination-content d-flex justify-content-end align-content-center py-3">
                <nav aria-label="Page navigation example">
                    <ul class="pagination justify-content-between">
                        {!! $data['students']->appends(\Request::capture()->except('page'))->links() !!}
                    </ul>
                </nav>
            </div>

        @endif
    </div>
@endsection
