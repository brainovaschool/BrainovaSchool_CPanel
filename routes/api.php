<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SettingAPIController;
use App\Http\Controllers\Api\Student\FeeAPIController;
use App\Http\Controllers\Api\NotificationApiController;
use App\Http\Controllers\Api\Student\BookAPIController;
use App\Http\Controllers\Api\Student\HomeAPIController;
use App\Http\Controllers\Api\Student\EventAPIController;
use Modules\LiveChat\Http\Controllers\MessageController;
use App\Http\Controllers\Api\Parent\StudentAPIController;
use App\Http\Controllers\Api\Student\ResultAPIController;
use App\Http\Controllers\Api\Student\ProfileAPIController;
use App\Http\Controllers\Api\Student\SubjectAPIController;
use App\Http\Controllers\Api\Teacher\TeacherApiController;
use App\Http\Controllers\Api\Student\ExamTypeAPIController;
use App\Http\Controllers\Api\Student\HomeworkAPIController;
use App\Http\Controllers\Api\Student\AttendanceAPIController;
use App\Http\Controllers\Api\Student\IssuedBookAPIController;
use App\Http\Controllers\Api\Student\OnlineExamAPIController;
use App\Http\Controllers\Api\Student\SchoolInfoAPIController;
use App\Http\Controllers\Api\Teacher\TeacherReportController;
use App\Http\Controllers\Api\Parent\ParentTransportController;
use App\Http\Controllers\Api\Student\ExamRoutineAPIController;
use App\Http\Controllers\Api\Student\StudentInfoAPIController;
use App\Http\Controllers\Api\Teacher\TeacherRoutineController;
use App\Http\Controllers\Api\Student\ClassRoutineAPIController;
use App\Http\Controllers\Api\Teacher\TeacherLiveChatController;
use App\Http\Controllers\Api\Parent\ParentLivechatAPIController;
use App\Http\Controllers\Api\Student\StudentTransportController;
use App\Http\Controllers\Api\Teacher\TeacherOnlineExamController;
use App\Http\Controllers\Api\Student\StudentLivechatAPIController;
use App\Http\Controllers\Api\Teacher\TeacherHomeworkApiController;
use App\Http\Controllers\Api\Teacher\TeacherStudentInfoController;
use App\Http\Controllers\Api\Teacher\TeacherLiveClassApiController;
use App\Http\Controllers\Api\Instructor\InstructorLivechatAPIController;
use App\Http\Controllers\Api\Parent\HomeworkAPIController as ParentHomeworkAPIController;


// Deliberately OUTSIDE saasApiMiddleware()'s group below: that group applies
// InitializeTenancyByDomain + PreventAccessFromCentralDomains once APP_SAAS
// is on, so a request to these from the main domain (now itself a tenant)
// would get switched into THAT tenant's own database -- wrong for an
// endpoint that needs to see every school, and for one that runs before a
// school has even been picked. Plain 'api' middleware only -> always runs
// in the central context, regardless of which domain it's reached from.
Route::middleware('api')->group(function () {
    // Public: lists every school for the mobile app's "Select a School" screen.
    Route::get('/saas/get-all-school', [App\Http\Controllers\Api\SchoolDirectoryController::class, 'index']);
    // Public: the access-code check gating actually proceeding into a school.
    Route::post('/saas/verify-school-code', [App\Http\Controllers\Api\SchoolDirectoryController::class, 'verifyCode']);
});

