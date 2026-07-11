@extends('frontend.master')
@section('title')
    {{ ___('frontend.contact_us') }}
@endsection

@section('main')


<!-- bradcam::start  -->
<div class="breadcrumb_area" data-background="{{ @globalAsset(@$sections['study_at']->upload->path, '1920X700.webp') }}">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-xl-5">
                <div class="breadcam_wrap text-center">
                    <h3>{{ ___('frontend.contact_us') }}</h3>
                    <div class="custom_breadcam">
                        <a href="{{url('/')}}" class="breadcrumb-item">{{ ___('frontend.home') }}</a>
                        <a href="#" class="breadcrumb-item">{{ ___('frontend.contact_us') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- bradcam::end  -->

<!-- CONTACT::START  -->
<div class="contact_section section_padding2  pb-0">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-7 col-md-8">
                <div class="section__title text-center mb_50">
                    <h3>{!! @$sections['contact_information']->defaultTranslate->name !!}</h3>
                </div>
            </div>
            <div class="col-12">
                <!-- find_content_widget_wrapper  -->
                <div class="find_content_widget_wrapper mb_20">


                    @foreach ($data['contactInfo'] as $item)
                        <div class="find_content_widget d-flex flex-column align-items-center">
                            <div class="icon">
                                <img src="{{ @globalAsset(@$item->upload->path,'65X90.webp') }}" alt="Icon">
                            </div>
                            <h3>{{ @$item->defaultTranslate->name }}</h3>
                            <p>{{ str_ireplace('G-9, Islamabad', 'Islamabad', @$item->defaultTranslate->address) }}</p>
                        </div>
                    @endforeach


                </div>
            </div>
            <div class="col-12">
                <div class="row justify-content-between">
                    <div class="col-lg-6">
                        <div class="contact_map">
                            <iframe src="https://www.google.com/maps/embed?pb={{setting('map_key')}}" width="100%" height="750" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="contact_form_box mb_30">
                            <div class="section__title mb_50">
                                <h3 class="mb-0 text-white">{{ ___('frontend.leave_a_message') }}</h3>
                            </div>
                            <form class="form-area contact-form" id="myForm" method="post">
                                <div class="row">
                                    <div class="col-xl-6">
                                        <label class="primary_label">{{ ___('frontend.Name') }}</label>
                                        <input name="name" placeholder="{{ ___('frontend.enter_name') }}" onfocus="this.placeholder = ''" onblur="this.placeholder = 'Enter Name'" class="name primary_input mb_30" required="" type="text">
                                    </div>
                                    <div class="col-xl-6">
                                        <label class="primary_label">{{ ___('frontend.phone_no') }}</label>
                                        <input name="phone" placeholder="{{ ___('frontend.phone_no') }}" onfocus="this.placeholder = ''" onblur="this.placeholder = 'Phone no'" class="phone primary_input mb_30" required="" type="text">
                                    </div>
                                    <div class="col-xl-6">
                                        <label class="primary_label">{{ ___('frontend.email_address') }}</label>
                                        <input name="email" placeholder="{{ ___('frontend.type_email_address') }}" pattern="[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{1,63}$" onfocus="this.placeholder = ''" onblur="this.placeholder = 'Type e-mail address'" class="email primary_input mb_30" required="" type="email">

                                    </div>
                                    <div class="col-xl-6">
                                        <label class="primary_label">{{ ___('frontend.Subject') }}</label>
                                        <input name="subject" placeholder="{{ ___('frontend.Subject') }}" onfocus="this.placeholder = ''" onblur="this.placeholder = 'Subject'" class="subject primary_input mb_30" required="" type="text">
                                    </div>
                                    <div class="col-xl-12">
                                        <label class="primary_label">{{ ___('frontend.Message') }}</label>
                                        <textarea class="message primary_textarea mb_30" name="message" placeholder="{{ ___('frontend.write_your_message') }}" onfocus="this.placeholder = ''" onblur="this.placeholder = 'Write your message'" required=""></textarea>
                                    </div>
                                    <div class="col-xl-12 text-left">
                                        <button type="submit" class="theme_btn submit-btn text-center d-inline-flex gap_14 align-items-center m-0">{{ ___('frontend.send_message') }} <i class="fab fa-telegram-plane f_s_20"></i></button>
                                        {{-- mail-script.js --}}
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
<!-- CONTACT::END  -->

<!-- contact_department_area::start  -->
<div class="contact_department_area section_padding4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-xl-6">
                <div class="section__title mb_76 text-center">
                    <h3 class="text-capitalize">{{ @$sections['department_contact_information']->defaultTranslate->name }}</h3>
                    <p>{{ @$sections['department_contact_information']->defaultTranslate->description }}</p>
                </div>
            </div>
        </div>
        <div class="row">

            @foreach ($data['depContact'] as $item)
                <div class="col-xl-3 col-lg-6 col-md-6">
                    <div class="contact_department_box text-center mb_30">
                        <div class="icon">
                            <img src="{{ @globalAsset(@$item->upload->path, '340X340.webp') }}" alt="Image">
                        </div>
                        <h3>{{ @$item->defaultTranslate->name }}</h3>
                        <p>{{ @$item->defaultTranslate->phone }} <br>
                            {{ @$item->defaultTranslate->email }}</p>
                    </div>
                </div>
            @endforeach

        </div>
    </div>
</div>
<!-- contact_department_area::end  -->

@endsection
