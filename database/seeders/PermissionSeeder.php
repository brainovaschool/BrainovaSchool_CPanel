<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $attributes = [
            'dashboard'         => ['read' => 'counter_read', 'read' => 'fees_collesction_read', 'read' => 'revenue_read', 'read' => 'fees_collection_this_month_read', 'read' => 'income_expense_read', 'read' => 'upcoming_events_read', 'read' => 'attendance_chart_read', 'read' => 'calendar_read'],
            //Student Info
            'student'           => ['read' => 'student_read', 'create' => 'student_create', 'update' => 'student_update', 'delete' => 'student_delete'],
            'student_category'  => ['read' => 'student_category_read', 'create' => 'student_category_create', 'update' => 'student_category_update', 'delete' => 'student_category_delete'],
            'promote_students'  => ['read' => 'promote_students_read', 'create' => 'promote_students_create'],
            'disabled_students' => ['read' => 'disabled_students_read', 'create' => 'disabled_students_create'],
            'parent'            => ['read' => 'parent_read', 'create' => 'parent_create', 'update' => 'parent_update', 'delete' => 'parent_delete'],
            'admission'         => ['read' => 'admission_read', 'create' => 'admission_create', 'update' => 'admission_update', 'delete' => 'admission_delete'],
            // Academic
            'classes'           => ['read' => 'classes_read', 'create' => 'classes_create', 'update' => 'classes_update', 'delete' => 'classes_delete'],
            'section'           => ['read' => 'section_read', 'create' => 'section_create', 'update' => 'section_update', 'delete' => 'section_delete'],
            'shift'             => ['read' => 'shift_read', 'create' => 'shift_create', 'update' => 'shift_update', 'delete' => 'shift_delete'],
            'class_setup'       => ['read' => 'class_setup_read', 'create' => 'class_setup_create', 'update' => 'class_setup_update', 'delete' => 'class_setup_delete'],
            'subject'           => ['read' => 'subject_read', 'create' => 'subject_create', 'update' => 'subject_update', 'delete' => 'subject_delete'],
            'subject_assign'    => ['read' => 'subject_assign_read', 'create' => 'subject_assign_create', 'update' => 'subject_assign_update', 'delete' => 'subject_assign_delete'],
            'class_routine'     => ['read' => 'class_routine_read', 'create' => 'class_routine_create', 'update' => 'class_routine_update', 'delete' => 'class_routine_delete'],
            'time_schedule'     => ['read' => 'time_schedule_read', 'create' => 'time_schedule_create', 'update' => 'time_schedule_update', 'delete' => 'time_schedule_delete'],
            'class_room'        => ['read' => 'class_room_read', 'create' => 'class_room_create', 'update' => 'class_room_update', 'delete' => 'class_room_delete'],
            // Fees
            'fees_group'        => ['read' => 'fees_group_read', 'create' => 'fees_group_create', 'update' => 'fees_group_update', 'delete' => 'fees_group_delete'],
            'fees_type'         => ['read' => 'fees_type_read', 'create' => 'fees_type_create', 'update' => 'fees_type_update', 'delete' => 'fees_type_delete'],
            'fees_master'       => ['read' => 'fees_master_read', 'create' => 'fees_master_create', 'update' => 'fees_master_update', 'delete' => 'fees_master_delete'],
            'fees_assign'       => ['read' => 'fees_assign_read', 'create' => 'fees_assign_create', 'update' => 'fees_assign_update', 'delete' => 'fees_assign_delete'],
            'fees_collect'      => ['read' => 'fees_collect_read', 'create' => 'fees_collect_create', 'update' => 'fees_collect_update', 'delete' => 'fees_collect_delete'],
            'discount_setup'    => ['siblings_discount' => 'siblings_discount', 'early_payment_discount' => 'early_payment_discount'],
            // examinations
            'exam_type'         => ['read' => 'exam_type_read', 'create' => 'exam_type_create', 'update' => 'exam_type_update', 'delete' => 'exam_type_delete'],
            'marks_grade'       => ['read' => 'marks_grade_read', 'create' => 'marks_grade_create', 'update' => 'marks_grade_update', 'delete' => 'marks_grade_delete'],
            'exam_assign'       => ['read' => 'exam_assign_read', 'create' => 'exam_assign_create', 'update' => 'exam_assign_update', 'delete' => 'exam_assign_delete'],
            'exam_routine'      => ['read' => 'exam_routine_read', 'create' => 'exam_routine_create', 'update' => 'exam_routine_update', 'delete' => 'exam_routine_delete'],
            'marks_register'    => ['read' => 'marks_register_read', 'create' => 'marks_register_create', 'update' => 'marks_register_update', 'delete' => 'marks_register_delete'],
            'homework'          => ['read' => 'homework_read', 'create' => 'homework_create', 'update' => 'homework_update', 'delete' => 'homework_delete'],
            'exam_setting'      => ['read' => 'exam_setting_read', 'update' => 'exam_setting_update'],
            // Account
            'account_head'      => ['read' => 'account_head_read', 'create' => 'account_head_create', 'update' => 'account_head_update', 'delete' => 'account_head_delete'],
            'income'            => ['read' => 'income_read', 'create' => 'income_create', 'update' => 'income_update', 'delete' => 'income_delete'],
            'expense'           => ['read' => 'expense_read', 'create' => 'expense_create', 'update' => 'expense_update', 'delete' => 'expense_delete'],
            // Attendance
            'attendance'        => ['read' => 'attendance_read', 'create' => 'attendance_create'],
            'attendance_report' => ['read' => 'report_attendance_read'],
            // Report
            'marksheet'         => ['read' => 'report_marksheet_read'],
            'merit_list'        => ['read' => 'report_merit_list_read'],
            'progress_card'     => ['read' => 'report_progress_card_read'],
            'due_fees'          => ['read' => 'report_due_fees_read'],
            'fees_collection'   => ['read' => 'report_fees_collection_read'],
            'account'           => ['read' => 'report_account_read'],
            'class_routine'     => ['read' => 'report_class_routine_read'],
            'exam_routine'      => ['read' => 'report_exam_routine_read'],
            'attendance_report' => ['read' => 'report_attendance_read'],
            // Language
            'language'          => ['read' => 'language_read', 'create' => 'language_create', 'update' => 'language_update','update terms' => 'language_update_terms', 'delete' => 'language_delete'],
            // Staff
            'roles'             => ['read' => 'role_read', 'create' => 'role_create', 'update' => 'role_update', 'delete' => 'role_delete'],
            'users'             => ['read' => 'user_read', 'create' => 'user_create', 'update' => 'user_update', 'delete' => 'user_delete'],
            'department'        => ['read' => 'department_read', 'create' => 'department_create', 'update' => 'department_update', 'delete' => 'department_delete'],
            'designation'       => ['read' => 'designation_read', 'create' => 'designation_create', 'update' => 'designation_update', 'delete' => 'designation_delete'],
            // Website setup
            'sections'          => ['read' => 'page_sections_read', 'update' => 'page_sections_update'],
            'slider'            => ['read' => 'slider_read', 'create' => 'slider_create', 'update' => 'slider_update', 'delete' => 'slider_delete'],
            'about'             => ['read' => 'about_read', 'create' => 'about_create', 'update' => 'about_update', 'delete' => 'about_delete'],
            'counter'           => ['read' => 'counter_read', 'create' => 'counter_create', 'update' => 'counter_update', 'delete' => 'counter_delete'],
            'contact_info'      => ['read' => 'contact_info_read', 'create' => 'contact_info_create', 'update' => 'contact_info_update', 'delete' => 'contact_info_delete'],
            'dep_contact'       => ['read' => 'dep_contact_read', 'create' => 'dep_contact_create', 'update' => 'dep_contact_update', 'delete' => 'dep_contact_delete'],
            'news'              => ['read' => 'news_read', 'create' => 'news_create', 'update' => 'news_update', 'delete' => 'news_delete'],
            'event'             => ['read' => 'event_read', 'create' => 'event_create', 'update' => 'event_update', 'delete' => 'event_delete'],
            'gallery_category'  => ['read' => 'gallery_category_read', 'create' => 'gallery_category_create', 'update' => 'gallery_category_update', 'delete' => 'gallery_category_delete'],
            'gallery'           => ['read' => 'gallery_read', 'create' => 'gallery_create', 'update' => 'gallery_update', 'delete' => 'gallery_delete'],
            'program_category'  => ['read' => 'program_category_read', 'create' => 'program_category_create', 'update' => 'program_category_update', 'delete' => 'program_category_delete'],
            'program_focus'     => ['read' => 'program_focus_read', 'create' => 'program_focus_create', 'update' => 'program_focus_update', 'delete' => 'program_focus_delete'],
            'program'           => ['read' => 'program_read', 'create' => 'program_create', 'update' => 'program_update', 'delete' => 'program_delete'],
            'subscribe'         => ['read' => 'subscribe_read'],
            'contact_message'   => ['read' => 'contact_message_read'],
            // Settings
            'general_settings'  => ['read' => 'general_settings_read', 'update' => 'general_settings_update'],
            'mobile_app_settings'  => ['read' => 'mobile_settings_read', 'update' => 'mobile_settings_update'],
            'storage_settings'  => ['read' => 'storage_settings_read', 'update' => 'storage_settings_update'],
            'task_schedules'    => ['read' => 'task_schedules_read', 'update' => 'task_schedules_update'],
            'software_update'   => ['read' => 'software_update_read', 'update' => 'software_update_update'],
            'recaptcha_settings'=> ['read' => 'recaptcha_settings_read', 'update' => 'recaptcha_settings_update'],
            'payment_gateway_settings'=> ['read' => 'payment_gateway_settings_read', 'update' => 'payment_gateway_settings_update'],
            'email_settings'    => ['read' => 'email_settings_read', 'update' => 'email_settings_update'],
            'sms_settings'    => ['read' => 'sms_settings_read', 'update' => 'sms_settings_update'],
            'genders'           => ['read' => 'gender_read', 'create' => 'gender_create', 'update' => 'gender_update', 'delete' => 'gender_delete'],
            'religions'         => ['read' => 'religion_read', 'create' => 'religion_create', 'update' => 'religion_update', 'delete' => 'religion_delete'],
            'blood_groups'      => ['read' => 'blood_group_read', 'create' => 'blood_group_create', 'update' => 'blood_group_update', 'delete' => 'blood_group_delete'],
            'sessions'          => ['read' => 'session_read', 'create' => 'session_create', 'update' => 'session_update', 'delete' => 'session_delete'],
            'tax_setup'        =>  ['update' => 'tax_setup'],
            // Library
            'book_category'     => ['read' => 'book_category_read', 'create' => 'book_category_create', 'update' => 'book_category_update', 'delete' => 'book_category_delete'],
            'book'              => ['read' => 'book_read', 'create' => 'book_create', 'update' => 'book_update', 'delete' => 'book_delete'],
            'member'            => ['read' => 'member_read', 'create' => 'member_create', 'update' => 'member_update', 'delete' => 'member_delete'],
            'member_category'   => ['read' => 'member_category_read', 'create' => 'member_category_create', 'update' => 'member_category_update', 'delete' => 'member_category_delete'],
            'issue_book'        => ['read' => 'issue_book_read', 'create' => 'issue_book_create', 'update' => 'issue_book_update', 'delete' => 'issue_book_delete'],
            // Online examination
            'online_exam_type'  => ['read' => 'online_exam_type_read', 'create' => 'online_exam_type_create', 'update' => 'online_exam_type_update', 'delete' => 'online_exam_type_delete'],
            'question_group'    => ['read' => 'question_group_read', 'create' => 'question_group_create', 'update' => 'question_group_update', 'delete' => 'question_group_delete'],
            'question_bank'     => ['read' => 'question_bank_read', 'create' => 'question_bank_create', 'update' => 'question_bank_update', 'delete' => 'question_bank_delete'],
            'online_exam'       => ['read' => 'online_exam_read', 'create' => 'online_exam_create', 'update' => 'online_exam_update', 'delete' => 'online_exam_delete'],

            // Id card
            'id_card'             => ['read' => 'id_card_read', 'create' => 'id_card_create', 'update' => 'id_card_update', 'delete' => 'id_card_delete'],
            'id_card_generate'    => ['read' => 'id_card_generate_read'],
            // Certificate
            'certificate'             => ['read' => 'certificate_read', 'create' => 'certificate_create', 'update' => 'certificate_update', 'delete' => 'certificate_delete'],
            'certificate_generate'    => ['read' => 'certificate_generate_read'],
            // homework
            'homework'             => ['read' => 'homework_read', 'create' => 'homework_create', 'update' => 'homework_update', 'delete' => 'homework_delete'],
            // gmeet
            'gmeet'                => ['read' => 'gmeet_read', 'create' => 'gmeet_create', 'update' => 'gmeet_update', 'delete' => 'gmeet_delete'],
            // notice board
            'notice_board'         => ['read' => 'notice_board_read', 'create' => 'notice_board_create', 'update' => 'notice_board_update', 'delete' => 'notice_board_delete'],
            // sms_mail_template
            'sms_mail_template'    => ['read' => 'sms_mail_template_read', 'create' => 'sms_mail_template_create', 'update' => 'nsms_mail_templateupdate', 'delete' => 'sms_mail_template_delete'],
            // sms_mail_template
            'sms_mail'             => ['read' => 'sms_mail_read', 'create' => 'sms_mail_send'],
            // Forums
            'forums'               => ['read' => 'forum_list', 'create' => 'forum_create', 'update' => 'forum_update', 'delete' => 'forum_delete', 'forum_feeds' => 'forum_feeds'],
            // Forum Comments
            'forum_comment'        => ['read' => 'forum_comment_list', 'create' => 'forum_comment_create', 'update' => 'forum_comment_update', 'delete' => 'forum_comment_delete'],
            // Memories
            'memories'             => ['read' => 'memory_list', 'create' => 'memory_create', 'update' => 'memory_update', 'delete' => 'memory_delete'],


        ];

        foreach($attributes as $key => $attribute){
        	$permission               = new Permission();
        	$permission->attribute    = $key;
            $permission->keywords     = $attribute;
        	$permission->save();
        }
    }
}
