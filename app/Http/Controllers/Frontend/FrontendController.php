<?php

namespace App\Http\Controllers\Frontend;

use PDF;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Repositories\GenderRepository;
use Illuminate\Support\Facades\Schema;
use App\Repositories\ReligionRepository;
use Illuminate\Support\Facades\Validator;
use App\Repositories\Report\MarksheetRepository;
use App\Repositories\Frontend\FrontendRepository;
use App\Repositories\WebsiteSetup\PageRepository;
use App\Http\Requests\Frontend\SearchResultRequest;
use App\Http\Requests\Frontend\OnlineAdmissionStoreRequest;
use App\Models\WebsiteSetup\OnlineAdmission;
use App\Repositories\Academic\ShiftRepository;
use App\Repositories\StudentInfo\StudentRepository;
use App\Repositories\StudentInfo\OnlineAdmissionSettingRepository;

class FrontendController extends Controller
{
    private $repo;
    private $religionRepo;
    private $genderRepo;
    private $marksheetRepo;
    private $studentRepo;
    private $pageRepo;
    private $admission_setting_repo;
    private $shift_repo;

    function __construct(
        FrontendRepository $repo,
        ReligionRepository $religionRepo,
        GenderRepository   $genderRepo,
        MarksheetRepository    $marksheetRepo,
        StudentRepository      $studentRepo,
        PageRepository      $pageRepo,
        OnlineAdmissionSettingRepository      $admission_setting_repo,
        ShiftRepository      $shift_repo,
    )
    {
        if (!Schema::hasTable('settings') && !Schema::hasTable('users'))
            abort(400);
        $this->repo         = $repo;
        $this->religionRepo = $religionRepo;
        $this->genderRepo   = $genderRepo;
        $this->marksheetRepo      = $marksheetRepo;
        $this->studentRepo        = $studentRepo;
        $this->pageRepo        = $pageRepo;
        $this->admission_setting_repo        = $admission_setting_repo;
        $this->shift_repo        = $shift_repo;
    }

    public function index()
    {
        $data['sliders']          = $this->repo->sliders();
        
        $data['counters']         = $this->repo->counters();
        $data['galleryCategory']  = $this->repo->galleryCategory();
        $data['gallery']          = $this->repo->gallery();
        $data['latestNews']       = $this->repo->latestNews();
        $data['comingEvents']     = $this->repo->comingEvents();

        return view('frontend.home', compact('data'));
    }

    // Result
    public function getClasses(Request $request){
        $data = $this->repo->getClasses($request); // session id
        return response()->json($data);
    }
    public function getSections(Request $request){
        $data = $this->repo->getSections($request); // class id
        return response()->json($data);
    }
    public function getExamType(Request $request)
    {
        $result = $this->repo->getExamType($request);
        return response()->json($result, 200);
    }
    public function result()
    {
        $data = $this->repo->result();
        $data['result'] = null;
        return view('frontend.result', compact('data'));
    }

    public function searchResult(SearchResultRequest $request){
        $data = $this->repo->searchResult($request);
        if(!$data)
        {
            $data = $this->repo->result();
            $data['result'] = "Result not found!";
            return view('frontend.result', compact('data'));
        }
        $data['request'] = $request;
        return view('frontend.search_result', compact('data'));
    }

    public function downloadPDF($id, $type, $class, $section)
    {
        $request = new Request([
            'student'   => $id,
            'exam_type' => $type,
            'class'     => $class,
            'section'   => $section,
        ]);

        $data['student']      = $this->studentRepo->show($request->student);
        $data['resultData']   = $this->marksheetRepo->search($request);

        $pdf = PDF::loadView('backend.report.marksheetPDF', compact('data'));
        return $pdf->download('marksheet'.'_'.date('d_m_Y').'_'.@$data['student']->first_name .'_'. @$data['student']->last_name .'.pdf');
    }

    public function about()
    {
        $data = $this->repo->abouts();

        return view('frontend.about', compact('data'));
    }

    // News / Blog (same table, split by the `type` column)
    public function news(Request $request)
    {
        $type = $request->query('type') === 'blog' ? 'blog' : 'news';

        $data['news']      = $this->repo->news($type);
        $data['type']      = $type;
        $data['pageTitle'] = $type === 'blog' ? ___('frontend.Blog') : ___('frontend.News');

        return view('frontend.news', compact('data'));
    }

    public function newsDetail($id)
    {
        $data['news'] = $this->repo->newsDetail($id);
        if (!$data['news']) {
            abort(404);
        }

        $data['type']      = ($data['news']->type ?? 'news') === 'blog' ? 'blog' : 'news';
        $data['pageTitle'] = $data['type'] === 'blog' ? ___('frontend.Blog') : ___('frontend.News');
        $data['allNews']   = $this->repo->news($data['type']);

        return view('frontend.news-detail', compact('data'));
    }

    // Event
    public function events()
    {
        $events = $this->repo->events();
        return view('frontend.events', compact('events'));
    }

