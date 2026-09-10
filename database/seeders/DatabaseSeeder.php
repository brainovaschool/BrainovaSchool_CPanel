<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\SearchSeeder;
use Database\Seeders\UploadSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Support\Facades\Log;
use Database\Seeders\FlagIconSeeder;
use Database\Seeders\LanguageSeeder;
use App\Models\Academic\TimeSchedule;
use Illuminate\Support\Facades\Cache;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\Staff\StaffSeeder;
use Database\Seeders\Fees\FeesTypeSeeder;
use Database\Seeders\Academic\ShiftSeeder;
use Database\Seeders\Fees\FeesGroupSeeder;
use App\Models\StudentInfo\StudentCategory;
use Database\Seeders\Accounts\IncomeSeeder;
use Database\Seeders\Fees\FeesMasterSeeder;
use Database\Seeders\SpecialDiscountSeeder;
use Database\Seeders\Academic\ClassesSeeder;
use Database\Seeders\Academic\SectionSeeder;
use Database\Seeders\Academic\SubjectSeeder;
use Database\Seeders\Accounts\ExpenseSeeder;
use Database\Seeders\Staff\DepartmentSeeder;
use Database\Seeders\Staff\DesignationSeeder;
use Database\Seeders\WebsiteSetup\NewsSeeder;
use Database\Seeders\WebsiteSetup\PageSeeder;
use Database\Seeders\WebsiteSetup\ProgramCatalogSeeder;
use Database\Seeders\Academic\ClassRoomSeeder;
use Database\Seeders\SystemNotificationSeeder;
use Database\Seeders\WebsiteSetup\AboutSeeder;
use Database\Seeders\WebsiteSetup\EventSeeder;
use Database\Seeders\Academic\ClassSetupSeeder;
use Database\Seeders\StudentInfo\StudentSeeder;
use Database\Seeders\WebsiteSetup\NoticeSeeder;
use Database\Seeders\WebsiteSetup\SliderSeeder;
use Database\Seeders\Accounts\AccountHeadSeeder;
use Database\Seeders\Examination\ExamTypeSeeder;
use Database\Seeders\Library\BookCategorySeeder;
use Database\Seeders\WebsiteSetup\CounterSeeder;
use Database\Seeders\WebsiteSetup\GallerySeeder;
use Database\Seeders\Academic\ClassRoutineSeeder;
use Database\Seeders\Academic\TimeScheduleSeeder;
use Database\Seeders\Examination\MarkGradeSeeder;
use Database\Seeders\Academic\SubjectAssignSeeder;
use Database\Seeders\Examination\ExamAssignSeeder;
use Database\Seeders\Examination\ExamRoutineSeeder;
use Database\Seeders\Examination\MarkRegisterSeeder;
use Database\Seeders\WebsiteSetup\ContactInfoSeeder;
use Modules\Forums\Database\Seeders\ForumPostSeeder;
use Database\Seeders\WebsiteSetup\PageSectionsSeeder;
use Database\Seeders\StudentInfo\ParentGuardianSeeder;
use Database\Seeders\StudentInfo\StudentCategorySeeder;
use Database\Seeders\Academic\ClassSetupChildrensSeeder;
use Database\Seeders\OnlineExamination\OnlineExamSeeder;
use Database\Seeders\WebsiteSetup\GalleryCategorySeeder;
use Modules\Forums\Database\Seeders\ForumsDatabaseSeeder;
use Database\Seeders\OnlineExamination\QuestionBankSeeder;
use Database\Seeders\WebsiteSetup\DepartmentContactSeeder;
use Database\Seeders\Examination\ExaminationSettingsSeeder;
use Database\Seeders\OnlineExamination\QuestionGroupSeeder;
use Modules\VehicleTracker\Database\Seeders\VehicleTrackerDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */

    public function run()
    {
        $seeders = [];

        if (env('APP_DEMO')) {
            $seeders = [
                UploadSeeder::class,
                RoleSeeder::class,
                DesignationSeeder::class,
                UserSeeder::class,
                PermissionSeeder::class,
                FlagIconSeeder::class,
                LanguageSeeder::class,
                SettingSeeder::class,
                SearchSeeder::class,
                GenderSeeder::class,
                ReligionSeeder::class,
                BloodGroupSeeder::class,
                SessionSeeder::class,
                SubscriptionSeeder::class,
                // // Staff
                DepartmentSeeder::class,
                StaffSeeder::class,
                // // Academic
                ClassesSeeder::class,
                SectionSeeder::class,
                ShiftSeeder::class,
                SubjectSeeder::class,
                ClassSetupSeeder::class,
                ClassSetupChildrensSeeder::class,
                ClassRoomSeeder::class,
                SubjectAssignSeeder::class,
                TimeScheduleSeeder::class,
                ClassRoutineSeeder::class,
                // Student info
                ParentGuardianSeeder::class,
                StudentCategorySeeder::class,
                StudentSeeder::class,
                // // Fees
                FeesGroupSeeder::class,
                FeesTypeSeeder::class,
                FeesMasterSeeder::class,
                // // Examication
                ExamTypeSeeder::class,
                MarkGradeSeeder::class,
                ExamRoutineSeeder::class,
                // // Accounts
                AccountHeadSeeder::class,
                IncomeSeeder::class,
                ExpenseSeeder::class,
                ExaminationSettingsSeeder::class,
                ExamAssignSeeder::class,
                MarkRegisterSeeder::class,
                // // Frontend
                PageSectionsSeeder::class,
                SliderSeeder::class,
                CounterSeeder::class,
                NewsSeeder::class,
                NoticeSeeder::class,
                EventSeeder::class,
                GalleryCategorySeeder::class,
                GallerySeeder::class,
                ContactInfoSeeder::class,
                DepartmentContactSeeder::class,
                AboutSeeder::class,
                PageSeeder::class,
                ProgramCatalogSeeder::class,
                // // Library
                BookCategorySeeder::class,
                // // Online Examination
                QuestionGroupSeeder::class,
                QuestionBankSeeder::class,
                OnlineExamSeeder::class,
                CurrencySeeder::class,
                LeaveTypeSeeder::class,

                //Discount
                SpecialDiscountSeeder::class,
                MobileAppSettingSeeder::class,
                SystemNotificationSeeder::class
            ];

        } else {
            $seeders = [
                UploadSeeder::class,
                RoleSeeder::class,
                DesignationSeeder::class,
                PermissionSeeder::class,
                UserSeeder::class,
                FlagIconSeeder::class,
                LanguageSeeder::class,
                SettingSeeder::class,
                SearchSeeder::class,
                GenderSeeder::class,
                ReligionSeeder::class,
                BloodGroupSeeder::class,
                SessionSeeder::class,
                SubscriptionSeeder::class,
                // Staff
                DepartmentSeeder::class,
                // Frontend
                PageSectionsSeeder::class,
                SliderSeeder::class,
                CounterSeeder::class,
                NewsSeeder::class,
                GalleryCategorySeeder::class,
                GallerySeeder::class,
                ContactInfoSeeder::class,
                DepartmentContactSeeder::class,
                AboutSeeder::class,
                PageSeeder::class,
                ProgramCatalogSeeder::class,
                // Library
                CurrencySeeder::class,
                MobileAppSettingSeeder::class
            ];

            Log::info('Seeders:', $seeders);
            // Check if the module is enabled and add its seeders
            if (hasModule('VehicleTracker')) {
                $seeders = array_merge($seeders, [
                    VehicleTrackerDatabaseSeeder::class,
                    // Add other related seeders here if needed
                ]);
            }
        }

        $this->call($seeders);
    }
}
