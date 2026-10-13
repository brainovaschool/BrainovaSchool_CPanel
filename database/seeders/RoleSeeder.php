<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;


class RoleSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            [
                'name'        => 'Super Admin',
                'permissions' => [
                    // Dashboard permissions
                    'counter_read',
                    'fees_collesction_read',
                    'revenue_read',
                    'fees_collection_this_month_read',
                    'income_expense_read',
                    'upcoming_events_read',
                    'attendance_chart_read',
                    'calendar_read',

                    // Start student info
                    // Student
                    'student_read',
                    'student_create',
                    'student_update',
                    'student_delete',
                    // Student Category
                    'student_category_read',
                    'student_category_create',
                    'student_category_update',
                    'student_category_delete',
                    // promote_students
                    'promote_students_read',
                    'promote_students_create',
                    // disabled_students
                    'disabled_students_read',
                    'disabled_students_create',
                    // parent
                    'parent_read',
                    'parent_create',
                    'parent_update',
                    'parent_delete',
                    // admission
                    'admission_read',
                    'admission_create',
                    'admission_update',
                    'admission_delete',
                    // End student info

                    // Start Academic
                    // class
                    'classes_read',
                    'classes_create',
                    'classes_update',
                    'classes_delete',
                    // section
                    'section_read',
                    'section_create',
                    'section_update',
                    'section_delete',
                    // shift
                    'shift_read',
                    'shift_create',
                    'shift_update',
                    'shift_delete',
                    // class_setup
                    'class_setup_read',
                    'class_setup_create',
                    'class_setup_update',
                    'class_setup_delete',
                    // subject
                    'subject_read',
                    'subject_create',
                    'subject_update',
                    'subject_delete',
                    // subject_assign
                    'subject_assign_read',
                    'subject_assign_create',
                    'subject_assign_update',
                    'subject_assign_delete',
                    // class_routine
                    'class_routine_read',
                    'class_routine_create',
                    'class_routine_update',
                    'class_routine_delete',
                    // time_schedule
                    'time_schedule_read',
                    'time_schedule_create',
                    'time_schedule_update',
                    'time_schedule_delete',
                    // class_room
                    'class_room_read',
                    'class_room_create',
                    'class_room_update',
                    'class_room_delete',
                    // End Academic

                    // Start Fees
                    // group
                    'fees_group_read',
                    'fees_group_create',
                    'fees_group_update',
                    'fees_group_delete',
                    // type
                    'fees_type_read',
                    'fees_type_create',
                    'fees_type_update',
                    'fees_type_delete',
                    // master
                    'fees_master_read',
                    'fees_master_create',
                    'fees_master_update',
                    'fees_master_delete',
                    // assign
                    'fees_assign_read',
                    'fees_assign_create',
                    'fees_assign_update',
                    'fees_assign_delete',
                    // collect
                    'fees_collect_read',
                    'fees_collect_create',
                    'fees_collect_update',
                    'fees_collect_delete',
                    // End Fees

                    // Start Examination
                    // type
                    'exam_type_read',
                    'exam_type_create',
                    'exam_type_update',
                    'exam_type_delete',
                    // grade
                    'marks_grade_read',
                    'marks_grade_create',
                    'marks_grade_update',
                    'marks_grade_delete',
                    // assing
                    'exam_assign_read',
                    'exam_assign_create',
                    'exam_assign_update',
                    'exam_assign_delete',
                    // exam_routine
                    'exam_routine_read',
                    'exam_routine_create',
                    'exam_routine_update',
                    'exam_routine_delete',
                    // register
                    'marks_register_read',
                    'marks_register_create',
                    'marks_register_update',
                    'marks_register_delete',
                    // homework
                    'homework_read',
                    'homework_create',
                    'homework_update',
                    'homework_delete',
                    'exam_setting_read',
                    'exam_setting_update',
                    // End Examination

                    // Start Transactions
                    // account_head
                    'account_head_read',
                    'account_head_create',
                    'account_head_update',
                    'account_head_delete',
                    // income
                    'income_read',
                    'income_create',
                    'income_update',
                    'income_delete',
                    // expense
                    'expense_read',
                    'expense_create',
                    'expense_update',
                    'expense_delete',
                    // End Transactions


                    // Start Attendance
                    // attendance
                    'attendance_read',
                    'attendance_create',
                    // attendance report
                    'report_attendance_read',
                    // end Attendance


                    // Start Report
                    'report_marksheet_read',
                    'report_merit_list_read',
                    'report_progress_card_read',
                    'report_due_fees_read',
                    'report_fees_collection_read',
                    'report_account_read',
                    'report_class_routine_read',
                    'report_exam_routine_read',
                    'report_attendance_read',
                    // End Report

                    // Start Language
                    'language_read',
                    'language_create',
                    'language_update',
                    'language_update_terms',
                    'language_delete',
                    // End Language

                    // Start Staff
                    // user
                    'user_read',
                    'user_create',
                    'user_update',
                    'user_delete',
                    // role
                    'role_read',
                    'role_create',
                    'role_update',
                    'role_delete',
                    // department
                    'department_read',
                    'department_create',
                    'department_update',
                    'department_delete',
                    // designation
                    'designation_read',
                    'designation_create',
                    'designation_update',
                    'designation_delete',
                    // End Staff

                    // website setup
                    // sections
                    'page_sections_read',
                    'page_sections_update',
                    // slider
                    'slider_read',
                    'slider_create',
                    'slider_update',
                    'slider_delete',
                    // about
                    'about_read',
                    'about_create',
                    'about_update',
                    'about_delete',
                    // counter
                    'counter_read',
                    'counter_create',
                    'counter_update',
                    'counter_delete',
                    // contact_info
                    'contact_info_read',
                    'contact_info_create',
                    'contact_info_update',
                    'contact_info_delete',
                    // dep_contact
                    'dep_contact_read',
                    'dep_contact_create',
                    'dep_contact_update',
                    'dep_contact_delete',
                    // news
                    'news_read',
                    'news_create',
                    'news_update',
                    'news_delete',
                    // event
                    'event_read',
                    'event_create',
                    'event_update',
                    'event_delete',
                    // gallery_category
                    'gallery_category_read',
                    'gallery_category_create',
                    'gallery_category_update',
                    'gallery_category_delete',
                    // gallery
                    'gallery_read',
                    'gallery_create',
                    'gallery_update',
                    'gallery_delete',
                    // subscriptions
                    'subscribe_read',
                    // contact
                    'contact_message_read',
                    // end website setup

                    // Start Settings
                    // general settings
                    'general_settings_read',
                    'general_settings_update',
                    // storage settings
                    'storage_settings_read',
                    'storage_settings_update',
                    // task schedules
                    'task_schedules_read',
                    'task_schedules_update',
                    // software_update
                    'software_update_read',
                    'software_update_update',
                    // recaptcha
                    'recaptcha_settings_read',
                    'recaptcha_settings_update',
                    // payment gateway
                    'payment_gateway_settings_read',
                    'payment_gateway_settings_update',
                    // email
                    'email_settings_read',
                    'email_settings_update',
                    // gender
                    'gender_read',
                    'gender_create',
                    'gender_update',
                    'gender_delete',
                    // religion
                    'religion_read',
                    'religion_create',
                    'religion_update',
                    'religion_delete',
                    // blood_group
                    'blood_group_read',
                    'blood_group_create',
                    'blood_group_update',
                    'blood_group_delete',
                    // session
                    'session_read',
                    'session_create',
                    'session_update',
                    'session_delete',
                    // End settings

                    // Library start
                    // book_category
                    'book_category_read',
                    'book_category_create',
                    'book_category_update',
                    'book_category_delete',
                    // book
                    'book_read',
                    'book_create',
                    'book_update',
                    'book_delete',
                    // member
                    'member_read',
                    'member_create',
                    'member_update',
                    'member_delete',
                    // member_category
                    'member_category_read',
                    'member_category_create',
                    'member_category_update',
                    'member_category_delete',
                    // issue_book
                    'issue_book_read',
                    'issue_book_create',
                    'issue_book_update',
                    'issue_book_delete',
                    // Library end

                    // Online exam
                    // online_exam_type
                    'online_exam_type_read',
                    'online_exam_type_create',
                    'online_exam_type_update',
                    'online_exam_type_delete',
                    // question_group
                    'question_group_read',
                    'question_group_create',
                    'question_group_update',
                    'question_group_delete',
                    // question_bank
                    'question_bank_read',
                    'question_bank_create',
                    'question_bank_update',
                    'question_bank_delete',
                    // online_exam
                    'online_exam_read',
                    'online_exam_create',
                    'online_exam_update',
                    'online_exam_delete',
                    // Online exam end

                    // Forums
                    'forum_list',
                    'forum_create',
                    'forum_update',
                    'forum_delete',
                    'forum_feeds',

                    // Forum comments
                    'forum_comment_list',
                    'forum_comment_create',
                    'forum_comment_update',
                    'forum_comment_delete',

                    // momory
                    'memory_list',
                    'memory_create',
                    'memory_update',
                    'memory_delete'

                ],
            ],
            [
                'name'        => 'Admin',
                'permissions' => [
                    'user_read',
                    'user_create',
                    'user_update',
                    'user_delete',
                    'role_read',
                    'role_create',
                    'role_update',
                    'role_delete',
                    'language_read',
                    'language_create',
                    'language_update_terms',
                    'general_settings_read',
                    'general_settings_update',
                    'storage_settings_read',
                    'storage_settings_read',
                    'recaptcha_settings_update',
                    'email_settings_read',
                ],
            ],
            [
                'name'        => 'Staff',
                'permissions' => []
            ],
            [
                'name'        => 'Accounting',
                'permissions' => [
                    'user_read',
                    'user_create',
                    'user_update',
                    'user_delete',
                    'role_read',
                    'role_create',
                    'role_update',
                    'role_delete',
                    'language_read',
                    'language_create',
                    'language_update_terms',
                    'general_settings_read',
                    'general_settings_update',
                    'storage_settings_read',
                    'storage_settings_read',
                    'recaptcha_settings_update',
                    'email_settings_read',
                ]
            ],
            [
                'name'        => 'Teacher',
                'permissions' => [
                    'ai_help_teacher_read',
                    'skill_mastery_report_read',
                ]
            ],
            [
                'name'        => 'Student',
                'permissions' => []
            ],
            [
                'name'        => 'Gurdian',
                'permissions' => []
            ],
        ];

        foreach ($roles as $role) {
            Role::create([
                "name"        => $role["name"],
                "slug"        => Str::slug($role["name"], "-"),
                "permissions" => $role["permissions"]
            ]);
        }
    }
}