    // ---------------------------------------------------------------
    // Programs / Courses catalogue (DB-driven, WebsiteSetup > Programs)
    // ---------------------------------------------------------------

    /** All programs, with the 4 categories as filter pills. */
    public function courses(Request $request)
    {
        $categories = \App\Models\WebsiteSetup\ProgramCategory::where('status', 1)
            ->orderBy('sort_order')->orderBy('name')->get();

        $activeCategory = trim((string) $request->query('category', 'all')) ?: 'all';

        $query = \App\Models\WebsiteSetup\Program::query()->active()
            ->with(['category', 'focus'])
            ->orderBy('sort_order')->orderBy('title');

        if ($activeCategory !== 'all') {
            $cat = $categories->firstWhere('slug', $activeCategory);
            if ($cat) {
                $query->where('program_category_id', $cat->id);
            } else {
                $activeCategory = 'all';
            }
        }

        $paginator = $query->paginate(9)->withQueryString();

        $data['hero'] = [
            'title'         => 'Programs built for curiosity and confidence',
            'subtitle'      => 'Homeschooling, tutoring, electives and clubs — every path is taught by the same faculty and tracked with the same weekly reporting.',
            'primary_cta'   => ['label' => 'Talk to admissions', 'route' => 'frontend.contact'],
            'secondary_cta' => ['label' => 'Start online admission', 'route' => 'frontend.online-admission'],
        ];
        $data['categories'] = array_merge(
            [['slug' => 'all', 'label' => 'All programs']],
            $categories->map(fn ($c) => ['slug' => $c->slug, 'label' => $c->name])->all()
        );
        $data['courses']         = $paginator->getCollection();
        $data['active_category'] = $activeCategory;
        $data['catalog_total']   = \App\Models\WebsiteSetup\Program::query()->active()->count();
        $data['faqs']            = $this->programFaqs();
        $data['trust']           = $this->programTrust();

        return view('frontend.courses', compact('data', 'paginator'));
    }

    /** A single program. */
    public function courseDetail(string $slug)
    {
        $data['course'] = \App\Models\WebsiteSetup\Program::query()->active()
            ->with(['category', 'focus'])
            ->where('slug', $slug)->first();

        if (!$data['course']) {
            abort(404);
        }

        $data['trust'] = $this->programTrust();

        return view('frontend.course-detail', compact('data'));
    }

    /** A category landing page (/programs/{slug}) with its focus areas as sub-filters. */
    public function programCategory(Request $request, string $category)
    {
        $model = \App\Models\WebsiteSetup\ProgramCategory::where('status', 1)
            ->where('slug', $category)->first();

        if (!$model) {
            abort(404);
        }

        $focuses = $model->focuses()->where('status', 1)->get();

        $activeFocus = trim((string) $request->query('focus', '')) ?: null;
        $focusModel  = $activeFocus ? $focuses->firstWhere('slug', $activeFocus) : null;
        if ($activeFocus && !$focusModel) {
            $activeFocus = null;
        }

        $query = \App\Models\WebsiteSetup\Program::query()->active()->with('focus')
            ->where('program_category_id', $model->id)
            ->orderBy('sort_order')->orderBy('title');

        if ($focusModel) {
            $query->where('program_focus_id', $focusModel->id);
        }

        $paginator = $query->paginate(9)->withQueryString();

        $data['category']     = $model;
        $data['focuses']      = $focuses;
        $data['active_focus'] = $activeFocus;
        $data['programs']     = $paginator->getCollection();
        $data['total']        = \App\Models\WebsiteSetup\Program::query()->active()
                                    ->where('program_category_id', $model->id)->count();
        $data['trust']        = $this->programTrust();

        return view('frontend.program-category', compact('data', 'paginator'));
    }

    protected function programTrust(): array
    {
        return [
            'headline' => 'Why families choose Brainova programs',
            'body'     => 'Experienced mentors, transparent reporting, and a culture that treats technology as a tutor — not a replacement for relationships. Your child progresses with adults who notice both effort and mastery.',
        ];
    }

    protected function programFaqs(): array
    {
        return [
            ['q' => 'Which program should we start with?', 'a' => 'Share your child’s grade band and weekly availability — admissions will point you to a short diagnostic or straight enrolment when the placement is obvious.'],
            ['q' => 'Are programs online or on campus?', 'a' => 'Most younger cohorts are face-to-face. Middle and upper programs often blend live sessions with portal tasks; the exact mix is published in each intake letter.'],
            ['q' => 'Fees, scholarships and payment plans?', 'a' => 'Contact admissions or complete the online admission flow — counsellors share the current fee grid, sibling policies and any term-limited bursaries.'],
        ];
    }

    public function eventDetail($id)
    {
        $data['allEvent'] = $this->repo->events();
        $data['event']    = $this->repo->eventDetail($id);
        return view('frontend.event-detail', compact('data'));
    }


    public function page($slug)
    {
        $data['page']    = $this->pageRepo->findBySlug($slug);
        return view('frontend.page-detail', compact('data'));
    }


