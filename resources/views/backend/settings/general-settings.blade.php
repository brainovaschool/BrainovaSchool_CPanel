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
                <h4>{{ ___('settings.general_settings') }} @if (app_translate())
                        <a href="{{ route('settings.general-settings.translate') }}"
                            class="float-end btn btn-sm ot-btn-primary">{{ ___('common.translate') }}</a>
                    @endif
                </h4>
            </div>
            <div class="card-body">
                <form action="{{ route('settings.general-settings.update') }}" enctype="multipart/form-data" method="post"
                    id="visitForm">
                    @csrf
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="row mb-3">
                                <!--Application Name Start -->
                                <div class="col-12 col-md-6 col-xl-6 col-lg-6 mb-3 ">
                                    <label for="inputname" class="form-label">{{ ___('settings.application_name') }} <span
                                            class="fillable">*</span></label>
                                    <input type="text" name="application_name"
                                        class="form-control ot-input @error('application_name') is-invalid @enderror"
                                        value="{{ Setting('application_name') }}"
                                        placeholder="{{ ___('settings.enter_you_application_name') }}">
                                    @error('application_name')
                                        <div id="validationServer04Feedback" class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                                <!--Application Name End -->
                                <!--Footer Text Start -->
                                <div class="col-12 col-md-6 col-xl-6 col-lg-6 mb-3 ">
                                    <label for="inputname" class="form-label">{{ ___('settings.footer_text') }} <span
                                            class="fillable">*</span></label>
                                    <input type="text" name="footer_text"
                                        class="form-control ot-input @error('footer_text') is-invalid @enderror"
                                        value="{{ Setting('footer_text') }}"
                                        placeholder="{{ ___('settings.enter_your_footer_text') }}">
                                    @error('footer_text')
                                        <div id="validationServer04Feedback" class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-12 col-md-6 col-xl-6 col-lg-6 mb-3">
                                    <label class="form-label" for="light_logo">{{ ___('settings.light_logo') }}
                                        {{ ___('common.(155 x 40 px)') }}</label>
                                    <br>
                                    <div class="d-flex justify-content-center align-items-center">
                                        <img class="img-thumbnail mb-10 ot-input full-logo setting-image"
                                            src="{{ @globalAsset(setting('light_logo'), '154X38.webp') }}"
                                            alt="{{ __('light logo') }}">
                                    </div>

                                    {{-- File Uplode --}}
                                    <div class="ot_fileUploader left-side mb-3">
                                        <input class="form-control" type="text"
                                            placeholder="{{ ___('settings.browse_light_logo') }}" readonly=""
                                            id="placeholder">
                                        <button class="primary-btn-small-input" type="button">
                                            <label class="btn btn-lg ot-btn-primary"
                                                for="fileBrouse">{{ ___('common.browse') }}</label>
                                            <input type="file" class="d-none form-control" name="light_logo"
                                                id="fileBrouse" accept="image/*">
                                        </button>
                                    </div>
                                </div>
                                <!--White Logo End -->
                                <!--Black Logo Start -->
                                <div class="col-12 col-md-6 col-xl-6 col-lg-6 ">
                                    <label class="form-label" for="dark_logo">{{ ___('settings.dark_logo') }}
                                        {{ ___('common.(155 x 40 px)') }}</label>
                                    <br>
                                    <div class="d-flex justify-content-center align-items-center">
                                        <img class="img-thumbnail mb-10 ot-input full-logo setting-image"
                                            src="{{ @globalAsset(setting('dark_logo'), '154X38.webp') }}"
                                            alt="{{ __('dark logo') }}">
                                    </div>
                                    {{-- File Uplode --}}
                                    <div class="ot_fileUploader left-side mb-3">
                                        <input class="form-control" type="text"
                                            placeholder="{{ ___('settings.browse_dark_logo') }}" readonly=""
                                            id="placeholder2">
                                        <button class="primary-btn-small-input" type="button">
                                            <label class="btn btn-lg ot-btn-primary"
                                                for="fileBrouse2">{{ ___('common.browse') }}</label>
                                            <input type="file" class="d-none form-control" name="dark_logo"
                                                id="fileBrouse2" accept="image/*">
                                        </button>
                                    </div>
                                </div>
                                <!--Black Logo End -->
                                <div class="col-12">
                                    <div class="">
                                        <div class="row align-items-end">
                                            <!--Favicon Start -->
                                            <div class="col-md-6 favicon-uploader">
                                                <div class="d-flex flex-column">
                                                    <label class="form-label" for="favicon">{{ ___('settings.favicon') }}
                                                        {{ ___('common.(40 x 40 px)') }}</label>
                                                    <br>
                                                    <div class="d-flex align-items-center gap-3 justify-content-center">
                                                        <img class="img-thumbnail mb-10 ot-input full-logo setting-image"
                                                            src="{{ @globalAsset(setting('favicon'), '40X40.webp') }}"
                                                            alt="{{ __('favicon') }}">
                                                    </div>
                                                    <div class="ot_fileUploader left-side mb-3">
                                                        <input class="form-control" type="text"
                                                            placeholder="{{ ___('settings.browse_favicon') }}"
                                                            readonly="" id="placeholder3">
                                                        <button class="primary-btn-small-input" type="button">
                                                            <label class="btn btn-lg ot-btn-primary"
                                                                for="fileBrouse3">{{ ___('common.browse') }}</label>
                                                            <input type="file" class="d-none form-control"
                                                                name="favicon" id="fileBrouse3" accept="image/*">
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            <!--Favicon End -->

                                            <!-- Country Start-->
                                            <div class="col-md-6 default-langauge mb-3">
                                                <div class="d-flex flex-column">
                                                    <label for="default langauge"
                                                        class="form-label">{{ ___('settings.Country') }} <span
                                                            class="fillable">*</span></label>
                                                    <select name="country" id="countryId"
                                                        class="form-select ot-input flag_icon_list @error('country') is-invalid @enderror">
                                                        @foreach (config('country') ?? [] as $country)
                                                            <option value="{{ $country }}"
                                                                {{ Setting('country') == $country ? 'selected' : '' }}>
                                                                {{ $country }}</option>
                                                        @endforeach
                                                        </option>

                                                    </select>
                                                </div>
                                            </div>
                                            <!-- Country End-->


                                            <!-- Timezone Start-->
                                            <div class="col-md-6 default-langauge mb-3">
                                                <div class="d-flex flex-column">
                                                    <label for="default langauge"
                                                        class="form-label">{{ ___('settings.Timezone') }} <span
                                                            class="fillable">*</span></label>
                                                    <select name="timezone" id="timezone"
                                                        class="form-select ot-input flag_icon_list @error('country') is-invalid @enderror">
                                                        @foreach ($data['timezones'] ?? [] as $timezone)
                                                            <option value="{{ $timezone }}"
                                                                {{ Setting('timezone') == $timezone ? 'selected' : '' }}>
                                                                {{ $timezone }}</option>
                                                        @endforeach
                                                        </option>

                                                    </select>
                                                </div>
                                            </div>
                                            <!-- Timezone End-->


                                            <!-- Default Langauge Start-->
                                            <div class="col-md-6 default-langauge mb-3">
                                                <div class="d-flex flex-column">
                                                    <label for="default langauge"
                                                        class="form-label">{{ ___('settings.default_langauge') }} <span
                                                            class="fillable">*</span></label>
                                                    <select name="default_langauge" id="defaultlangaugeId"
                                                        class="form-select ot-input @error('default_langauge') is-invalid @enderror">

                                                        @foreach ($data['languages'] as $row)
                                                            <option value="{{ $row->code }}"
                                                                data-icon="{{ $row->icon_class }}"
                                                                {{ Setting('default_langauge') == $row->code ? 'selected' : '' }}>
                                                                {{ $row->name }}</option>
                                                        @endforeach
                                                        </option>

                                                    </select>
                                                </div>
                                            </div>
                                            <!-- Default Langauge End-->

                                            <!-- Currency Start-->
                                            <div class="col-md-6 default-langauge mb-3">
                                                <div class="d-flex flex-column">
                                                    <label for="currency_code" class="form-label">
                                                        {{ ___('settings.Currency') }}
                                                        <span class="fillable">*</span>
                                                    </label>
                                                    <select name="currency_code" id="currency_code"
                                                        class="form-select ot-input flag_icon_list @error('currency_code') is-invalid @enderror">
                                                        @foreach ($data['currencies'] as $currency)
                                                            <option value="{{ $currency->code }}"
                                                                {{ Setting('currency_code') == $currency->code ? 'selected' : '' }}>
                                                                {{ $currency->code }} &mdash; {{ $currency->symbol }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <!-- Currency End-->

                                            <!-- Session Start-->
                                            <div class="col-md-6 default-langauge mb-3">
                                                <div class="d-flex flex-column">
                                                    <label for="session"
                                                        class="form-label">{{ ___('settings.Session') }} <span
                                                            class="fillable">*</span></label>
                                                    <select name="session" id="session"
                                                        class="form-select ot-input flag_icon_list @error('session') is-invalid @enderror">
                                                        @foreach ($data['sessions'] as $row)
                                                            <option {{ setting('session') == $row->id ? 'selected' : '' }}
                                                                value="{{ $row->id }}">{{ $row->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <!-- Session End-->

                                            <!--Address Start -->
                                            <div class="col-12">
                                                <label for="inputname" class="form-label">{{ ___('common.address') }}
                                                    <span class="fillable">*</span></label>
                                                <input type="text" name="address"
                                                    class="form-control ot-input @error('address') is-invalid @enderror"
                                                    value="{{ Setting('address') }}"
                                                    placeholder="{{ ___('settings.enter_you_address') }}">
                                                @error('address')
                                                    <div id="validationServer04Feedback" class="invalid-feedback">
                                                        {{ $message }}
                                                    </div>
                                                @enderror
                                            </div>
                                            <!--Address End -->

                                            <!--Map Start -->
                                            <div class="col-12 mt-4">
                                                <label for="inputname" class="form-label">{{ ___('common.Map') }} <span
                                                        class="fillable">*</span> <code> <a
                                                            href="https://www.google.com/maps"
                                                            translate="_blacnk">{{ ___('settings.click_here') }}</a></code></label>
                                                <textarea name="map_key" class="form-control ot-input @error('map_key') is-invalid @enderror"
                                                    placeholder="{{ ___('settings.enter_google_map_embaded_key') }}">{{ Setting('map_key') }}</textarea>
                                                @error('map_key')
                                                    <div id="validationServer04Feedback" class="invalid-feedback">
                                                        {{ $message }}
                                                    </div>
                                                @enderror
                                            </div>
                                            <!--Map End -->

                                            <!--Phone Start -->
                                            <div class="col-12 col-md-6 col-xl-6 col-lg-6 mb-3 ">
                                                <label for="inputname" class="form-label">{{ ___('common.phone') }} <span
                                                        class="fillable">*</span></label>
                                                <input type="text" name="phone"
                                                    class="form-control ot-input @error('phone') is-invalid @enderror"
                                                    value="{{ Setting('phone') }}"
                                                    placeholder="{{ ___('settings.enter_you_phone') }}">
                                                @error('phone')
                                                    <div id="validationServer04Feedback" class="invalid-feedback">
                                                        {{ $message }}
                                                    </div>
                                                @enderror
                                            </div>
                                            <!--Phone End -->

                                            <!--Email Start -->
                                            <div class="col-12 col-md-6 col-xl-6 col-lg-6 mb-3 ">
                                                <label for="inputname" class="form-label">{{ ___('common.email') }} <span
                                                        class="fillable">*</span></label>
                                                <input type="email" name="email"
                                                    class="form-control ot-input @error('email') is-invalid @enderror"
                                                    value="{{ Setting('email') }}"
                                                    placeholder="{{ ___('settings.enter_you_email') }}">
                                                @error('email')
                                                    <div id="validationServer04Feedback" class="invalid-feedback">
                                                        {{ $message }}
                                                    </div>
                                                @enderror
                                            </div>
                                            <!--Email End -->

                                            <!--Contact page mode Start -->
                                            <div class="col-12 col-md-6 col-xl-6 col-lg-6 mb-3 ">
                                                <label for="inputname" class="form-label">{{ ___('settings.contact_page_mode') }}</label>
                                                <select name="contact_mode" class="nice-select niceSelect bordered_style wide @error('contact_mode') is-invalid @enderror">
                                                    <option value="form" {{ Setting('contact_mode') != 'whatsapp' ? 'selected' : '' }}>{{ ___('settings.website_form') }}</option>
                                                    <option value="whatsapp" {{ Setting('contact_mode') == 'whatsapp' ? 'selected' : '' }}>{{ ___('settings.whatsapp_chat') }}</option>
                                                </select>
                                                <small class="text-secondary">{{ ___('settings.contact_page_mode_help') }}</small>
                                                @error('contact_mode')
                                                    <div id="validationServer04Feedback" class="invalid-feedback">
                                                        {{ $message }}
                                                    </div>
                                                @enderror
                                            </div>
                                            <!--Contact page mode End -->

                                            <!--WhatsApp number Start -->
                                            <div class="col-12 col-md-6 col-xl-6 col-lg-6 mb-3 ">
                                                <label for="inputname" class="form-label">{{ ___('settings.whatsapp_number') }}</label>
                                                <input type="text" name="whatsapp_number"
                                                    class="form-control ot-input @error('whatsapp_number') is-invalid @enderror"
                                                    value="{{ Setting('whatsapp_number') }}"
                                                    placeholder="+923001234567">
                                                <small class="text-secondary">{{ ___('settings.whatsapp_number_help') }}</small>
                                                @error('whatsapp_number')
                                                    <div id="validationServer04Feedback" class="invalid-feedback">
                                                        {{ $message }}
                                                    </div>
                                                @enderror
                                            </div>
                                            <!--WhatsApp number End -->

                                            <!--WhatsApp default message Start -->
                                            <div class="col-12 mb-3">
                                                <label for="inputname" class="form-label">{{ ___('settings.whatsapp_default_message') }}</label>
                                                <input type="text" name="whatsapp_default_message"
                                                    class="form-control ot-input @error('whatsapp_default_message') is-invalid @enderror"
                                                    value="{{ Setting('whatsapp_default_message') }}"
                                                    placeholder="{{ ___('settings.whatsapp_default_message_placeholder') }}">
                                                @error('whatsapp_default_message')
                                                    <div id="validationServer04Feedback" class="invalid-feedback">
                                                        {{ $message }}
                                                    </div>
                                                @enderror
                                            </div>
                                            <!--WhatsApp default message End -->

                                            <!--School about Start -->
                                            <div class="col-12">
                                                <label for="inputname"
                                                    class="form-label">{{ ___('settings.school_about') }} <span
                                                        class="fillable">*</span></label>
                                                <textarea name="school_about" class="m-0 form-control ot-input @error('school_about') is-invalid @enderror"
                                                    cols="30" rows="10">{{ Setting('school_about') }}"</textarea>
                                                @error('school_about')
                                                    <div id="validationServer04Feedback" class="invalid-feedback">
                                                        {{ $message }}
                                                    </div>
                                                @enderror
                                            </div>
                                            <!--School about End -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12 mt-3">
                            <!-- Update Button Start-->
                            <div class="text-end">
                                @if (hasPermission('general_settings_update'))
                                    <button class="btn btn-lg ot-btn-primary"><span><i class="fa-solid fa-save"></i>
                                        </span>{{ ___('common.update') }}</button>
                                @endif
                            </div>
                            <!-- Update Button End-->
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
