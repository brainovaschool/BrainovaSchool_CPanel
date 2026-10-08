<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>@yield('title')</title>
    <link rel="icon" type="image/x-icon" href="{{ @globalAsset(setting('favicon'), '40X40.webp') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <input type="hidden" name="url" id="url" value="{{ url('') }}">
    <!-- Delete alert text-->
    <input type="hidden" name="alert_title" id="alert_title" value="{{ ___('common.are_you_sure') }}">
    <input type="hidden" name="alert_subtitle" id="alert_subtitle"
        value="{{ ___('common.you_wont_be_able_to_revert_this') }}">
    <input type="hidden" name="alert_yes_btn" id="alert_yes_btn" value="{{ ___('common.yes_delete_it') }}">
    <input type="hidden" name="alert_cancel_btn" id="alert_cancel_btn" value="{{ ___('common.Cancel') }}">

    <meta name="keywords"
        content="admin, admin dashboard, admin template, backend, bootstrap, crm, laravel, laravel admin, web application">
    <meta name="description" content="{{ setting('application_name') }}">
    @if (findDirectionOfLang() == 'rtl')
        <link rel="stylesheet" href="{{ global_asset('backend') }}/assets/css/bootstrap.rtl.min.css">
    @else
        <link rel="stylesheet" href="{{ global_asset('backend') }}/assets/css/bootstrap.min.css">
    @endif

    <!-- metis menu for sidebar  -->
    <link rel="stylesheet" href="{{ global_asset('backend') }}/assets/css/metisMenu.min.css">
    {{-- Chart js --}}
    <link rel="stylesheet" href="{{ global_asset('backend') }}/assets/css/apexcharts.min.css">
    <!-- jvectormap css -->
    <link rel="stylesheet" href="{{ global_asset('backend/vendors/jvectormap/css/jquery-jvectormap-1.2.2.css') }}">
    {{-- All icon-fonts --}}
    <link rel="stylesheet" href="{{ global_asset('backend') }}/assets/css/icon-fonts.css">
    <!-- All Plugin  -->
    <link rel="stylesheet" href="{{ global_asset('backend') }}/assets/css/plugin.css">

    <link href="{{ global_asset('backend') }}/vendors/summernote/summernote-lite.min.css" rel="stylesheet">

    {{-- date range picker --}}
    <link rel="stylesheet" href="{{ global_asset('backend') }}/assets/css/daterangepicker.css">
    {{-- full calender --}}

    <!-- Custom CSS  start -->
    <link rel="stylesheet" href="{{ global_asset('backend') }}/assets/css/style.css">
    <link rel="stylesheet" href="{{ global_asset('backend') }}/assets/css/style2.css">
    <link rel="stylesheet" href="{{ global_asset('backend') }}/assets/css/custom.css">
    <link rel="stylesheet" href="{{ global_asset('backend') }}/assets/css/aurora-glass.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">


    @yield('css')
    @stack('css')

</head>

<body class="{{ findDirectionOfLang() }} default-theme" dir="{{ @findDirectionOfLang() }}">

    <div id="layout-wrapper">
        <!-- start header -->
        @include('backend.partials.header')
        <!-- end header -->

        <!-- start sidebar -->
        @include('backend.partials.sidebar')
        <!-- end sidebar -->

        <main class="main-content ph-24 ph-lg-32 pt-100 mt-4">
            <!-- start main content -->
            @yield('content')
            <!-- end main content -->

            <!-- start footer -->
            @include('backend.partials.footer')
            <!-- end footer -->
        </main>
    </div>

    {{-- theme mode switch --}}
    <script src="{{ global_asset('backend') }}/assets/js/theme.js"></script>
    <script src="{{ global_asset('backend') }}/assets/js/popper.min.js"></script>
    <script src="{{ global_asset('backend') }}/assets/js/bootstrap.min.js"></script>
    <script src="{{ global_asset('backend') }}/assets/js/jquery-3.6.0.min.js"></script>

    @if (findDirectionOfLang() == 'rtl')
        <script src="{{ global_asset('backend') }}/assets/js/__dir.js"></script>
    @endif

    <script src="{{ global_asset('backend') }}/assets/js/semantic.min.js"></script>
    <!-- Metis menu for sidebar  -->
    <script src="{{ global_asset('backend') }}/assets/js/metisMenu.min.js"></script>
    <!-- jvectormap js -->
    <script src="{{ global_asset('backend/vendors/jvectormap/js/jquery-jvectormap-1.2.2.min.js') }}"></script>
    <script src="{{ global_asset('backend/vendors/jvectormap/js/jquery-jvectormap-us-merc-en.js') }}"></script>
    {{-- Chart --}}
    <script src="{{ global_asset('backend') }}/vendors/apexchart/js/apexcharts.min.js"></script>
    <script src="{{ global_asset('backend') }}/vendors/chartjs/js/chart.min.js"></script>
    <script src="{{ global_asset('backend') }}/assets/js/ot-charts.js"></script>
    <script src="{{ global_asset('backend') }}/assets/js/datepicker.min.js"></script>
    {{-- All Plugin js --}}
    <script src="{{ global_asset('backend') }}/assets/js/plugin.js"></script>
    <!-- Vendor JS end  -->
    <script src="{{ global_asset('backend') }}/assets/js/main.js"></script>
    {{-- Custom Js --}}
    <script src="{{ global_asset('backend') }}/assets/js/fees-master.js"></script>
    <script src="{{ global_asset('backend') }}/assets/js/custom.js"></script>


    <script src="{{ global_asset('backend') }}/vendors/summernote/summernote-lite.min.js"></script>
    <script src="{{ global_asset('backend') }}/assets/js/daterangepicker.min.js"></script>
    <script src="{{ global_asset('backend') }}/assets/js/fullcalendar.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>



    {{-- alert message --}}
    @include('backend.partials.alert-message')
    {{-- delete method --}}
    @stack('script')

    @include('pushnotification::script')

    @if (hasModule('MultiBranch'))
        <script>
            $(document).ready(function() {
                $('#branchId').on('change', function() {
                    let selectedValue = $(this).val();

                    if (selectedValue) {
                        $.ajax({
                            url: '{{ route('switch-branch') }}',
                            type: 'GET',
                            data: {
                                branch_id: selectedValue
                            },
                            success: function(response) {
                                window.location.reload();
                            },
                            error: function(xhr, status, error) {
                                window.location.reload();
                            }
                        });
                    } else {
                        $('#output').text('Please select a valid option.');
                    }
                });
            });
        </script>
    @endif

</body>

</html>
