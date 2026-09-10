@extends('frontend.master')
@section('title')
    {{ ___('frontend.about_US') }}
@endsection

@section('main')
    {{-- HERO ------------------------------------------------------------------ --}}
    <div class="breadcrumb_area">
        <div class="container">
            <div class="breadcam_wrap text-center">
                <h3>About Brainova</h3>
                <p>An online school that goes beyond classrooms &mdash; where curiosity sparks intelligence,
                    and intelligence drives innovation.</p>
            </div>
        </div>
    </div>

    <div class="bn-about">
        <div class="container">

            {{-- WHO WE ARE --------------------------------------------------------- --}}
            <section class="bn-about-intro">
                <div class="bn-about-intro__text">
                    <p class="bn-eyebrow">Who we are</p>
                    <h2>A future-ready school, built for the way children learn today</h2>
                    <p>At Brainova we go <strong>Beyond Classrooms</strong>, creating a future-focused learning
                        environment where curiosity sparks intelligence, and intelligence drives innovation. We work
                        to unlock the full cognitive and creative potential of every child through a learner-centric
                        pedagogy, a future-ready curriculum, and personalised, AI-enabled instruction.</p>
                    <p>Everything happens online &mdash; small live groups led by qualified teachers, adaptive
                        practice between sessions, and a parent account that shows you exactly how your child is
                        progressing every week.</p>
                </div>
                <ul class="bn-about-facts">
                    <li><span>100% online</span>Live classes, recorded lessons and support &mdash; no commute, no campus.</li>
                    <li><span>Small live groups</span>Every session is taught by a qualified teacher who knows your child.</li>
                    <li><span>Weekly parent reporting</span>Progress, effort and next steps sent straight to your account.</li>
                    <li><span>AI-enabled learning</span>Adaptive platforms personalise practice to each learner&rsquo;s pace.</li>
                </ul>
            </section>

            {{-- MISSION & VISION (admin-managed "statement" section) --------------- --}}
            @if (!empty($sections['statement']))
            <section class="bn-about-block">
                <div class="bn-home-head">
                    <p class="bn-eyebrow">Purpose</p>
                    <h2>{{ @$sections['statement']->defaultTranslate->name ?: 'Our mission and vision' }}</h2>
                </div>
                <div class="statement_area">
                    <div class="row align-items-center">
                        <div class="col-xl-7 col-lg-6">
                            <div class="statement_info mb_30">
                                <ul class="statement_lists">
                                    @foreach (is_array(@$sections['statement']->defaultTranslate->data ?? []) ? $sections['statement']->defaultTranslate->data : [] as $item)
                                        <li>
                                            <div class="statement_title d-flex align-items-center gap_20">
                                                <div class="icon">
                                                    <svg width="25" height="26" viewBox="0 0 25 26" fill="none"
                                                        xmlns="http://www.w3.org/2000/svg">
                                                        <rect x="12.1931" y="0.806641" width="17.2437" height="17.2437"
                                                            transform="rotate(45 12.1931 0.806641)" fill="#0097b2" />
                                                        <rect x="14.7651" y="3.37891" width="13.6062" height="13.6062"
                                                            transform="rotate(45 14.7651 3.37891)" fill="#5e17eb" />
                                                    </svg>
                                                </div>
                                                <h4>{{ $item['title'] }}</h4>
                                            </div>
                                            <p>{{ $item['description'] }}</p>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        <div class="col-xl-5 col-lg-6">
                            <div class="accreditation_wrapper mb_30">
                                <div class="thumb">
                                    <img src="{{ @globalAsset(@$sections['statement']->upload->path, '512X512.webp') }}"
                                        alt="Image" class="img-fluid">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            @endif

            {{-- WHY FAMILIES CHOOSE BRAINOVA (admin-managed "study_at" section) --- --}}
            @if (isset($sections['study_at']->defaultTranslate->data) && is_array($sections['study_at']->defaultTranslate->data) && count($sections['study_at']->defaultTranslate->data))
            <section class="bn-about-block">
                <div class="bn-home-head">
                    <p class="bn-eyebrow">Why Brainova</p>
                    <h2>{{ @$sections['study_at']->defaultTranslate->name ?: 'Why families choose Brainova' }}</h2>
                    @if (@$sections['study_at']->defaultTranslate->description)
                        <p>{{ $sections['study_at']->defaultTranslate->description }}</p>
                    @endif
                </div>
                @php $org = $sections['study_at']->data ?? []; @endphp
                <div class="bn-feature-grid">
                    @foreach ($sections['study_at']->defaultTranslate->data as $key => $item)
                        <article class="bn-feature">
                            <img class="bn-feature__img"
                                src="{{ @globalAsset(uploadPath(@$org[$key]['icon']), '90X60.webp') }}" alt="">
                            <h3>{{ $item['title'] }}</h3>
                            <p>{{ $item['description'] }}</p>
                        </article>
                    @endforeach
                </div>
            </section>
            @endif

            {{-- INSIDE BRAINOVA — what families get ------------------------------- --}}
            <section class="bn-about-block">
                <div class="bn-home-head">
                    <p class="bn-eyebrow">Inside Brainova</p>
                    <h2>What every family gets</h2>
                    <p>One login for your child and one for you &mdash; classes, coursework, assessment and
                        reporting in a single connected system.</p>
                </div>
                <div class="bn-feature-grid">
                    <article class="bn-feature">
                        <i class="fas fa-chart-line" aria-hidden="true"></i>
                        <h3>Parent portal</h3>
                        <p>Weekly progress, attendance and teacher feedback for every child, in one place.</p>
                    </article>
                    <article class="bn-feature">
                        <i class="fas fa-chalkboard-user" aria-hidden="true"></i>
                        <h3>Live small-group classes</h3>
                        <p>Timetabled sessions with a qualified teacher &mdash; interactive, not a video wall.</p>
                    </article>
                    <article class="bn-feature">
                        <i class="fas fa-clock-rotate-left" aria-hidden="true"></i>
                        <h3>Lessons on demand</h3>
                        <p>Recorded classes and resources your child can revisit any time.</p>
                    </article>
                    <article class="bn-feature">
                        <i class="fas fa-list-check" aria-hidden="true"></i>
                        <h3>Online exams &amp; scoreboards</h3>
                        <p>Assessments, quizzes and results tracked automatically as your child learns.</p>
                    </article>
                    <article class="bn-feature">
                        <i class="fas fa-file-arrow-up" aria-hidden="true"></i>
                        <h3>Homework &amp; projects</h3>
                        <p>Work set, submitted and marked online, including project and file uploads.</p>
                    </article>
                    <article class="bn-feature">
                        <i class="fas fa-award" aria-hidden="true"></i>
                        <h3>Certificates &amp; records</h3>
                        <p>Report cards, ID cards and certificates generated for you when they&rsquo;re needed.</p>
                    </article>
                </div>
            </section>

        </div>
    </div>

    {{-- OUR STORY (admin-managed "abouts") ----------------------------------- --}}
    @if (count($data['abouts']))
        <div class="about_gallery_wrapper section_padding">
            <div class="container">
                <div class="bn-home-head">
                    <p class="bn-eyebrow">Our journey</p>
                    <h2>How Brainova came together</h2>
                </div>
                <div class="row mb_30">
                    <div class="col-12">
                        @foreach ($data['abouts'] as $key => $item)
                            @if ($key % 2 == 0)
                                <div class="single_about_gallery">
                                    <div class="single_about_gallery_thumb">
                                        <img src="{{ @globalAsset(@$item->upload->path, '800X500.webp') }}" alt="Image" class="img-fluid">
                                    </div>
                                    <div class="single_about_content">
                                        <div class="iconImg">
                                            <img src="{{ @globalAsset(@$item->icon_upload->path, '90X60.webp') }}" alt="Image" class="img-fluid">
                                        </div>
                                        <h4>{{ @$item->defaultTranslate->name }}</h4>
                                        <p>{{ @$item->defaultTranslate->description }}</p>
                                    </div>
                                </div>
                            @else
                                <div class="single_about_gallery">
                                    <div class="single_about_content">
                                        <div class="iconImg">
                                            <img src="{{ @globalAsset(@$item->icon_upload->path, '65X90.webp') }}" alt="Image" class="img-fluid">
                                        </div>
                                        <h4>{{ @$item->defaultTranslate->name }}</h4>
                                        <p>{{ @$item->defaultTranslate->description }}</p>
                                    </div>
                                    <div class="single_about_gallery_thumb">
                                        <img src="{{ @globalAsset(@$item->upload->path, '800X500.webp') }}" alt="Image" class="img-fluid">
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- MEET OUR TEACHERS (admin-managed staff) ----------------------------- --}}
    @if (count($data['teachers']))
        <div class="instractors_wrapper gray_bg">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="section__title mb_76 text-center">
                            <p class="bn-eyebrow">Our people</p>
                            <h3 class="text-capitalize">{{ @$sections['our_teachers']->defaultTranslate->name ?: 'Meet our teachers' }}</h3>
                            <p>Qualified, specialist teachers who lead every live session and know each child by name.</p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    @foreach ($data['teachers'] as $item)
                        <div class="col-md-6 col-lg-4 col-xl-3">
                            <div class="single_instractor mb_30 position-relative">
                                <a href="#" class="thumb">
                                    <img src="{{ @globalAsset(@$item->upload->path, '340X340.webp') }}" alt="Image">
                                </a>
                                <div class="instractor_info text-center">
                                    <div class="instractor_info_content">
                                        <h4>{{ @$item->first_name }} {{ @$item->last_name }}</h4>
                                        <div class="instractor_social">
                                            <p>{{ @$item->designation->name }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- CTA ---------------------------------------------------------------- --}}
    <div class="bn-about">
        <div class="container">
            <section class="bn-about-block" style="padding-bottom:70px">
                <div class="bn-handbook-inner bn-aurora">
                    <div>
                        <h2>See Brainova with your own child</h2>
                        <p>Book a free trial and watch a real live session, or start your application when you&rsquo;re ready.</p>
                    </div>
                    <div class="bn-handbook-actions">
                        <a href="{{ route('frontend.book-free-trial') }}" class="bn-btn bn-btn--primary">Book a free trial</a>
                        <a href="{{ route('frontend.online-admission') }}" class="bn-btn bn-btn--ghost">Start online admission</a>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