Route::middleware(saasApiMiddleware())->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'loginRoleWise']);
    Route::post('/login-team', [AuthController::class, 'loginTeam']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    // Public (no login needed) -- app name + logo for the splash/login
    // screens, shown before anyone has signed in.
    Route::get('/app-branding', [App\Http\Controllers\Api\AppBrandingController::class, 'index']);



    Route::group(['middleware' => ['auth:sanctum']], function () {
        Route::post('update-password', [AuthController::class, 'updatePassword']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('/notifications', [NotificationApiController::class, 'notifications']);

        Route::group(['prefix' => 'student'], function () {
            Route::get('menus', [StudentAPIController::class, 'menuList']);
            Route::get('profile', [ProfileAPIController::class, 'profile']);
            Route::post('profile', [ProfileAPIController::class, 'update']);
            Route::get('student-info', StudentInfoAPIController::class);
            Route::get('home', [HomeAPIController::class,'menuList']);
            Route::get('subjects', SubjectAPIController::class);
            Route::get('class-routines', [ClassRoutineAPIController::class, 'index']);
            Route::get('exam-types', ExamTypeAPIController::class);
            Route::get('exam-routines', ExamRoutineAPIController::class);
            Route::get('fees/payment-gateways', [FeeAPIController::class, 'paymentGateways']);
            Route::get('fee-groups', [FeeAPIController::class, 'feeGroups']);
            Route::get('fees/{fee_group_id}', [FeeAPIController::class, 'fees']);
            Route::get('events', [EventAPIController::class, 'index']);
            Route::get('events/{id}', [EventAPIController::class, 'show']);
            Route::get('dashboard-events', [EventAPIController::class, 'dashboardEvents']);
            Route::get('online-exam-types', [OnlineExamAPIController::class, 'onlineExamTypes']);
            Route::get('online-exams', [OnlineExamAPIController::class, 'index']);
            Route::get('online-exam-questions/{online_exam_id}', [OnlineExamAPIController::class, 'onlineExamQuestions']);
            Route::post('online-exams/{online_exam_id}', [OnlineExamAPIController::class, 'store']);
            Route::get('online-exams/result/{online_exam_id}', [OnlineExamAPIController::class, 'result']);
            Route::get('results', [ResultAPIController::class, 'index']);
            Route::get('attendances', [AttendanceAPIController::class, 'index']);
            Route::get('attendance-progress', [AttendanceAPIController::class, 'attendanceProgress']);
            Route::get('yearly-present-percentage', [AttendanceAPIController::class, 'yearlyPresentPercentage']);
            Route::get('school-info', [SchoolInfoAPIController::class, 'index']);
            Route::get('current-session-teachers', [TeacherAPIController::class, 'currentSessionTeachers']);
            Route::get('teachers/{id}', [TeacherAPIController::class, 'show']);
            Route::get('teachers/assign-subjects/{id}', [TeacherAPIController::class, 'assignSubjects']);
            Route::get('homework', [HomeworkAPIController::class, 'index']);
            Route::post('homework/submit/', [HomeworkAPIController::class, 'submit']);
            Route::get('book-list/', [BookAPIController::class, 'index']);
            Route::get('issued-book-list', [IssuedBookAPIController::class, 'index']);

            Route::middleware('student')->controller(StudentLivechatAPIController::class)->prefix('livechat')->group(function () {
                Route::get('chat-list', 'chatList');
                Route::get('chat-conversions/{id}', 'chatConversions');
                Route::post('send-message', 'sendMessage');
            });

            // Student transport route
            Route::middleware('student')->controller(StudentTransportController::class)->group(function () {
                Route::get('/duty-schedules',    'dutySchedule');
                Route::get('/enrollment-report', 'enrollmentReport');
                Route::get('/live-track',        'liveTrack');
                Route::post('/feedback',         'feedback');
            });
        });


        Route::group(['prefix' => 'parent', 'middleware' => 'guardian'], function () {
            Route::get('menus', [StudentAPIController::class, 'guardianMenuList']);
            Route::get('students', [StudentAPIController::class, 'index']);
            Route::get('homework', [ParentHomeworkAPIController::class, 'index']);
            Route::get('book-list/', [BookAPIController::class, 'index']);
            Route::get('issued-book-list', [IssuedBookAPIController::class, 'index']);

            Route::prefix('livechat')->controller(ParentLivechatAPIController::class)->group(function(){
                Route::get('chat-list', 'chatList');
                Route::get('chat-conversions/{id}', 'chatConversions');
                Route::post('send-message', 'sendMessage');
            });

            // Parent transport route
            Route::controller(ParentTransportController::class)->group(function () {
                Route::get('/duty-schedules',    'dutySchedule');
                Route::get('/enrollment-report', 'enrollmentReport');
                Route::get('/live-track',        'liveTrack');
                Route::post('/feedback',         'feedback');
            });
        });

        Route::group(['prefix' => 'instructor', 'middleware' => 'instructor'], function () {
            Route::prefix('livechat')->controller(InstructorLivechatAPIController::class)->group(function () {
                Route::get('chat-list', 'chatList');
                Route::get('chat-conversions/{id}', 'chatConversions');
                Route::post('send-message', 'sendMessage');
            });
        });

        Route::prefix('live-chat')->group(function () {
            Route::controller(MessageController::class)->group(function () {
                Route::get('message-read/{id}', 'messageRead');
            });
        });


        Route::group(['prefix' => 'teacher'], function () {
            Route::get('issued-book-list', [TeacherApiController::class, 'issuedBookAPI']);
            Route::get('menus', [TeacherApiController::class, 'menuList']);
            Route::post('profile', [TeacherApiController::class, 'prfileUpdate']);
            Route::get('classes', [TeacherApiController::class, 'classes']);
            Route::post('subject-by-class-section', [TeacherApiController::class, 'getSubjectList']);
            Route::get('section_by_class/{id}', [TeacherApiController::class, 'sectionByClassId']);
            Route::post('attendance-search-student', [TeacherApiController::class, 'searchStudent']);
            Route::post('attendance-store', [TeacherApiController::class, 'attendanceStore']);

            Route::get('gmeet-live-class', [TeacherLiveClassApiController::class, 'gmeetLiveClass']);
            Route::post('gmeet-live-class', [TeacherLiveClassApiController::class, 'gmeetLiveClassStore']);

            Route::group(['prefix' => 'homework'], function () {
                Route::get('/list', [TeacherHomeworkApiController::class, 'list']);
                Route::post('store', [TeacherHomeworkApiController::class, 'store']);
                Route::post('update', [TeacherHomeworkApiController::class, 'update']);
                Route::post('delete', [TeacherHomeworkApiController::class, 'delete']);
                Route::get('evaluate-students/{id}', [TeacherHomeworkApiController::class, 'evaluateStudents']);
                Route::post('evaluate-students-mark', [TeacherHomeworkApiController::class, 'evaluateStudentmark']);

            });

            Route::group(['prefix' => 'student'], function () {
                Route::get('/list', [TeacherStudentInfoController::class, 'studentList']);
                Route::get('details/{id}', [TeacherStudentInfoController::class, 'studentDetails']);
            });


            Route::group(['prefix' => 'routine'], function () {
                Route::get('class', [TeacherRoutineController::class, 'classRoutines']);
                Route::get('exam', [TeacherRoutineController::class, 'examRoutines']);
            });


            Route::group(['prefix' => 'online-exam'], function () {
                Route::get('types', [TeacherOnlineExamController::class, 'types']);
                Route::get('list', [TeacherOnlineExamController::class, 'list']);
                Route::get('questions/{id}', [TeacherOnlineExamController::class, 'questions']);
                 Route::get('results/{id}', [TeacherOnlineExamController::class, 'results']);
            });


            Route::group(['prefix' => 'report'], function () {
                Route::get('student-marksheet', [TeacherReportController::class, 'studentMarksheet']);
                Route::get('student-merit-list', [TeacherReportController::class, 'studentMeritList']);
                Route::get('student-progress-card', [TeacherReportController::class, 'studentProgressCard']);
                Route::get('monthly-attendance', [TeacherReportController::class, 'attendanceReport']);
            });


            Route::controller(TeacherLiveChatController::class)->prefix('livechat')->group(function () {
                Route::get('chat-list', 'chatList');
                Route::get('chat-conversions/{id}', 'chatConversions');
                Route::post('send-message', 'sendMessage');
            });



        });

        // Team Portal (internal staff tool: tasks, reels, work log) -- not
        // nested under 'teacher' since whoever has portal_access may have
        // logged in as Teacher or Staff; same PermissionCheck the website
        // uses, so only staff actually granted Team Portal access see this.
        Route::group(['prefix' => 'portal', 'middleware' => 'PermissionCheck:portal_access'], function () {
            Route::get('my-tasks',          [App\Http\Controllers\Api\Portal\PortalTaskController::class, 'myTasks']);
            Route::get('my-permissions',    [App\Http\Controllers\Api\Portal\PortalTaskController::class, 'myPermissions']);
            Route::get('reels',             [App\Http\Controllers\Api\Portal\PortalReelController::class, 'index']);
            Route::get('work-log',          [App\Http\Controllers\Api\Portal\PortalWorkLogController::class, 'today']);
            Route::post('work-log',         [App\Http\Controllers\Api\Portal\PortalWorkLogController::class, 'store']);

            // Assigning work to other people is a coordinator/manager
            // action on the website (PermissionCheck:portal_manage) -- the
            // mobile API was missing that same check entirely, so anyone
            // with plain portal_access could call these directly.
            Route::middleware('PermissionCheck:portal_manage')->group(function () {
                Route::get('assignable-staff', [App\Http\Controllers\Api\Portal\PortalTaskController::class, 'assignableStaff']);
                Route::post('tasks',           [App\Http\Controllers\Api\Portal\PortalTaskController::class, 'store']);
            });
        });

    });
});



// ==========================================
// BRAINOVA UNIVERSAL SCORING BRIDGE
// ==========================================
Route::post('/sync-e6-points', [App\Http\Controllers\Api\BrainovaE6ScoreController::class, 'syncScore']);
Route::post('/get-leaderboard', [App\Http\Controllers\Api\BrainovaE6ScoreController::class, 'getLeaderboard']); // <--- ADD THIS
Route::post('/sync-external-quiz', [App\Http\Controllers\Admin\HomeworkController::class, 'syncExternalQuiz']);
Route::get('/get-lms-ids', [App\Http\Controllers\Admin\HomeworkController::class, 'getInventoryIds']);
Route::post('/store-external-quiz', [App\Http\Controllers\Admin\HomeworkController::class, 'storeExternalQuiz']);
// ==========================================



