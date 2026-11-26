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
                        <li class="breadcrumb-item">{{ $data['title'] }}</li>
                    </ol>
                </div>
            </div>
        </div>
        {{-- bradecrumb Area E n d --}}

        <div class="card ot-card">
            <div class="card-header">
                <h4>{{ ___('settings.sms_settings') }}</h4>
            </div>
            <div class="card-body">
                <form action="{{ route('settings.sms-setting.update') }}" enctype="multipart/form-data" method="post"
                    id="visitForm">
                    @csrf
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="row">
                                {{-- Recaptch SITEKEY start --}}
                                <div class="row mb-3">
                                    <div class="col-12 col-md-6 col-xl-6 col-lg-6 mb-3">
                                        <label for="inputname" class="form-label">{{ ___('settings.twilio_account_sid') }}
                                            <span class="fillable">*</span></label>
                                        <input type="text" name="twilio_account_sid"
                                            class="form-control ot-input @error('twilio_account_sid') is-invalid @enderror"
                                            value="{{ Setting('twilio_account_sid') }}" placeholder="{{ ___('settings.twilio_account_sid') }}">
                                        @error('twilio_account_sid')
                                            <div id="validationServer04Feedback" class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                    {{-- Recaptch SITEKEY start --}}

                                    {{-- Recaptch SECRET start --}}
                                    <div class="col-12 col-md-6 col-xl-6 col-lg-6 mb-3">
                                        <label for="inputname" class="form-label">{{ ___('settings.twilio_auth_token') }}
                                            <span class="fillable">*</span></label>
                                        <input type="text" name="twilio_auth_token"
                                            class="form-control ot-input @error('twilio_auth_token') is-invalid @enderror"
                                            value="{{ Setting('twilio_auth_token') }}" placeholder="{{ ___('settings.twilio_auth_token') }}">
                                        {{-- value="{{ $data['data']->where('name', 'recaptcha_secret')->pluck('value')->first() }} --}}
                                        @error('twilio_auth_token')
                                            <div id="validationServer04Feedback" class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                    {{-- Recaptch SECRET end --}}

                                    {{-- Recaptch SECRET start --}}
                                    <div class="col-12 col-md-6 col-xl-6 col-lg-6 mb-3">
                                        <label for="inputname" class="form-label">{{ ___('settings.twilio_phone_number') }}
                                            <span class="fillable">*</span></label>
                                        <input type="text" name="twilio_phone_number"
                                            class="form-control ot-input @error('twilio_phone_number') is-invalid @enderror"
                                            value="{{ Setting('twilio_phone_number') }}" placeholder="{{ ___('settings.twilio_phone_number') }}">
                                        {{-- value="{{ $data['data']->where('name', 'recaptcha_secret')->pluck('value')->first() }} --}}
                                        @error('twilio_phone_number')
                                            <div id="validationServer04Feedback" class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                    {{-- Recaptch SECRET end --}}


                                </div>
                            </div>

                            <div class="col-md-12 mt-3">
                                <div class="text-end">
                                    @if (hasPermission('recaptcha_settings_update'))
                                        <button class="btn btn-lg ot-btn-primary">
                                            <span>
                                                <i class="fa-solid fa-save"></i>
                                            </span>{{ ___('common.update') }}
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card ot-card mt-20">
            <div class="card-header">
                <h4>Send test WhatsApp message</h4>
            </div>
            <div class="card-body">
                <p class="text-secondary mb-3">Sends one real WhatsApp message through the Twilio number above, so you can tell right away whether it's approved for WhatsApp yet — without opening the Twilio console. If it fails, the error message below will say why (e.g. "channel not enabled" means this number can send SMS but is not yet an approved WhatsApp sender).</p>
                <form action="{{ route('settings.sms-setting.test-whatsapp') }}" method="post" class="d-flex align-items-center gap-2 flex-wrap">
                    @csrf
                    <input type="text" name="test_whatsapp_number" class="form-control" style="max-width:260px" placeholder="e.g. +923001234567" required>
                    <button class="btn ot-btn-primary">Send test message</button>
                </form>
            </div>
        </div>
    </div>
@endsection
