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

    // Blog
    public function news()
    {
        $data['news'] = $this->repo->news();
        return view('frontend.news', compact('data'));
    }
    public function newsDetail($id)
    {
        $data['allNews'] = $this->repo->news();
        $data['news']    = $this->repo->newsDetail($id);
        return view('frontend.news-detail', compact('data'));
    }

    // Event
    public function events()
    {
        $events = $this->repo->events();
        return view('frontend.events', compact('events'));
    }

    public function holidayCamps(Request $request)
    {
        $data = $this->normalizeCampsCatalog($this->frontendHolidayCampsCatalog());
        $allCamps = collect($data['camps'] ?? []);

        $category = $this->normalizeCampCategory((string) $request->query('category', 'all'));
        if ($category === '') {
            $category = 'all';
        }

        $filtered = $category === 'all'
            ? $allCamps
            : $allCamps->filter(fn (array $camp) => ($camp['category'] ?? '') === $category);

        $perPage = 9;
        $page = max(1, (int) $request->query('page', 1));
        $total = $filtered->count();
        $items = $filtered->values()->forPage($page, $perPage);

        $query = $category !== 'all' ? ['category' => $category] : [];

        $paginator = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path'  => route('frontend.holiday-camps'),
                'query' => $query,
            ]
        );

        $data['camps'] = $items->values()->all();
        $data['active_category'] = $category;
        $data['catalog_total'] = $allCamps->count();

        return view('frontend.holiday-camps', compact('data', 'paginator'));
    }

    public function holidayCampDetail(string $slug)
    {
        $data = $this->normalizeCampsCatalog($this->frontendHolidayCampsCatalog());
        $camp = collect($data['camps'] ?? [])->firstWhere('slug', $slug);
        if ($camp === null) {
            abort(404);
        }
        $data['camp'] = $camp;

        return view('frontend.holiday-camp-detail', compact('data'));
    }

    public function earlyYears()
    {
        return view('frontend.early-years');
    }

    public function primaryEducation()
    {
        return view('frontend.primary-education');
    }

    public function courses(Request $request)
    {
        $data = $this->normalizeCoursesCatalog($this->frontendCoursesCatalog());
        $allCourses = collect($data['courses'] ?? []);

        $category = $this->normalizeCourseCategory((string) $request->query('category', 'all'));
        if ($category === '') {
            $category = 'all';
        }

        $filtered = $category === 'all'
            ? $allCourses
            : $allCourses->filter(fn (array $course) => ($course['category'] ?? '') === $category);

        $perPage = 9;
        $page = max(1, (int) $request->query('page', 1));
        $total = $filtered->count();
        $items = $filtered->values()->forPage($page, $perPage);

        $query = $category !== 'all' ? ['category' => $category] : [];

        $paginator = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path'  => route('frontend.courses'),
                'query' => $query,
            ]
        );

        $data['courses'] = $items->values()->all();
        $data['active_category'] = $category;
        $data['catalog_total'] = $allCourses->count();

        return view('frontend.courses', compact('data', 'paginator'));
    }

    public function courseDetail(string $slug)
    {
        $data = $this->normalizeCoursesCatalog($this->frontendCoursesCatalog());
        $courses = $data['courses'] ?? [];
        $course = collect($courses)->firstWhere('slug', $slug);
        if ($course === null) {
            abort(404);
        }
        $data['course'] = $course;

        return view('frontend.course-detail', compact('data'));
    }

    /**
     * Public courses catalog. Prefer config(); if courses are missing (stale
     * config:cache, file not merged, or deploy without the file), load
     * config/frontend_courses.php from disk, then use a minimal fallback.
     */
    protected function frontendCoursesCatalog(): array
    {
        $paths = [
            base_path('config/frontend_courses.php'),
            config_path('frontend_courses.php'),
        ];

        foreach ($paths as $path) {
            if (!is_readable($path)) {
                continue;
            }

            $data = require $path;

            if (is_array($data) && !empty($data['courses']) && is_array($data['courses'])) {
                return $this->normalizeCoursesCatalog($data);
            }
        }

        $cached = config('frontend_courses');

        if (is_array($cached) && !empty($cached['courses']) && is_array($cached['courses'])) {
            return $this->normalizeCoursesCatalog($cached);
        }

        return $this->normalizeCoursesCatalog($this->minimalFrontendCoursesCatalog());
    }

    /**
     * Map legacy / display category labels to filter pill slugs.
     */
    protected function normalizeCourseCategory(string $category): string
    {
        $category = strtolower(trim($category));

        $map = [
            'maths'        => 'math',
            'mathematics'  => 'math',
            'english'      => 'language',
            'languages'    => 'language',
            'stem & computing' => 'stem',
            'life skills'  => 'skills',
        ];

        return $map[$category] ?? $category;
    }

    protected function normalizeCoursesCatalog(array $data): array
    {
        if (!empty($data['courses']) && is_array($data['courses'])) {
            foreach ($data['courses'] as $index => $course) {
                if (!is_array($course)) {
                    continue;
                }
                $data['courses'][$index]['category'] = $this->normalizeCourseCategory(
                    (string) ($course['category'] ?? '')
                );
            }
        }

        return $data;
    }

    protected function frontendHolidayCampsCatalog(): array
    {
        $paths = [
            base_path('config/frontend_holiday_camps.php'),
            config_path('frontend_holiday_camps.php'),
        ];

        foreach ($paths as $path) {
            if (!is_readable($path)) {
                continue;
            }

            $data = require $path;

            if (is_array($data) && !empty($data['camps']) && is_array($data['camps'])) {
                return $this->normalizeCampsCatalog($data);
            }
        }

        $cached = config('frontend_holiday_camps');

        if (is_array($cached) && !empty($cached['camps']) && is_array($cached['camps'])) {
            return $this->normalizeCampsCatalog($cached);
        }

        return $this->normalizeCampsCatalog([]);
    }

    protected function normalizeCampCategory(string $category): string
    {
        return strtolower(trim($category));
    }

    protected function normalizeCampsCatalog(array $data): array
    {
        if (!empty($data['camps']) && is_array($data['camps'])) {
            foreach ($data['camps'] as $index => $camp) {
                if (!is_array($camp)) {
                    continue;
                }
                $data['camps'][$index]['category'] = $this->normalizeCampCategory(
                    (string) ($camp['category'] ?? '')
                );
            }
        }

        return $data;
    }

    /**
     * Last-resort catalog so /courses is never blank (e.g. missing deploy).
     */
    protected function minimalFrontendCoursesCatalog(): array
    {
        return [
            'hero' => [
                'title'       => 'Explore our courses',
                'subtitle'    => 'Programs at Brainova School—contact us for the full catalog and current intake.',
                'primary_cta' => [
                    'label' => 'Contact us',
                    'route' => 'frontend.contact',
                ],
                'secondary_cta' => [
                    'label' => 'Online admission',
                    'route' => 'frontend.online-admission',
                ],
            ],
            'categories' => [
                ['slug' => 'all', 'label' => 'All programs'],
                ['slug' => 'stem', 'label' => 'STEM & computing'],
                ['slug' => 'math', 'label' => 'Mathematics'],
                ['slug' => 'language', 'label' => 'Languages'],
                ['slug' => 'skills', 'label' => 'Life skills'],
            ],
            'courses' => [
                [
                    'slug'        => 'basic-to-intermediate-maths',
                    'category'    => 'math',
                    'badge'       => 'Maths',
                    'title'       => 'Basic to Intermediate Maths',
                    'description' => 'Number place value, Arthematics, Fractions, Decimals, Geometry',
                    'age_range'   => 'Ages 6-9',
                    'grade'       => 'Grade 2-5',
                    'lessons'     => '12 sessions',
                    'duration'    => '6 weeks',
                    'enrolled'    => 'Open enrollment',
                    'price'       => 'Contact for fee',
                    'accent'      => 'indigo',
                    'image'       => 'https://images.unsplash.com/photo-1485827404703-89b55fcc595e?auto=format&fit=crop&w=1200&q=80',
                    'overview'    => ['Details available from the school office.'],
                    'highlights'  => ['Small groups', 'Safe lab practices'],
                    'format'      => 'Twice weekly',
                ],
                [
                    'slug'        => 'advanced-maths',
                    'category'    => 'math',
                    'badge'       => 'Maths',
                    'title'       => 'Advanced Maths',
                    'description' => 'Word problem, Arthematic, Geometry and Algebra',
                    'age_range'   => 'Ages 10-14',
                    'grade'       => 'Grade 4-7',
                    'lessons'     => '12 sessions',
                    'duration'    => '6 weeks',
                    'enrolled'    => 'Open enrollment',
                    'price'       => 'Contact for fee',
                    'accent'      => 'teal',
                    'image'       => 'https://images.unsplash.com/photo-1635070041078-e363dbe005cb?auto=format&fit=crop&w=1200&q=80',
                    'overview'    => ['Placement by short diagnostic.'],
                    'highlights'  => ['Growth mindset', 'Weekly feedback'],
                    'format'      => 'Twice weekly.',
                ],
                [
                    'slug'        => 'basic-to-intermediate-english',
                    'category'    => 'language',
                    'badge'       => 'English',
                    'title'       => 'Basic to Intermediate English',
                    'description' => 'Reading, writing, listening, and speaking from foundational fluency through confident intermediate use.',
                    'age_range'   => 'Ages 8–12',
                    'grade'       => 'Grade 4–6',
                    'lessons'     => '60 workshops',
                    'duration'    => 'Academic year',
                    'enrolled'    => 'Open enrollment',
                    'price'       => 'Contact for fee',
                    'accent'      => 'amber',
                    'image'       => 'https://images.unsplash.com/photo-1456513080510-7bf3a84b82f8?auto=format&fit=crop&w=1200&q=80',
                    'overview'    => ['Placement by short diagnostic.'],
                    'highlights'  => ['Growth dashboards', 'Weekly feedback'],
                    'format'      => '3 × 55-minute sessions weekly',
                ],
                [
                    'slug'        => 'advanced-english',
                    'category'    => 'language',
                    'badge'       => 'English',
                    'title'       => 'Advanced English',
                    'description' => 'Comprehension, Creative Writting, Grammar and Vocabulary, Spoken English',
                    'age_range'   => 'Ages 10-14',
                    'grade'       => 'Grade 4-7',
                    'lessons'     => '12 sessions',
                    'duration'    => '6 weeks',
                    'enrolled'    => 'Open enrollment',
                    'price'       => 'Contact for fee',
                    'accent'      => 'teal',
                    'image'       => '/Users/muhammadraza/Projects/BrainovaSchool_CPanel/public/frontend/frontend/assets/images/images/Advanced-English-Vocabulary.jpg',
                    'overview'    => ['Placement by short diagnostic.'],
                    'highlights'  => ['Growth mindset', 'Weekly feedback'],
                    'format'      => 'Twice weekly.',
                ],
            ],
            'faqs' => [],
            'trust' => [
                'headline' => 'Need the full program list?',
                'body'     => 'Our team can share current courses, fees, and start dates. Use Contact or Online admission—we reply within business hours.',
            ],
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

    // onlineAdmission
    public function onlineAdmission()
    {
        $data = $this->repo->result();
        $data['religions']= $this->religionRepo->all();
        $data['genders']  = $this->genderRepo->all();
        $data['shifts']  = $this->shift_repo->all();
        $data['setting']  = $this->admission_setting_repo->getIsShowByType('online_admission');
        return view('frontend.online-admission', compact('data'));
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

    public function storeOnlineAdmission(Request $request) {

        $admission = $this->repo->onlineAdmission($request);
        $fees = $this->repo->onlineAdmissionFees($admission->session_id, $admission->classes_id , $admission->section_id);
        $payment_setting = $this->admission_setting_repo->getOneByFied('admission_payment');

        if($admission && $fees && $payment_setting->is_show == 1){
            return redirect()->route('frontend.online-admission-fees',[$admission->reference_no , $admission->id])->with('message' , 'Admission Inform submitted successfully , Please wait for school approval');
        }
        return redirect()->back()->with('message' , 'Admission Inform submitted successfully, Please wait for school approval');

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