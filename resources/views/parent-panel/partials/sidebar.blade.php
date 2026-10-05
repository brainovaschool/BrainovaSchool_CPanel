<aside class="sidebar" id="sidebar">

    <x-sidebar-header />

    <div class="sidebar-menu srollbar">
        <div class="sidebar-menu-section">


            <!-- parent menu list start  -->
            <ul class="sidebar-dropdown-menu">
                <li class="sidebar-menu-item {{ set_menu(['parent-panel-dashboard*']) }}">
                    <a href="{{ route('parent-panel-dashboard.index') }}" class="parent-item-content">
                        {{-- <img src="{{ asset('backend') }}/assets/images/icons/notification-status.svg" alt="Dashboard" /> --}}
                        <i class="las la-desktop"></i>
                        <span class="on-half-expanded">{{ ___('common.dashboard') }}</span>
                    </a>
                </li>
                @if (hasFeature('academic'))
                    <li class="sidebar-menu-item {{ set_menu(['parent-panel-subject-list*']) }}">
                        <a href="{{ route('parent-panel-subject-list.index') }}" class="parent-item-content">
                            {{-- <img src="{{ asset('backend') }}/assets/images/icons/notification-status.svg" alt="subject-list" /> --}}
                            <i class="las la-book"></i>
                            <span class="on-half-expanded">{{ ___('settings.subject_list') }}</span>
                        </a>
                    </li>
                @endif
                @if (hasFeature('routine'))
                    <li class="sidebar-menu-item {{ set_menu(['parent-panel-class-routine*']) }}">
                        <a href="{{ route('parent-panel-class-routine.index') }}" class="parent-item-content">
                            {{-- <img src="{{ asset('backend') }}/assets/images/icons/notification-status.svg" alt="class-routine" /> --}}
                            <i class="las la-clock"></i>
                            <span class="on-half-expanded">{{ ___('settings.class_routine') }}</span>
                        </a>
                    </li>
                @endif
                @if (hasFeature('fees'))
                    <li class="sidebar-menu-item {{ set_menu(['parent-panel-fees*']) }}">
                        <a href="{{ route('parent-panel-fees.index') }}" class="parent-item-content">
                            <i class="las la-wallet"></i>
                            <span class="on-half-expanded">{{ ___('settings.fees') }}</span>
                        </a>
                    </li>
                @endif

                @if (hasFeature('fees') && hasModule('BehaviourRecord'))
                    <li class="sidebar-menu-item {{ set_menu(['parent-panel.behaviourrecord.index', 'parent-panel.behaviourrecord.*']) }}">
                        <a href="{{ route('parent-panel.behaviourrecord.index') }}" class="parent-item-content">
                            <i class="las la-book"></i>
                            <span class="on-half-expanded">{{ ___('student.Behaviour Record') }}</span>
                        </a>
                    </li>
                @endif

                @if (hasModule('VehicleTracker'))
                <li class="sidebar-menu-item {{ set_menu(['parent-panel-transport/*']) }}">
                    <a class="parent-item-content has-arrow">
                        <i class="las la-bus"></i>
                        <span class="on-half-expanded">{{ ___('student.Transport') }} @if(env('APP_DEMO')) <span class="badge badge-danger">Pro</span>@endif</span>
                    </a>
                    <ul class="child-menu-list">
                        <li class="sidebar-menu-item {{ set_menu(['parent-panel-transport.schdule']) }}">
                            <a href="{{ route('parent-panel-transport.schdule') }}">{{ ___('settings.Schdule') }}</a>
                        </li>
                        <li class="sidebar-menu-item {{ set_menu(['parent-panel-transport/report*']) }}">
                            <a
                                href="{{ route('parent-panel-transport.report') }}">{{ ___('settings.Report') }}</a>
                        </li>
                        <li class="sidebar-menu-item {{ set_menu(['parent-panel-transport.livetrack']) }}">
                            <a href="{{ route('parent-panel-transport.livetrack') }}">{{ ___('settings.Live Track') }}</a>
                        </li>
                    </ul>
                </li>
                @endif

                @if (hasFeature('routine'))
                    <li class="sidebar-menu-item {{ set_menu(['parent-panel-exam-routine*']) }}">
                        <a href="{{ route('parent-panel-exam-routine.index') }}" class="parent-item-content">
                            {{-- <img src="{{ asset('backend') }}/assets/images/icons/notification-status.svg" alt="exam-routine" /> --}}
                            <i class="las la-book-reader"></i>
                            <span class="on-half-expanded">{{ ___('settings.exam_routine') }}</span>
                        </a>
                    </li>
                @endif
                @if (hasFeature('report'))
                    <li class="sidebar-menu-item {{ set_menu(['parent-panel-marksheet*']) }}">
                        <a href="{{ route('parent-panel-marksheet.index') }}" class="parent-item-content">
                            {{-- <img src="{{ asset('backend') }}/assets/images/icons/notification-status.svg" alt="marksheet" /> --}}
                            <i class="las la-graduation-cap"></i>
                            <span class="on-half-expanded">{{ ___('settings.marksheet') }}</span>
                        </a>
                    </li>
                @endif
                @if (hasFeature('attendance'))
                    <li class="sidebar-menu-item {{ set_menu(['parent-panel-attendance*']) }}">
                        <a href="{{ route('parent-panel-attendance.index') }}" class="parent-item-content">
                            <i class="las la-calendar-check"></i>
                            <span class="on-half-expanded">{{ ___('settings.Attendance') }}</span>
                        </a>
                    </li>
                @endif
                @if (hasFeature('attendance'))
                    <li class="sidebar-menu-item {{ set_menu(['parent-panel-notice*']) }}">
                        <a href="{{ route('parent-panel-notices.index') }}" class="parent-item-content">
                            <i class="las la-bell"></i>
                            <span class="on-half-expanded">{{ ___('settings.notices') }}</span>
                        </a>
                    </li>
                @endif
                <!-- Library start -->
                @if (hasFeature('attendance'))
                    <li
                        class="sidebar-menu-item {{ set_menu(['parent-panel-book.index', 'parent-panel-issue-books.index']) }}">
                        <a class="parent-item-content has-arrow">
                            <i class="las la-swatchbook"></i>
                            <span class="on-half-expanded">{{ ___('settings.Library') }}</span>
                        </a>
                        <ul class="child-menu-list">
                            <li class="sidebar-menu-item {{ set_menu(['parent-panel-book.index']) }}">
                                <a href="{{ route('parent-panel-book.index') }}">{{ ___('settings.Book') }}</a>
                            </li>
                            <li class="sidebar-menu-item {{ set_menu(['parent-panel-issue-books.index']) }}">
                                <a
                                    href="{{ route('parent-panel-issue-books.index') }}">{{ ___('settings.issue_book') }}</a>
                            </li>
                        </ul>
                    </li>
                @endif

                <li class="sidebar-menu-item {{ set_menu(['student.study-material*']) }}">
                    <a href="{{ route('parent.leave.index') }}" class="parent-item-content">
                        <i class="las la-power-off"></i>
                        <span class="on-half-expanded">{{ ___('study-material.Leave') }}</span>
                    </a>
                </li>

                <!-- Library end -->
                @if (hasFeature('attendance'))
                    <li class="sidebar-menu-item {{ set_menu(['parent-panel-homework*']) }}">
                        <a href="{{ route('parent-panel-homeworks.index') }}" class="parent-item-content">
                            <i class="las la-home"></i>
                            <span class="on-half-expanded">{{ ___('online-examination.homeworks') }}</span>
                        </a>
                    </li>
                @endif
                <li class="sidebar-menu-item {{ set_menu(['parent-panel-class-content*']) }}">
                    <a href="{{ route('parent-panel-class-content.index') }}" class="parent-item-content">
                        <i class="las la-book-reader"></i>
                        <span class="on-half-expanded">Class Content</span>
                    </a>
                </li>

                @if (hasModule('StudyMaterial'))
                    <li class="sidebar-menu-item {{ set_menu(['parent.study-material*']) }}">
                        <a href="{{ route('parent.study-material.index') }}" class="parent-item-content">
                            <i class="las la-book-reader"></i>
                            <span class="on-half-expanded">{{ ___('study-material.Study Material') }}</span>
                        </a>
                    </li>
                @endif

                @if (hasModule('LiveChat'))
                    <li class="sidebar-menu-item {{ set_menu(['guardian/live-chat*']) }}">
                        <a href="{{ route('guardian.live_chat') }}" class="parent-item-content">
                            <i class="lab la-facebook-messenger"></i>
                            <span class="on-half-expanded">{{ ___('settings.Live Chat') }}</span>
                        </a>
                    </li>
                @endif
                @if (hasModule('Forums'))
                    @include('forums::menus.parents_forum_menu')
                @endif



            </ul>
        </div>
    </div>
</aside>
