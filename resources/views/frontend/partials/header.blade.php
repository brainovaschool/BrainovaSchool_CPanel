<!doctype html>
 <!-- class="rtl" dir="rtl" => will add on rtl mode  -->
<html class="no-js" class="{{ @findDirectionOfLang() }}" dir="{{ @findDirectionOfLang() }}" lang="zxx">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <link rel="shortcut icon" type="image/x-icon" href="{{ @globalAsset(setting('favicon'), '40X40.webp')}}">
    <title>Brainova School</title>
    <meta name="description" content="{{setting('application_name')}}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <input type="hidden" name="url" id="url" value="{{ url('') }}">

    @if(findDirectionOfLang()== "rtl")
    <link rel="stylesheet" href="{{global_asset('frontend')}}/css/bootstrap-rtl.min.css">
    @else
    <link rel="stylesheet" href="{{global_asset('frontend')}}/css/bootstrap.min.css">
    @endif
    <link rel="stylesheet" href="{{global_asset('frontend')}}/css/owl.carousel.min.css">
    <link rel="stylesheet" href="{{global_asset('frontend')}}/css/owl.carousel.min.css">
    <link rel="stylesheet" href="{{global_asset('frontend')}}/css/magnific-popup.css">
    <link rel="stylesheet" href="{{global_asset('frontend')}}/css/fontawesome.css ">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{global_asset('frontend')}}/css/themify-icons.css">
    <link rel="stylesheet" href="{{global_asset('frontend')}}/css/flaticon.css">
    <link rel="stylesheet" href="{{global_asset('frontend')}}/css/nice-select.css">
    <link rel="stylesheet" href="{{global_asset('frontend')}}/css/animate.min.css">
    <link rel="stylesheet" href="{{global_asset('frontend')}}/css/slicknav.css">
    <link rel="stylesheet" href="{{global_asset('frontend')}}/css/style.css">
    <link rel="stylesheet" href="{{global_asset('frontend')}}/css/custom.css?v={{ @filemtime(public_path('frontend/css/custom.css')) ?: 1 }}">
    <link rel="stylesheet" href="{{global_asset('frontend')}}/css/sweetalert2.min.css">

    @stack('css')

</head>

<body class="default-theme">
