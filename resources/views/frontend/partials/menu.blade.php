    <!-- HEADER::START -->
    <header>
        <div id="sticky-header" class="header_area">
            <div class="header_topbar_area">
                <div class="container">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="header_topbar_wrapper">
                                <div class="header_topbar_left">
                                    <p> <svg width="19" height="18" viewBox="0 0 19 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M18.5 13.42V16.956C18.5001 17.2092 18.4042 17.453 18.2316 17.6382C18.059 17.8234 17.8226 17.9363 17.57 17.954C17.133 17.984 16.776 18 16.5 18C7.663 18 0.5 10.837 0.5 2C0.5 1.724 0.515 1.367 0.546 0.93C0.563722 0.677444 0.676581 0.441011 0.861804 0.268409C1.04703 0.0958068 1.29082 -0.000114433 1.544 2.56579e-07L5.08 2.56579e-07C5.20404 -0.000125334 5.3237 0.045859 5.41573 0.12902C5.50776 0.212182 5.5656 0.326583 5.578 0.45C5.601 0.68 5.622 0.863 5.642 1.002C5.84073 2.38892 6.248 3.73783 6.85 5.003C6.945 5.203 6.883 5.442 6.703 5.57L4.545 7.112C5.86445 10.1865 8.31455 12.6365 11.389 13.956L12.929 11.802C12.9919 11.714 13.0838 11.6509 13.1885 11.6237C13.2932 11.5964 13.4042 11.6068 13.502 11.653C14.767 12.2539 16.1156 12.6601 17.502 12.858C17.641 12.878 17.824 12.9 18.052 12.922C18.1752 12.9346 18.2894 12.9926 18.3724 13.0846C18.4553 13.1766 18.5012 13.2961 18.501 13.42H18.5Z" fill="white" />
                                        </svg>
                                        {{ settingLocale('phone') }}
                                    </p>
                                    <p> <svg width="23" height="18" viewBox="0 0 23 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M22.5 17.007C22.4982 17.2696 22.3931 17.521 22.2075 17.7068C22.0219 17.8926 21.7706 17.9979 21.508 18H3.492C3.22881 17.9997 2.9765 17.895 2.79049 17.7088C2.60448 17.5226 2.5 17.2702 2.5 17.007V16H20.5V4.3L12.5 11.5L2.5 2.5V1C2.5 0.734784 2.60536 0.48043 2.79289 0.292893C2.98043 0.105357 3.23478 0 3.5 0H21.5C21.7652 0 22.0196 0.105357 22.2071 0.292893C22.3946 0.48043 22.5 0.734784 22.5 1V17.007ZM4.934 2L12.5 8.81L20.066 2H4.934ZM0.5 12H8.5V14H0.5V12ZM0.5 7H5.5V9H0.5V7Z" fill="white" />
                                        </svg>

                                        {{ setting('email') }}
                                    </p>
                                </div>
                                <div class="header_topbar_right">
                                    <div class="d-none d-lg-block">
                                        <div class="dropdown theme_dropdown p-0 ">
                                            <button id="button" class="btn p-0">
                                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M10.0007 15.0002C7.23923 15.0002 5.00065 12.7616 5.00065 10.0002C5.00065 7.23874 7.23923 5.00016 10.0007 5.00016C12.7621 5.00016 15.0006 7.23874 15.0006 10.0002C15.0006 12.7616 12.7621 15.0002 10.0007 15.0002ZM10.0007 13.3335C11.8416 13.3335 13.334 11.8411 13.334 10.0002C13.334 8.15921 11.8416 6.66683 10.0007 6.66683C8.1597 6.66683 6.66732 8.15921 6.66732 10.0002C6.66732 11.8411 8.1597 13.3335 10.0007 13.3335ZM9.16732 0.833496H10.834V3.3335H9.16732V0.833496ZM9.16732 16.6668H10.834V19.1668H9.16732V16.6668ZM2.92958 4.1076L4.10809 2.9291L5.87586 4.69686L4.69735 5.87537L2.92958 4.1076ZM14.1254 15.3035L15.304 14.1249L17.0717 15.8927L15.8932 17.0712L14.1254 15.3035ZM15.8932 2.9291L17.0717 4.1076L15.304 5.87537L14.1254 4.69686L15.8932 2.9291ZM4.69735 14.1249L5.87586 15.3035L4.10809 17.0712L2.92958 15.8927L4.69735 14.1249ZM19.1673 9.16683V10.8335H16.6673V9.16683H19.1673ZM3.33398 9.16683V10.8335H0.833984V9.16683H3.33398Z" fill="#392C7D" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="">
                                        <select class="nice_Select language-change">
                                            @foreach (@$language['languages'] as $row)
                                                <option value="{{ $row->code }}" {{ $row->code == \Session::get('locale') ? 'selected' : '' }}>
                                                    {{ $row->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="header_topbar_social d-none d-sm-flex">
                                        @foreach ($sections['social_links']->data ?? [] as $item)
                                        <a target="_blank" href="{{ $item['link'] }}"><i class="{{ $item['icon'] }}"></i></a>
                                        @endforeach
                                    </div>
                                    <div class="login__regiter d-flex align-items-center">
                                        @if(!\Auth::check())
                                        <a href="{{route('login')}}">{{ ___('frontend.Login') }} </a>
                                        @else
                                            @if(\Auth::user()->role_id == 6)
                                                <a href="{{route('student-panel-dashboard.index')}}">{{ ___('frontend.Dashboard') }} </a>

                                            @elseif(\Auth::user()->role_id == 7)
                                                <a href="{{route('parent-panel-dashboard.index')}}">{{ ___('frontend.Dashboard') }} </a>
                                            @else
                                                <a href="{{route('dashboard')}}">{{ ___('frontend.Dashboard') }} </a>

                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <div class="header__wrapper">
                            <!-- header__left__start  -->
                            <div class="header__left d-flex align-items-center">
                                <div class="logo_img">
                                    <a href="{{ route('frontend.home') }}">
                                        <img class="logo dark_logo" src="{{ @globalAsset(setting('dark_logo'), 'dark.png') }}" alt="Logo">
                                        <img class="logo light_logo" src="{{ @globalAsset(setting('light_logo'), 'light.png') }}" alt="Logo">
                                    </a>
                                </div>
                            </div>
                            <!-- header__left__start  -->

                            <!-- main_menu_start  -->
                            <div class="main_menu text-right d-none d-lg-block">
                                <nav>
                                    <ul id="mobile-menu">
                                        <li><a href="{{ route('frontend.home') }}">{{ ___('frontend.Home') }}</a></li>
                                        <li><a href="{{ route('frontend.about') }}">{{ ___('frontend.About') }}</a></li>
                                        <li><a href="{{ route('frontend.news') }}">{{ ___('frontend.News') }}</a></li>
                                        <li><a href="{{ route('frontend.events') }}">{{ ___('frontend.Events') }}</a></li>
                                        <li>
                                            <a href="{{ route('frontend.courses') }}">Our programs</a>
                                            <ul class="submenu">
                                                <li>
                                                    <a href="{{ route('frontend.courses') }}">Online platform</a>
                                                    <ul class="submenu">
                                                        <li><a href="{{ route('frontend.holiday-camps') }}">Holiday Camps</a></li>
                                                        <li><a href="{{ route('frontend.courses') }}">Short Courses</a></li>
                                                    </ul>
                                                </li>
                                                <li>
                                                    <a href="{{ route('frontend.contact') }}">On campus</a>
                                                    <ul class="submenu">
                                                        <li><a href="{{ route('frontend.early-years') }}">Early Years</a></li>
                                                        <li><a href="{{ route('frontend.primary-education') }}">Primary Education</a></li>
                                                    </ul>
                                                </li>

                                            </ul>
                                        </li>
                                        <li><a href="{{ route('frontend.notices') }}">{{ ___('frontend.notices') }}</a></li>
                                        <li><a href="{{ route('frontend.result') }}">{{ ___('frontend.Result') }}</a></li>
                                        <li><a href="{{ route('frontend.contact') }}">{{ ___('frontend.contact_us') }}</a></li>
                                        <li class="d-lg-none"><a href="{{ route('frontend.online-admission') }}">{{ ___('frontend.online_admission') }}</a></li>
                                        @foreach ($header_pages as $header)
                                            <li><a href="{{ route('frontend.page',$header->slug) }}">{{ @$header->defaultTranslate->name }}</a></li>
                                        @endforeach
                                    </ul>
                                </nav>
                            </div>
                            <!-- main_menu_start  -->

                            <!-- header__right_start  -->
                            <div class="header__right">
                                <div class="contact_wrap d-flex align-items-center">
                                    <div class="contact_btn d-none d-lg-flex gap_15 align-items-center">
                                        <a href="{{ route('frontend.online-admission') }}" class="theme_btn small_btn3 min_windth_150 text-center">{{ ___('frontend.online_admission') }}</a>
                                    </div>

                                </div>
                            </div>
                            <!-- header__right_end  -->
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="mobile_menu d-block d-lg-none"></div>
                    </div>
                </div>
            </div>
        </div>
    </header>
    <!--/ HEADER::END -->
