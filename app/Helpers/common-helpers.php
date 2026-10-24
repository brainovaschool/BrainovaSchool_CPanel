<?php

use Carbon\Carbon;
use App\Models\Upload;
use App\Models\Setting;
use Twilio\Rest\Client;
use App\Models\Currency;
use App\Models\Language;
use Illuminate\Support\Str;
use App\Models\Subscription;
use App\Models\SystemNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Intervention\Image\Facades\Image;
use App\Models\Examination\MarksGrade;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Modules\MainApp\Enums\PackagePaymentType;
use App\Models\Academic\SubjectAssignChildren;
use App\Models\Examination\ExaminationSettings;
use App\Models\StudentInfo\SessionClassStudent;
use App\Models\WebsiteSetup\OnlineAdmissionSetting;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
function getPagination($ITEM)
{
    return view('common.pagination', compact('ITEM'));
}


function setting($name)
{
    try {
        if ($name == 'currency_symbol') {
            $currencyCode = Setting::where('name', 'currency_code')->first()?->value;
            return Currency::where('code', $currencyCode)->first()?->symbol;
        }

        $setting_data = Setting::where('name', $name)->first();
        if ($setting_data) {
            return $setting_data->value;
        }

        return null;
    } catch (\Throwable $th) {
        return null;
    }
}

function settingLocale($name)
{
    $setting_data = Setting::where('name', $name)->first();
    if ($setting_data) {
        return @$setting_data->defaultTranslate->value;
    }

    return null;
}

function examSetting($name)
{
    $setting_data = ExaminationSettings::where('name', $name)->where('session_id', setting('session'))->first();
    if ($setting_data) {
        return $setting_data->value;
    }

    return null;
}


function findDirectionOfLang()
{
    $data = Language::where('code', Session::get('locale'))->select('direction')->first();
    return @$data->direction != null ? strtolower(@$data->direction) : '';
}

// for menu active
if (!function_exists('set_menu')) {
    function set_menu(array $path, $active = 'mm-active')
    {
        foreach ($path as $route) {
            if (Route::currentRouteName() == $route) {
                return $active;
            }
        }
        return (request()->is($path)) ? $active : '';
        // return call_user_func_array('Request::is', (array) $path) ? $active : '';
    }
}

// for  submenu list item active
if (!function_exists('menu_active_by_route')) {
    function menu_active_by_route($route)
    {
        return request()->routeIs($route) ? 'mm-show' : 'in-active';
    }
}


// get upload path
if (!function_exists('uploadPath')) {
    function uploadPath($id)
    {
        $row = Upload::find($id);
        return $row->path;
    }
}

if (!function_exists('calculateTax')) {
    function calculateTax($amount)
    {
        static $settings = null;

        if ($settings === null) {
            $settings = Setting::whereIn('name', ['tax_min_amount', 'tax_percentage'])
                ->pluck('value', 'name');
        }

        $tax = 0;

        if (isset($settings['tax_min_amount'], $settings['tax_percentage'])) {
            if ($amount >= $settings['tax_min_amount']) {
                $tax = ($settings['tax_percentage'] / 100) * $amount;
            }
        }

        return $tax;
    }
}

if (!function_exists('calculateDiscount')) {
    function calculateDiscount($amount, $percent)
    {
        return ($amount * $percent) / 100;
    }
}

if (!function_exists('dashboard_feature_enabled')) {
    /**
     * Website Setup > Dashboard Features lets the admin show/hide individual
     * student/teacher/parent dashboard sections. Loaded once per request
     * (static cache) since a single page checks many of these. A key nobody
     * has configured yet — or one an admin deleted from the management list —
     * stays visible by default (fail-open), so this toggle system can never
     * silently break a dashboard section that isn't in the catalogue.
     */
    function dashboard_feature_enabled(string $portal, string $key): bool
    {
        static $flags = null;

        if ($flags === null) {
            $flags = [];
            try {
                foreach (\App\Models\WebsiteSetup\DashboardFeature::select('portal', 'feature_key', 'status')->get() as $row) {
                    $flags[$row->portal . '.' . $row->feature_key] = (int) $row->status;
                }
            } catch (\Throwable $e) {
                $flags = [];
            }
        }

        $lookup = $portal . '.' . $key;

        if (!array_key_exists($lookup, $flags)) {
            return true;
        }

        return $flags[$lookup] === \App\Enums\Status::ACTIVE;
    }
}