    // Event
    public function notices()
    {
        $data['notices'] = $this->repo->notices();
        return view('frontend.notices', compact('data'));
    }
    public function noticeDetail($id)
    {
        $data['allNotice'] = $this->repo->notices();
        $data['notice-board']    = $this->repo->noticeDetail($id);
        return view('frontend.notice-detail', compact('data'));
    }

    // Contact
    public function contact()
    {
        $data['contactInfo']    = $this->repo->contactInfo();
        $data['depContact']     = $this->repo->depContact();
        return view('frontend.contact', compact('data'));
    }

    // Testimonials & Reviews — one method, two routes
    public function testimonialsPage(Request $request)
    {
        $type = $request->routeIs('frontend.reviews') ? 'review' : 'testimonial';

        $data['type']  = $type;
        $data['title'] = $type === 'review' ? 'Reviews' : 'Testimonials';
        $data['lead']  = $type === 'review'
            ? 'Ratings and feedback shared by our community.'
            : 'Stories from parents and students across our programs.';
        $data['other'] = $type === 'review'
            ? ['label' => 'Read testimonials', 'url' => route('frontend.testimonials')]
            : ['label' => 'See reviews', 'url' => route('frontend.reviews')];

        $data['items'] = \App\Models\WebsiteSetup\Testimonial::query()->active()
            ->where('type', $type)
            ->orderBy('sort_order')->orderBy('id', 'desc')
            ->get();

        return view('frontend.testimonials', compact('data'));
    }

    // Book a free trial
    public function bookFreeTrial()
    {
        $slots = \App\Models\WebsiteSetup\TrialSlot::query()->active()
            ->whereDate('slot_date', '>=', now()->toDateString())
            ->orderBy('slot_date')->orderBy('start_time')
            ->get()
            ->filter(fn ($s) => $s->remaining > 0);

        $data['slotsByDate'] = $slots->groupBy(fn ($s) => $s->slot_date->format('Y-m-d'))
            ->map(fn ($group) => $group->map(fn ($s) => [
                'id'        => $s->id,
                'label'     => $s->time_label,
                'remaining' => $s->remaining,
            ])->values())
            ->toArray();

        $data['availableDates'] = array_keys($data['slotsByDate']);

        return view('frontend.book-a-free-trial', compact('data'));
    }

    public function storeFreeTrial(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:120',
            'email'         => 'required|email|max:150',
            'phone'         => 'required|string|max:40',
            'child_age'     => 'nullable|string|max:80',
            'program'       => ['required', 'string', 'max:150', \Illuminate\Validation\Rule::in(array_merge(online_admission_programs(), ['Not sure yet']))],
            'trial_slot_id' => 'nullable|integer|exists:trial_slots,id',
            'message'       => 'nullable|string|max:2000',
        ]);

        $result = $this->repo->freeTrial($request);

        if ($result === 'slot_full') {
            return back()->withInput()->with('error', 'Sorry — that time slot just filled up. Please pick another.');
        }

        return redirect()->route('frontend.book-free-trial')
            ->with('message', 'Thanks! Your free-trial request has been received — our team will contact you shortly.');
    }

    // onlineAdmission
    public function onlineAdmission()
    {
        return view('frontend.online-admission');
    }


        // onlineAdmission
        public function onlineAdmissionFees($student_phone, $admission_id)
        {
            $data['admission'] = $this->repo->onlineAdmissionDetail($admission_id);
            $data['setting']  = $this->admission_setting_repo->getIsShowByType('online_admission');
            $data['fees'] = $this->repo->onlineAdmissionFees($data['admission']->session_id, $data['admission']->classes_id , $data['admission']->section_id);
            $data['payment_instruction'] = $this->admission_setting_repo->getOneByFied('admission_payment_info');
            if($data['admission']->payment_status == 2 && $data['fees']){
                return view('frontend.online-admission-fees', compact('data'));
            }

            return view('frontend.online-admission-fees', compact('data'));
        }

    public function storeOnlineAdmission(OnlineAdmissionStoreRequest $request) {
        $admission = $this->repo->onlineAdmission($request);

        if (!$admission instanceof OnlineAdmission) {
            return redirect()->back()->with('error', 'Something went wrong. Please try again.');
        }

        return redirect()->route('frontend.online-admission')->with('message', 'Your application has been submitted successfully! We will contact you shortly.');
    }


    public function storeOnlineAdmissionFees(Request $request) {
        $validator = Validator::make($request->all(), [
            'payment_image' => 'required|mimes:jpeg,png,jpg,gif'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $admission = $this->repo->storeOnlineAdmissionFees($request);

        if($admission){
            return redirect()->route('frontend.online-admission')->with('message' , 'Admission Inform submitted successfully , Please complete payment for successfully admission');
        }
    }

    public function storeContact(Request $request)
    {
        return $this->repo->contact($request);
    }

    public function storeSubscribe(Request $request)
    {
        return $this->repo->subscribe($request);
    }
}