function ___($key = null, $replace = [], $locale = null)
{

    $input = explode('.', $key);
    $term = $input[1] ?? $key;
    $app_local = Session::get('locale') ?: 'bn';


    try {
        if (str_contains($key, '.')) {
            [$file_name, $trans_key] = explode('.', $key, 2); // allow for multiple dots
            $file_path = base_path('lang/' . $app_local . '/' . $file_name . '.json');
            if(env('APP_SAAS')) {
                $tenancy = tenant() ? tenant()->id : null;
                $file_path = base_path('lang/'. $tenancy. '/' . $app_local . '/' . $file_name . '.json');
            }
        }

        if (!file_exists($file_path)) {
            file_put_contents($file_path, json_encode([]));
            chmod($file_path, 0755);
        }

        $file_data = json_decode(file_get_contents($file_path), true) ?? [];
        if (!array_key_exists($trans_key, $file_data)) {
            // Transform: replace underscores and convert to title case
            $default_value = ucwords(str_replace('_', ' ', $trans_key));
            $file_data[$trans_key] = $default_value;
            file_put_contents($file_path, json_encode($file_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        return $file_data[$trans_key];
    } catch (\Exception $e) {
        return ucwords(str_replace('_', ' ', $term));
    }
}



// function ___($key = null, $replace = [], $locale = null)
// {

//     $input = explode('.', $key);
//     $file = $input[0];
//     $term = $input[1] ?? '';
//     $app_local = Session::get('locale') ? Session::get('locale') : 'en';
//     $file_path = base_path('lang/' . $app_local . '/' . $file . '.json');
//     $term = str_replace('_', ' ', $term);

//     if (!is_dir(dirname($file_path))) {
//         mkdir(dirname($file_path), 0777, true);
//     }
//     if (!file_exists($file_path)) {
//         $data = [];
//         file_put_contents($file_path, json_encode($data, JSON_UNESCAPED_UNICODE));
//     }

//     $jsonString = file_get_contents($file_path);
//     $data = json_decode($jsonString, true);

//     if (@$data[$term]) {
//         return $data[$term];
//     } else {
//         $data[$term] = $term;
//         file_put_contents($file_path, json_encode($data, JSON_UNESCAPED_UNICODE));
//     }

//     return $term;
// }

// global thumbnails
if (!function_exists('globalAsset')) {
    function globalAsset($path, $default_image = null)
    {
        if ($path == "") {
            return url("backend/uploads/default-images/$default_image");
        } else {
            try {
                if (setting('file_system') == "s3" && Storage::disk('s3')->exists($path) && $path != "") {
                    return Storage::disk('s3')->url($path);
                } else if (setting('file_system') == "local" && file_exists(@$path)) {
                    return url($path);
                } else {
                    if ($default_image == null) {
                        return url('backend/uploads/default-images/user2.jpg');
                    } else {
                        return url("backend/uploads/default-images/$default_image");
                    }
                }
            } catch (\Exception $c) {
                return url("backend/uploads/default-images/$default_image");
            }
        }
    }
}


// Permission check — uses role + user rows together (see authEffectivePermissions).
if (!function_exists('authEffectivePermissions')) {
    /**
     * Permissions granted to the current user: intersection of the role template and
     * the user's stored list. That way tightening a role takes effect even if
     * users.permissions was copied long ago and never updated.
     */
    function authEffectivePermissions(): array
    {
        if (!Auth::check()) {
            return [];
        }

        static $cache = null;
        static $cacheKey = null;

        $request = function_exists('request') ? request() : null;
        $requestPart = $request ? spl_object_id($request) : 'cli';
        $user = Auth::user();
        $key = $requestPart . ':' . $user->getKey();

        if ($cacheKey === $key && is_array($cache)) {
            return $cache;
        }

        $user->loadMissing('role');

        $rolePerms = $user->role?->permissions;
        $rolePerms = is_array($rolePerms) ? $rolePerms : [];

        $userPerms = $user->permissions;
        if (!is_array($userPerms) || $userPerms === []) {
            $cacheKey = $key;

            return $cache = $rolePerms;
        }

        $cacheKey = $key;

        return $cache = array_values(array_intersect($rolePerms, $userPerms));
    }
}

if (!function_exists('authHasPermission')) {
    function authHasPermission($keyword): bool
    {
        if (!Auth::check()) {
            return false;
        }
        if ((int) Auth::user()->role_id === 1) {
            return true;
        }

        return in_array($keyword, authEffectivePermissions(), true);
    }
}

if (!function_exists('hasPermission')) {
    function hasPermission($keyword)
    {
        return authHasPermission($keyword);
    }
}

if (!function_exists('schoolSettingsBlockedRoleSlugs')) {
    /**
     * Role slugs that must not access school CMS / system settings (routes + sidebar).
     * Includes "gurdian" (DB seeder spelling) and "guardian" if renamed.
     *
     * @return list<string>
     */
    function schoolSettingsBlockedRoleSlugs(): array
    {
        return ['teacher', 'student', 'gurdian', 'guardian'];
    }
}

if (!function_exists('cannotAccessSchoolSettings')) {
    /**
     * True for Teacher, Student, or Parent/Guardian roles (sidebar + policy checks).
     */
    function cannotAccessSchoolSettings(): bool
    {
        if (!Auth::check()) {
            return false;
        }

        Auth::user()->loadMissing('role');
        $slug = Auth::user()->role?->slug;

        return $slug !== null && $slug !== '' && in_array($slug, schoolSettingsBlockedRoleSlugs(), true);
    }
}


// Date format
if (!function_exists('dateFormat')) {
    function dateFormat($keyword)
    {
        return date('d M Y', strtotime($keyword));
    }
}
if (!function_exists('timeFormat')) {
    function timeFormat($keyword)
    {
        return date('g:i A', strtotime($keyword));
    }
}
// Mark grade
if (!function_exists('markGrade')) {
    function markGrade($data)
    {
        $result = MarksGrade::where('session_id', setting('session'))->where('percent_upto', '>=', $data)->where('percent_from', '<=', $data)->first();
        if ($result) {
            return $result->name;
        }
        return '...';
    }
}

if (!function_exists('userTheme')) {
    function userTheme()
    {
        $session_theme = Session::get('user_theme');

        if (isset($session_theme)) {
            return $session_theme;
        } else {
            return 'default-theme';
        }
    }
}

if (!function_exists('leadingZero')) {
    function withLeadingZero($number)
    {

        // $strNumber = $number;
        // if(strlen($strNumber) < 10){
        //     return $strNumber;
        // }

        return $number;
    }
}


if (!function_exists('setEnvironmentValue')) {
    function setEnvironmentValue($envKey, $envValue)
    {
        $envFile = app()->environmentFilePath();
        $str = file_get_contents($envFile);

        $str .= "\n"; // In case the searched variable is in the last line without \n
        $keyPosition = strpos($str, "{$envKey}=");
        $endOfLinePosition = strpos($str, PHP_EOL, $keyPosition);
        $oldLine = substr($str, $keyPosition, $endOfLinePosition - $keyPosition);
        $str = str_replace($oldLine, "{$envKey}={$envValue}", $str);
        $str = substr($str, 0, -1);

        $fp = fopen($envFile, 'w');
        fwrite($fp, $str);
        fclose($fp);
    }
}

if (!function_exists('s3Upload')) {
    function s3Upload($directory, $file)
    {
        $directory = 'public/' . $directory;
        return Storage::disk('s3')->put($directory, $file, 'public');
    }
}

if (!function_exists('s3ObjectCheck')) {
    function s3ObjectCheck($path)
    {
        return Storage::disk('s3')->exists($path);
    }
}


if (!function_exists('include_route_files')) {
    /**
     * Loops through a folder and requires all PHP files
     * Searches sub-directories as well.
     *
     * @param $folder
     */
    function include_route_files($folder)
    {
        try {
            $rdi = new RecursiveDirectoryIterator($folder);
            $it = new RecursiveIteratorIterator($rdi);

            while ($it->valid()) {
                if (!$it->isDot() && $it->isFile() && $it->isReadable() && $it->current()->getExtension() === 'php') {
                    require $it->key();
                }

                $it->next();
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    function getAllDaysInMonth($year, $month)
    {
        $daysInMonth = Carbon::create($year, $month)->daysInMonth;
        $days = [];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::create($year, $month, $day);
            $days[] = $date->format('Y-m-d');
        }

        return $days;
    }


    function getSubdomainName()
    {
        $parsedUrl = parse_url(url()->full());
        $hostParts = explode('.', $parsedUrl['host']);
        return $hostParts;
    }
}

if (!function_exists('saasMiddleware')) {
    function saasMiddleware()
    {
        if (env('APP_SAAS')) {
            return [
                'web',
                InitializeTenancyByDomain::class,
                PreventAccessFromCentralDomains::class,
            ];
        }

        return [
            'web'
        ];
    }
}

if (!function_exists('saasApiMiddleware')) {
    function saasApiMiddleware()
    {

        if (env('APP_SAAS')) {
            return [
                'api',
                InitializeTenancyByDomain::class,
                PreventAccessFromCentralDomains::class,
            ];
        }

        return [
            'api'
        ];
    }
}


function activeSubscriptionStudentLimit()
{
    if (env('APP_SAAS')) {
        return cache()->rememberForever('activeSubscriptionStudentLimit', function () {
            $subscription = Subscription::active()->first();

            // No active subscription row, or a prepaid one seeded without a
            // student_limit (nullable column — happens when the tenant was
            // provisioned without that value ever being set), must mean
            // "no real limit to enforce", not zero. Before this fix,
            // `$count >= null` evaluated true in PHP's loose comparison for
            // ANY student count including zero, so new installs could never
            // create even their very first student.
            if ($subscription && $subscription->payment_type == PackagePaymentType::PREPAID && $subscription->student_limit) {
                return (int) $subscription->student_limit;
            }

            return PHP_INT_MAX;
        });
    }

    return PHP_INT_MAX;
}

function activeSubscriptionStaffLimit()
{
    if (env('APP_SAAS')) {
        return cache()->rememberForever('activeSubscriptionStaffLimit', function () {
            $subscription = Subscription::active()->first();

            // See activeSubscriptionStudentLimit() above — same fix.
            if ($subscription && $subscription->payment_type == PackagePaymentType::PREPAID && $subscription->staff_limit) {
                return (int) $subscription->staff_limit;
            }

            return PHP_INT_MAX;
        });
    }

    return PHP_INT_MAX;
}

function activeSubscriptionExpiryDate()
{
    if (env('APP_SAAS')) {
        return cache()->rememberForever('activeSubscriptionExpiryDate', function () {
            $subscription = Subscription::active()->first();
            if ($subscription) {
                if ($subscription->expiry_date) { // expiry gate null menas this is lifetime package
                    if (date('Y-m-d') <= date('Y-m-d', strtotime($subscription->expiry_date))) {
                        return true;
                    }
                    return false;
                }
                return true;
            }
            return false;
        });
    }
    return true;
}

function activeSubscriptionFeatures()
{
    if (env('APP_SAAS')) {
        return cache()->rememberForever('activeSubscriptionFeatures', function () {
            return Subscription::active()->first()?->features;
        });
    }

    return null;
}


// Feature check
if (!function_exists('hasFeature')) {
    function hasFeature($keyword)
    {
        if (!env('APP_SAAS')) {
            return true;
        }
        // if (in_array($keyword, Setting('features') ?? [])) {
        if (in_array($keyword, activeSubscriptionFeatures() ?? [])) {
            return true;
        }
        return false;
    }
}

if (!function_exists('decisionAllows')) {
    /** Whether the current viewer should see $field (e.g.
     *  'show_replacement_day') for a record whose subject is
     *  $subjectRoleId, per the admin-configured Decisions rules
     *  (Website Setup > Decisions). See DecisionRuleRepository::allows(). */
    function decisionAllows(string $category, ?int $subjectRoleId, string $field): bool
    {
        return \App\Repositories\WebsiteSetup\DecisionRuleRepository::allows($category, $subjectRoleId, $field);
    }
}


/** The student's one PRIMARY (main/grade) enrollment — a student may
 *  also hold secondary short-course enrollments, deliberately excluded
 *  here so this stays "the" class for the ~25 call sites (mostly the
 *  mobile app) that already assume a single class. Ordered by latest id
 *  as a safety net; application code never allows two primary rows for
 *  the same student to exist at once. */
function sessionClassStudent()
{

    $sesionClassStudent = SessionClassStudent::query()
        ->where('student_id', request()->filled('student_id') ? request('student_id') : @auth()->user()->student->id)
        ->primary()
        ->latest('id')
        ->first();

    if ($sesionClassStudent) {
        return $sesionClassStudent;
    }

    if (sessionClassStudentByParent()) {
        return sessionClassStudentByParent();
    }

    if (isStudentAccessInAPI()) {
        $data = SessionClassStudent::query()
            ->where('student_id', request()->filled('student_id') ? request('student_id') : @auth()->user()->student->id)
            ->primary()
            ->whereHas('session', function ($q) {
                $q->whereYear('start_date', '<=', date('Y'))
                    ->whereYear('end_date', '>=', date('Y'));
            })->latest('id')->first();

        return $data;
    }

    return null;
}


function getDayNum($date)
{
    $day = Str::lower(Carbon::createFromFormat('Y-m-d', $date)->format('l'));

    switch ($day) {
        case $day == 'saturday':
            return 1;
            break;
        case $day == 'sunday':
            return 2;
            break;
        case $day == 'monday':
            return 3;
            break;
        case $day == 'tuesday':
            return 4;
            break;
        case $day == 'wednesday':
            return 5;
            break;
        case $day == 'thursday':
            return 6;
            break;
        default:
            return 7;
    }
}


function loadPayPalCredentials()
{
    if (Str::lower(Setting('paypal_payment_mode')) == 'sandbox') {
        \Config::set('paypal.sandbox.username', Setting('paypal_sandbox_api_username'));
        \Config::set('paypal.sandbox.password', Setting('paypal_sandbox_api_password'));
        \Config::set('paypal.sandbox.secret', Setting('paypal_sandbox_api_secret'));
        \Config::set('paypal.sandbox.certificate', Setting('paypal_sandbox_api_certificate'));
    } elseif (Str::lower(Setting('paypal_payment_mode')) == 'live') {
        \Config::set('paypal.live.username', Setting('paypal_live_api_username'));
        \Config::set('paypal.live.password', Setting('paypal_live_api_password'));
        \Config::set('paypal.live.secret', Setting('paypal_live_api_secret'));
        \Config::set('paypal.live.certificate', Setting('paypal_live_api_certificate'));
    }
}


function teacherSubjects()
{
    return SubjectAssignChildren::with('subject')
        ->when(Auth::user()->role_id == 5, function ($query) {
            return $query->where('staff_id', Auth::user()->staff->id);
        })
        ->pluck('subject_id')
        ->toArray();
}

if (!function_exists('isHomeworkFilterAdmin')) {
    /**
     * Super Admin (1) and Admin (2): see all classes/sections/subjects on homework screens.
     */
    function isHomeworkFilterAdmin(): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $rid = (int) Auth::user()->role_id;

        return $rid === \App\Enums\RoleEnum::SUPERADMIN || $rid === \App\Enums\RoleEnum::ADMIN;
    }
}



if (!function_exists('get_base_url')) {
    function get_base_url($subDomainKey = null)
    {
        $scheme = env('APP_HTTPS') ? 'https://' : 'http://';
        $mainDomain = env('APP_MAIN_APP_URL'); // e.g. example.com

        if ($subDomainKey) {
            return $scheme . $subDomainKey . '.' . $mainDomain;
        }

        return $scheme . $mainDomain;
    }
}




if (!function_exists('getAttendanceType')) {

    function getAttendanceType($type)
    {
        if ($type == 1) {
            return 'PRESENT';
        } elseif ($type == 2) {
            return 'LATE';
        } elseif ($type == 3) {
            return 'ABSENT';
        } elseif ($type == 4) {
            return 'HALFDAY';
        } else {
            return '';
        }
    }
}


if (!function_exists('send_web_notification')) {
    function send_web_notification($title, $message, $reciever_id, $url = null)
    {
        try {
            $notification = new SystemNotification();
            $notification->title = $title;
            $notification->message = $message;
            $notification->reciver_id = $reciever_id;
            $notification->url = $url;
            $notification->save();
        } catch (\Throwable $th) {
            Log::info('NOtification store::' . $th);
        }
    }
}

if (!function_exists('send_message')) {
    function send_message_twillo($message, $recipients)
    {
        Log::info('To Number ::' . $recipients . 'Message::  ' . $message);
        try {
            $sid = setting('twilio_account_sid');
            $token = setting('twilio_auth_token');
            $twilio_number = setting('twilio_phone_number');

            if ($sid && $token && $twilio_number) {
                $twilio = new Client($sid, $token);
                return $twilio->messages
                    ->create(
                        $recipients,
                        [
                            "body" => $message,
                            "from" => $twilio_number
                        ]
                    );
            }
        } catch (\Throwable $th) {
            Log::info('Twillo Msg Error' . $th->getMessage());
        }
    }
}


if (!function_exists('send_flutter_notification')) {
    function send_flutter_notification($title, $message, $img = null)
    {

        try {
            $url = 'https://fcm.googleapis.com/fcm/send';
            $dataArr = array('click_action' => 'FLUTTER_NOTIFICATION_CLICK', 'status' => "done");
            $notification = array('title' => $title, 'text' => $message, 'image' => $img, 'sound' => 'default', 'badge' => '1',);
            $arrayToSend = array('notification' => $notification, 'data' => $dataArr, 'priority' => 'high');
            $fields = json_encode($arrayToSend);
            $headers = array(
                'Authorization: key=' . setting('FCM_SECRET_KEY'),
                'Content-Type: application/json'
            );
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
            $result = curl_exec($ch);
            curl_close($ch);
        } catch (\Throwable $th) {
            Log::info('Flutter Push Msg Error' . $th->getMessage());
        }
    }
}

function getAttendanceType($type)
{
    if ($type == 1) {
        return 'PRESENT';
    } elseif ($type == 2) {
        return 'LATE';
    } elseif ($type == 3) {
        return 'ABSENT';
    } elseif ($type == 4) {
        return 'HALFDAY';
    } else {
        return '';
    }
}


function send_web_notification($title, $message, $reciever_id, $url = null)
{
    try {
        $notification = new SystemNotification();
        $notification->title = $title;
        $notification->message = $message;
        $notification->reciver_id = $reciever_id;
        $notification->url = $url;
        $notification->save();
    } catch (\Throwable $th) {
        Log::info('NOtification store::' . $th);
        //   Log::info($th->getMessage());
    }
}

if (!function_exists('hasModule')) {
    function hasModule($name): bool
    {
        $filePath = base_path('modules_statuses.json');
        $statuses = json_decode(file_get_contents($filePath), true);
        if (isset($statuses[$name])) {
            $isModuleEnabled = $statuses[$name];
            if ($isModuleEnabled) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}

if (!function_exists('isSuperAdmin')) {
    function isSuperAdmin()
    {
        $role = auth()->user()?->role_id;
        if ($role == \App\Enums\RoleEnum::SUPERADMIN) {
            return true;
        }
        return false;
    }
}


if (!function_exists('online_admission_programs')) {
    function online_admission_programs(): array
    {
        $programs = config('online_admission.programs');

        if (is_array($programs)) {
            return $programs;
        }

        // Kept in sync with config/online_admission.php — used only if that
        // config value fails to load (e.g. a stale config cache on the host).
        return [
            'Homeschooling',
            'Academic Support',
            'Electives & Enrichment',
            'Social Clubs',
        ];
    }
}

if (!function_exists('youtube_embed_src')) {
    /** Turns any YouTube URL shape (watch/short/share link) into an
     *  embeddable iframe src, or null if it isn't a recognisable YouTube
     *  link. Used anywhere a single admin-pasted YouTube link needs to
     *  become a video embed without pulling in the full multi-platform
     *  HomeVideo resolver. */
    function youtube_embed_src(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }

        if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([A-Za-z0-9_-]{6,})/i', $url, $m)) {
            return "https://www.youtube-nocookie.com/embed/{$m[1]}?rel=0";
        }

        return null;
    }
}

if (!function_exists('matchAnyClassSectionPair')) {
    /** Constrains a query to rows whose $classCol/$sectionCol match ANY
     *  one of the given (classes_id, section_id) pairs — used wherever
     *  homework/exam/attendance visibility must cover every class a
     *  student is enrolled in (their main class plus any short courses),
     *  not just one. An empty $pairs list matches nothing, never
     *  everything — a student with no resolvable enrollment should see
     *  no homework/exams, not all of them. */
    function matchAnyClassSectionPair($query, $pairs, string $classCol = 'classes_id', string $sectionCol = 'section_id')
    {
        $pairs = collect($pairs);

        if ($pairs->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function ($q) use ($pairs, $classCol, $sectionCol) {
            foreach ($pairs as $pair) {
                $q->orWhere(function ($qq) use ($pair, $classCol, $sectionCol) {
                    $qq->where($classCol, $pair->classes_id)->where($sectionCol, $pair->section_id);
                });
            }
        });
    }
}

if (!function_exists('admission_fields')) {
    function admission_fields()
    {
        // dd(OnlineAdmissionSetting::where('type', 'online_admission')->where('is_show',1)->get()->pluck('field')->values(),2);
        try {
            if (Cache::has('online_admission_field_is_show') && Cache::get('online_admission_field_is_show')) {
                return Cache::get('online_admission_field_is_show');
            }
            return Cache::rememberForever('online_admission_setting', function () {
                return OnlineAdmissionSetting::where('type', 'online_admission')->where('is_show', 1)->get()->pluck(['field'])->toArray();
            });
        } catch (\Throwable $th) {
            return [];
        }
    }
}

if (!function_exists('is_show')) {
    function is_show($field)
    {
        try {
            $field_array = admission_fields();
            return in_array($field, $field_array);
        } catch (\Throwable $th) {
            dd($th);
            return false;
        }
    }
}

if (!function_exists('is_required')) {
    function is_required($field)
    {
        try {
            $field_array = admission_required_fields();
            return in_array($field, $field_array);
        } catch (\Throwable $th) {
            return false;
        }
    }
}

if (!function_exists('admission_required_fields')) {
    function admission_required_fields()
    {
        try {
            if (Cache::has('online_admission_field_is_require') && Cache::get('online_admission_field_is_require')) {
                return Cache::get('online_admission_field_is_require');
            }
            return Cache::rememberForever('online_admission_field_is_require', function () {
                return OnlineAdmissionSetting::where('type', 'online_admission')->where('is_required', 1)->get()->pluck(['field'])->toArray();
            });
        } catch (\Throwable $th) {
            return [];
        }
    }
}


if (!function_exists('app_translate')) {
    function app_translate()
    {
        try {
            return env('APP_TRANSLATE');
        } catch (\Throwable $th) {
            return false;
        }
    }
}


if (!function_exists('isParentUserAccessStudentInAPI')) {
    function isParentUserAccessStudentInAPI()
    {
        return auth()->check() &&
            @auth()->user()->role_id == 7 &&
            request()->filled('student_id')
            ? true : false;
    }
}


if (!function_exists('isStudentAccessInAPI')) {
    function isStudentAccessInAPI()
    {
        return !isParentUserAccessStudentInAPI() &&
            (
                (auth()->check() && @auth()->user()->role_id == 6) ||
                request()->filled('student_id')
            )
            ? true : false;
    }
}


if (!function_exists('sessionClassStudentByParent')) {
    function sessionClassStudentByParent()
    {

        return SessionClassStudent::query()
            ->where('student_id', request('student_id'))
            ->whereHas('student', fn($q) => $q->where('parent_guardian_id', @auth()->user()->parent->id))
            ->whereHas('session', function ($q) {
                $q->whereYear('start_date', '<=', date('Y'))
                    ->whereYear('end_date', '>=', date('Y'));
            })
            ->primary()
            ->latest('id')
            ->first();
    }
}

// encrypt id
if (!function_exists('encryptFunction')) {
    function encryptFunction($number = null)
    {

        return openssl_encrypt($number, "AES-128-CTR", "CodeSpeedyKeybj54HH", 0, '8565825542115032');
    }
};

// decrypt id
if (!function_exists('decryptFunction')) {
    function decryptFunction($encrypted = null)
    {
        return openssl_decrypt($encrypted, "AES-128-CTR", "CodeSpeedyKeybj54HH", 0, '8565825542115032');
    }
};


if (!function_exists('humanReadableDate')) {
    function humanReadableDate($date)
    {
        $date = Carbon::parse($date);

        // Check if the date is within one day
        if ($date->diffInDays() >= 1) {
            // Show in a standard date format (e.g., 'Y-m-d H:i:s')
            $formattedDate = $date->format('jS F Y h:i A');
        } else {
            // Show human-readable time difference (e.g., '2 hours ago')
            $formattedDate = $date->diffForHumans();
        }

        return $formattedDate;
    }
};


if (!function_exists('saasTenantMigrationPaths')) {
    function saasTenantMigrationPaths()
    {
        $filePath = base_path('modules_statuses.json');
        $migrationPaths = [
            database_path('migrations/tenant') // Core tenant migrations
        ];

        if (file_exists($filePath)) {
            $json_content = file_get_contents($filePath);
            $modules = json_decode($json_content, true);

            // Exclude MainApp and Installer
            unset($modules["MainApp"]);
            unset($modules["Installer"]);

            // Loop through only enabled (true) modules
            foreach ($modules as $module => $status) {
                if ($status === true) {
                    $migrationPaths[] = base_path("Modules/$module/Database/Migrations");
                }
            }
        }

        return $migrationPaths;
    }
}


if (!function_exists('specialDiscountCalc')) {
    /**
     * Calculate discount amount based on discount setup
     *
     * @param float $fees_gross  // Original total fee amount
     * @param \App\Models\SpecialDiscount $special_disc // Discount setup model
     * @return float Final discount amount
     */
    function specialDiscountCalc(float $fees_gross, $special_disc): float
    {
        // Check eligibility based on gross fee
        if (!is_null($special_disc->min_eligible_amount) && $fees_gross < $special_disc->min_eligible_amount) {
            return 0;
        }

        if (!is_null($special_disc->max_eligible_amount) && $fees_gross > $special_disc->max_eligible_amount) {
            return 0;
        }

        // Calculate initial discount
        $discountAmount = 0;

        if ($special_disc->type === 'P') {
            $discountAmount = ($fees_gross * $special_disc->discount) / 100;
        } elseif ($special_disc->type === 'F') {
            $discountAmount = $special_disc->discount;
        }

        // Apply min and max discount limits
        if (!is_null($special_disc->min_discount_amount)) {
            $discountAmount = max($discountAmount, $special_disc->min_discount_amount);
        }

        if (!is_null($special_disc->max_discount_amount)) {
            $discountAmount = min($discountAmount, $special_disc->max_discount_amount);
        }

        return round($discountAmount, 2);
    }
}

if (!function_exists('createAvatarAndUpload')) {
    function createAvatarAndUpload($firstName, $lastName, $path = 'backend/uploads/users')
    {
        // Step 1: Generate avatar initials
        $initials = strtoupper(Str::substr($firstName, 0, 1) . Str::substr($lastName, 0, 1));

        // Step 2: Create image with Intervention
        $backgroundColor = '#17095f'; // Generate random hex color
        $image = Image::canvas(200, 200, $backgroundColor);
        $image->text($initials, 100, 100, function ($font) {
            $font->file(public_path('backend/assets/fonts/OpenSans-Bold.ttf')); // Ensure font exists
            $font->size(80);
            $font->color('#ffffff');
            $font->align('center');
            $font->valign('center');
        });

        // Step 3: Encode image and prepare file name
        $encodedImage = $image->encode('png');
        $filename     = time() . Str::random(10) . '.png';

        // Step 4: Prepare Upload record
        $upload       = new Upload();

        if (setting('file_system') === 's3') {
            // Upload to S3
            $filePath = $path . '/' . $filename;
            Storage::disk('s3')->put($filePath, (string) $encodedImage);
            if (Storage::disk('s3')->exists($filePath)) {
                $upload->path = $filePath;
            }
        } else {
            // Local storage
            if (!File::exists(public_path($path))) {
                File::makeDirectory(public_path($path), 0755, true);
            }
            $fullPath = public_path($path . '/' . $filename);
            file_put_contents($fullPath, $encodedImage);
            $upload->path = $path . '/' . $filename;
        }

        // Step 5: Save record and return ID
        $upload->save();
        return $upload->id;
    }
}

if (!function_exists('portalOwnsResponsibility')) {
    /** Team Portal (phase 8): true for portal_manage, or for the Staff
     *  currently holding the given Responsibility key (content/audience)
     *  — used in sidebar/view code where a full permission check isn't
     *  worth a controller round-trip. The controllers re-check this same
     *  thing server-side on every write, so this is purely for what to
     *  show, never the actual access control. */
    function portalOwnsResponsibility(string $key): bool
    {
        if (!Auth::check()) {
            return false;
        }
        if ((int) Auth::user()->role_id === 1 || authHasPermission('portal_manage')) {
            return true;
        }
        $staff = Auth::user()->staff;
        if (!$staff) {
            return false;
        }
        return in_array($staff->id, \App\Models\Portal\PortalResponsibility::ownerStaffIdsForKey($key), true);
    }
}

if (!function_exists('permission_category_map')) {
    /**
     * Permission attribute => display category, for the Roles / Change
     * Permission screens. Mirrors the top-level groupings used in the admin
     * sidebar (resources/views/backend/partials/sidebar.blade.php) so a
     * category here reads the same way it does in the menu. Any attribute
     * not listed falls back to "Other" rather than being dropped, so a
     * module/add-on's own permissions still show up somewhere.
     *
     * @return array<string, string>
     */
    function permission_category_map(): array
    {
        return [
            'dashboard' => 'Dashboard',
            'admission' => 'Online Admission',
            'admission_setting' => 'Online Admission',
            'student' => 'Student Info',
            'student_category' => 'Student Info',
            'promote_students' => 'Student Info',
            'disabled_students' => 'Student Info',
            'parent' => 'Student Info',
            'id_card' => 'Id Cards',
            'id_card_generate' => 'Id Cards',
            'certificate' => 'Certificates',
            'certificate_generate' => 'Certificates',
            'classes' => 'Academic',
            'section' => 'Academic',
            'shift' => 'Academic',
            'class_setup' => 'Academic',
            'subject' => 'Academic',
            'subject_assign' => 'Academic',
            'time_schedule' => 'Academic',
            'class_room' => 'Academic',
            'class_routine' => 'Routines',
            'exam_routine' => 'Routines',
            'attendance' => 'Attendance',
            'attendance_report' => 'Attendance',
            'attendance_teacher' => 'Attendance',
            'leave_type' => 'Leave',
            'leave_request' => 'Leave',
            'fees_group' => 'Fees',
            'fees_type' => 'Fees',
            'fees_master' => 'Fees',
            'fees_assign' => 'Fees',
            'fees_collect' => 'Fees',
            'discount_setup' => 'Fees',
            'fees_payment_proof' => 'Fees',
            'exam_type' => 'Examination',
            'marks_grade' => 'Examination',
            'exam_assign' => 'Examination',
            'marks_register' => 'Examination',
            'exam_setting' => 'Examination',
            'online_exam_type' => 'Online Examination',
            'question_group' => 'Online Examination',
            'question_bank' => 'Online Examination',
            'online_exam' => 'Online Examination',
            'class_content' => 'Teacher Tools',
            'portal' => 'Staff Workspace',
            'reel_studio' => 'Staff Workspace',
            'portal_employees' => 'Staff Workspace',
            'portal_my_tasks' => 'Staff Workspace',
            'portal_paid_tasks' => 'Staff Workspace',
            'portal_work_log' => 'Staff Workspace',
            'portal_my_responsibilities' => 'Staff Workspace',
            'portal_my_performance' => 'Staff Workspace',
            'portal_social_reels' => 'Staff Workspace',
            'portal_social_month_plan' => 'Staff Workspace',
            'portal_social_page_fixes' => 'Staff Workspace',
            'portal_manager_tasks' => 'Staff Workspace',
            'portal_settings' => 'Staff Workspace',
            'portal_team_attendance' => 'Staff Workspace',
            'portal_manager_work_logs' => 'Staff Workspace',
            'portal_responsibilities_manage' => 'Staff Workspace',
            'portal_analytics' => 'Staff Workspace',
            'portal_social_settings' => 'Staff Workspace',
            'portal_growth_pay' => 'Staff Workspace',
            'portal_reel_characters' => 'Staff Workspace',
            'portal_reel_settings' => 'Staff Workspace',
            'ai_help_teacher' => 'Teacher Tools',
            'skill_mastery_report' => 'Teacher Tools',
            'homework' => 'Homework',
            'notice_board' => 'Communication',
            'sms_mail_template' => 'Communication',
            'sms_mail' => 'Communication',
            'gmeet' => 'Live Class',
            'book_category' => 'Library',
            'book' => 'Library',
            'member' => 'Library',
            'member_category' => 'Library',
            'issue_book' => 'Library',
            'account_head' => 'Accounts',
            'income' => 'Accounts',
            'expense' => 'Accounts',
            'marksheet' => 'Reports',
            'merit_list' => 'Reports',
            'progress_card' => 'Reports',
            'due_fees' => 'Reports',
            'fees_collection' => 'Reports',
            'account' => 'Reports',
            'report_class_routine' => 'Reports',
            'report_exam_routine' => 'Reports',
            'language' => 'Language',
            'roles' => 'Staff Manage',
            'users' => 'Staff Manage',
            'department' => 'Staff Manage',
            'designation' => 'Staff Manage',
            'subscription' => 'Staff Manage',
            'decision_rule' => 'Staff Manage',
            'subscribe' => 'Subscribers & Messages',
            'contact_message' => 'Subscribers & Messages',
            'program_category' => 'Course Catalogue',
            'program_focus' => 'Course Catalogue',
            'program' => 'Course Catalogue',
            'testimonial' => 'Course Catalogue',
            'trial_slot' => 'Course Catalogue',
            'program_waitlist' => 'Course Catalogue',
            'skill' => 'Learning Engine',
            'dashboard_features' => 'Learning Engine',
            'avatar_item' => 'Learning Engine',
            'student_feature_access' => 'Learning Engine',
            'character_line' => 'Learning Engine',
            'mission' => 'Learning Engine',
            'watch_learn_tab' => 'Learning Engine',
            'watch_learn_template' => 'Learning Engine',
            'watch_learn_video' => 'Learning Engine',
            'knowledge_hub_page' => 'Learning Engine',
            'knowledge_hub_topic' => 'Learning Engine',
            'page' => 'Website Setup',
            'sections' => 'Website Setup',
            'slider' => 'Website Setup',
            'about' => 'Website Setup',
            'counter' => 'Website Setup',
            'contact_info' => 'Website Setup',
            'dep_contact' => 'Website Setup',
            'news' => 'Website Setup',
            'event' => 'Website Setup',
            'home_video' => 'Website Setup',
            'ai_helper' => 'Website Setup',
            'gallery_category' => 'Gallery',
            'gallery' => 'Gallery',
            'forums' => 'Forums',
            'forum_comment' => 'Forums',
            'memories' => 'Memories',
            'general_settings' => 'General Settings',
            'mobile_app_settings' => 'General Settings',
            'storage_settings' => 'General Settings',
            'task_schedules' => 'General Settings',
            'software_update' => 'General Settings',
            'recaptcha_settings' => 'General Settings',
            'payment_gateway_settings' => 'General Settings',
            'email_settings' => 'General Settings',
            'sms_settings' => 'General Settings',
            'genders' => 'Basic Data',
            'religions' => 'Basic Data',
            'blood_groups' => 'Basic Data',
            'sessions' => 'Basic Data',
            'tax_setup' => 'Basic Data',
        ];
    }
}

if (!function_exists('group_permissions_by_category')) {
    /**
     * Groups a Permission collection into ['Category' => Collection<Permission>],
     * ordered the same way categories first appear in permission_category_map()
     * (so the Roles/Change-Permission screens, the sidebar, and this grouping
     * all agree on the same order), with anything unmapped collected last
     * under "Other" instead of silently disappearing.
     *
     * @param  iterable<\App\Models\Permission>  $permissions
     * @return \Illuminate\Support\Collection<string, \Illuminate\Support\Collection>
     */
    function group_permissions_by_category($permissions)
    {
        $map = permission_category_map();
        $order = array_values(array_unique(array_merge(array_values($map), ['Other'])));

        $grouped = collect($permissions)->groupBy(
            fn ($permission) => $map[$permission->attribute] ?? 'Other'
        );

        return collect($order)
            ->mapWithKeys(fn ($category) => [$category => $grouped->get($category, collect())])
            ->filter(fn ($group) => $group->isNotEmpty());
    }
}

if (!function_exists('linkify')) {
    /**
     * Escapes free text and turns any http(s) URL inside it into a real,
     * clickable link — e.g. a task description or comment where someone
     * pasted a reference URL, which Blade's {{ }} alone renders as plain,
     * unclickable text. Safe to use with {!! !!}: the whole string is
     * HTML-escaped first, so the only markup introduced is the <a> tag
     * this function adds itself; nothing from the original text can break
     * out of it.
     */
    function linkify(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        $escaped = nl2br(e($text));

        return preg_replace_callback('/(https?:\/\/[^\s<]+)/i', function ($matches) {
            $url = rtrim($matches[1], '.,;:!?)');
            $trailing = substr($matches[1], strlen($url));
            return '<a href="' . $url . '" target="_blank" rel="noopener noreferrer nofollow">' . $url . '</a>' . $trailing;
        }, $escaped);
    }
}
