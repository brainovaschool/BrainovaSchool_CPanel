<?php

namespace App\Repositories;

use App\Models\Accounts\AccountHead;
use App\Models\Session;
use App\Models\Setting;
use App\Models\Currency;
use App\Models\Language;
use Illuminate\Support\Str;
use App\Traits\CommonHelperTrait;
use App\Interfaces\SettingInterface;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\SettingStoreRequest;
use App\Http\Requests\GeneralSetting\StorageUpdateRequest;
use App\Models\SettingTranslate;
use Illuminate\Support\Facades\DB;
use App\Traits\ReturnFormatTrait;


class SettingRepository implements SettingInterface
{
    use ReturnFormatTrait, CommonHelperTrait;

    private $model;
    private $setting_trans;

    public function __construct(Setting $model, SettingTranslate $setting_trans)
    {
        $this->model = $model;
        $this->setting_trans = $setting_trans;
    }

    public function getAll()
    {
        return Setting::all();
    }

    public function getLanguage()
    {
        return Language::all();
    }

    public function getSessions()
    {
        return Session::active()->get();
    }

    public function getCurrencies()
    {
        return Currency::get(['code', 'symbol']);
    }

    // General setting start
    public function updateGeneralSetting($request)
    {
        try {
            // Application name start
            if($request->has('application_name')){
                $setting            = $this->model::where('name', 'application_name')->first();
                if($setting){
                    $setting->value = $request->application_name;
                }else{
                    $setting        = new $this->model;
                    $setting->name  = 'application_name';
                    $setting->value = $request->application_name;
                }
                $setting->save();
            }
            // Application name end

            //Footer Text start
            if($request->has('footer_text')){
                $setting            = $this->model::where('name', 'footer_text')->first();
                if($setting){
                    $setting->value = $request->footer_text;
                }else{
                    $setting        = new $this->model;
                    $setting->name  = 'footer_text';
                    $setting->value = $request->footer_text;
                }
                $setting->save();
            }
            //Footer Text end

            //country start
            if($request->has('country')){
                $setting            = $this->model::where('name', 'country')->first();
                if($setting){
                    $setting->value = $request->country;
                }else{
                    $setting        = new $this->model;
                    $setting->name  = 'country';
                    $setting->value = $request->country;
                }
                $setting->save();
            }
            //country Text end

            //timezone start
            if($request->has('timezone')){
                $setting            = $this->model::where('name', 'timezone')->first();
                if($setting){
                    $setting->value = $request->timezone;
                }else{
                    $setting        = new $this->model;
                    $setting->name  = 'timezone';
                    $setting->value = $request->timezone;
                }
                $setting->save();
            }
            //timezone Text end


            //Address start
            if($request->has('address')){
                $setting            = $this->model::where('name', 'address')->first();
                if($setting){
                    $setting->value = $request->address;
                }else{
                    $setting        = new $this->model;
                    $setting->name  = 'address';
                    $setting->value = $request->address;
                }
                $setting->save();
            }
             //Address end

              //Map start

            if($request->has('map_key')){
                $setting            = $this->model::where('name', 'map_key')->first();
                if($setting){
                    $setting->value = $request->map_key;
                }
                $setting->save();
            }

            //Map End


            //Phone start
            if($request->has('phone')){
                $setting            = $this->model::where('name', 'phone')->first();
                if($setting){
                    $setting->value = $request->phone;
                }else{
                    $setting        = new $this->model;
                    $setting->name  = 'phone';
                    $setting->value = $request->phone;
                }
                $setting->save();
            }
            //Phone end

            //Email start
            if($request->has('email')){
                $setting            = $this->model::where('name', 'email')->first();
                if($setting){
                    $setting->value = $request->email;
                }else{
                    $setting        = new $this->model;
                    $setting->name  = 'email';
                    $setting->value = $request->email;
                }
                $setting->save();
            }
            //Email end

            //Contact mode start (frontend Contact page: website form vs WhatsApp chat)
            if($request->has('contact_mode')){
                $setting            = $this->model::where('name', 'contact_mode')->first();
                if($setting){
                    $setting->value = $request->contact_mode;
                }else{
                    $setting        = new $this->model;
                    $setting->name  = 'contact_mode';
                    $setting->value = $request->contact_mode;
                }
                $setting->save();
            }
            //Contact mode end

            //WhatsApp number start
            if($request->has('whatsapp_number')){
                $setting            = $this->model::where('name', 'whatsapp_number')->first();
                if($setting){
                    $setting->value = $request->whatsapp_number;
                }else{
                    $setting        = new $this->model;
                    $setting->name  = 'whatsapp_number';
                    $setting->value = $request->whatsapp_number;
                }
                $setting->save();
            }
            //WhatsApp number end

            //WhatsApp default message start
            if($request->has('whatsapp_default_message')){
                $setting            = $this->model::where('name', 'whatsapp_default_message')->first();
                if($setting){
                    $setting->value = $request->whatsapp_default_message;
                }else{
                    $setting        = new $this->model;
                    $setting->name  = 'whatsapp_default_message';
                    $setting->value = $request->whatsapp_default_message;
                }
                $setting->save();
            }
            //WhatsApp default message end

            //School about start
            if($request->has('school_about')){
                $setting            = $this->model::where('name', 'school_about')->first();
                if($setting){
                    $setting->value = $request->school_about;
                }else{
                    $setting        = new $this->model;
                    $setting->name  = 'school_about';
                    $setting->value = $request->school_about;
                }
                $setting->save();
            }
            //School about end

            //Defualt language start
            if($request->has('default_langauge')){
                $setting            = $this->model::where('name', 'default_langauge')->first();
                if($setting){
                    $setting->value = $request->default_langauge;
                }else{
                    $setting        = new $this->model;
                    $setting->name  = 'default_langauge';
                    $setting->value = $request->default_langauge;
                }
                $path = base_path('lang/' . $setting->value);
                if(is_dir($path)){
                    session()->put('locale', $setting->value);
                }
                $setting->save();
            }
            //Defualt language end
            //Defualt session start
            if($request->has('session')){
                $setting            = $this->model::where('name', 'session')->first();
                if($setting){
                    $setting->value = $request->session;
                }else{
                    $setting        = new $this->model;
                    $setting->name  = 'session';
                    $setting->value = $request->session;
                }
                $setting->save();
            }
            //Defualt session end

            // White logo start
            if ($request->has('light_logo') && $request->file('light_logo')->isValid()) {
                $setting            = $this->model::where('name', 'light_logo')->first();
                $path               = 'backend/uploads/settings';
                if ($setting) {
                    $file_path          = public_path($setting->value);
                    // if(file_exists($file_path)){
                    //     File::delete($file_path);
                    // }
                    $file               = $request->file('light_logo');
                    $extension          = $file->guessExtension();
                    $filename           = Str::random(6). '_' . time() . '.' . $extension;
                    if (setting('file_system') == 's3') {
                        $filePath       = s3Upload($path, $file);
                        $setting->value = $filePath;
                    }else{
                        $file->move($path, $filename);
                        $setting->value = $path .'/'. $filename;
                    }
                    $setting->save();

                }else {
                    $setting        = new $this->model;
                    $setting->name  = 'light_logo';
                    $file           = $request->file('light_logo');
                    $extension      = $file->guessExtension();
                    $filename       = Str::random(6). '_' . time() . '.' . $extension;
                    if (setting('file_system') == 's3') {
                        $filePath       = s3Upload($path, $file);
                        $setting->value = $filePath;
                    }else{
                        $file->move($path, $filename);
                        $setting->value = $path .'/'. $filename;
                    }
                    $setting->save();
                }
            }
            // White logo end


            if ($request->has('dark_logo') && $request->file('dark_logo')->isValid()) {
                $setting            = $this->model::where('name', 'dark_logo')->first();
                $path               = 'backend/uploads/settings';
                if ($setting) {
                    $file_path = public_path($setting->value);
                    // if(file_exists($file_path)){
                    //     File::delete($file_path);
                    // }
                    $file               = $request->file('dark_logo');
                    $extension          = $file->guessExtension();
                    $filename           = Str::random(6). '_' . time() . '.' . $extension;
                    if (setting('file_system') == 's3') {
                        $filePath       = s3Upload($path, $file);
                        $setting->value = $filePath;
                    }else{
                        $file->move($path, $filename);
                        $setting->value = $path .'/'. $filename;
                    }
                    $setting->save();

                }else {

                    $setting        = new $this->model;
                    $setting->name  = 'dark_logo';
                    $file           = $request->file('dark_logo');
                    $extension      = $file->guessExtension();
                    $filename       = Str::random(6). '_' . time() . '.' . $extension;
                    if (setting('file_system') == 's3') {
                        $filePath       = s3Upload($path, $file);
                        $setting->value = $filePath;
                    }else{
                        $file->move($path, $filename);
                        $setting->value = $path .'/'. $filename;
                    }
                    $setting->save();
                }
            }

            if ($request->has('favicon') && $request->file('favicon')->isValid()) {
                $setting                = $this->model::where('name', 'favicon')->first();
                $path = 'backend/uploads/settings';
                if ($setting) {
                    $file_path          = public_path($setting->value);
                    // if(file_exists($file_path)){
                    //     File::delete($file_path);
                    // }
                    $file               = $request->file('favicon');
                    $extension          = $file->guessExtension();
                    $filename           = Str::random(6). '_' . time() . '.' . $extension;
                    if (setting('file_system') == 's3') {
                        $filePath       = s3Upload($path, $file);
                        $setting->value = $filePath;
                    }else{
                        $file->move($path, $filename);
                        $setting->value = $path .'/'. $filename;
                    }
                    $setting->save();

                }else {
                    $setting            = new $this->model;
                    $setting->name      = 'favicon';
                    $file = $request->file('favicon');
                    $extension          = $file->guessExtension();
                    $filename           = Str::random(6). '_' . time() . '.' . $extension;
                    if (setting('file_system') == 's3') {
                        $filePath       = s3Upload($path, $file);
                        $setting->value = $filePath;
                    }else{
                        $file->move($path, $filename);
                        $setting->value = $path .'/'. $filename;
                    }
                    $setting->save();
                }
            }


            // Currency Code start
            if($request->has('currency_code')){
                $setting            = $this->model::where('name', 'currency_code')->first();
                if($setting){
                    $setting->value = $request->currency_code;
                }else{
                    $setting        = new $this->model;
                    $setting->name  = 'currency_code';
                    $setting->value = $request->currency_code;
                }
                $setting->save();
            }
            // Currency Code end


            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }
    // General setting en
    public function updateRecaptchaSetting($request)
    {
        try {
            // Recaptcha site key start
            if($request->has('recaptcha_sitekey')){
                $setting            = $this->model::where('name', 'recaptcha_sitekey')->first();
                if($setting){
                    $setting->value = $request->recaptcha_sitekey;
                }else{
                    $setting        = new $this->model;
                    $setting->name  = 'recaptcha_sitekey';
                    $setting->value = $request->recaptcha_sitekey;
                }
                $setting->save();
            }
            // Recaptcha site key end

            // Recaptcha secret start
            if($request->has('recaptcha_secret')){
                $setting            = $this->model::where('name', 'recaptcha_secret')->first();
                if($setting){
                    $setting->value = $request->recaptcha_secret;
                }else{
                    $setting        = new $this->model;
                    $setting->name  = 'recaptcha_secret';
                    $setting->value = $request->recaptcha_secret;
                }
                $setting->save();
            }
            // Recaptcha secret end

            // Recaptcha status start
            if($request->has('recaptcha_status')){
                $setting            = $this->model::where('name', 'recaptcha_status')->first();
                if($setting){
                    $setting->value = $request->recaptcha_status;
                }else{
                    $setting        = new $this->model;
                    $setting->name  = 'recaptcha_status';
                    $setting->value = $request->recaptcha_status;
                }
                $setting->save();
            }
            // Recaptcha status end

            // recaptcha write in env
            $this->setEnvironmentValue('NOCAPTCHA_SITEKEY', $request->recaptcha_sitekey);
            $this->setEnvironmentValue('NOCAPTCHA_SECRET',  $request->recaptcha_secret);
            // recaptcha write in env
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }

    // General setting en
    public function updateSmsSetting($request)
    {
        try {
            // Recaptcha site key start
            if($request->has('twilio_account_sid')){
                $setting            = $this->model::where('name', 'twilio_account_sid')->first();
                if($setting){
                    $setting->value = $request->twilio_account_sid;
                }else{
                    $setting        = new $this->model;
                    $setting->name  = 'twilio_account_sid';
                    $setting->value = $request->twilio_account_sid;
                }
                $setting->save();
            }
            // Recaptcha site key end

            // Recaptcha secret start
            if($request->has('twilio_auth_token')){
                $setting            = $this->model::where('name', 'twilio_auth_token')->first();
                if($setting){
                    $setting->value = $request->twilio_auth_token;
                }else{
                    $setting        = new $this->model;
                    $setting->name  = 'twilio_auth_token';
                    $setting->value = $request->twilio_auth_token;
                }
                $setting->save();
            }
            // Recaptcha secret end

            // Recaptcha status start
            if($request->has('twilio_phone_number')){
                $setting            = $this->model::where('name', 'twilio_phone_number')->first();
                if($setting){
                    $setting->value = $request->twilio_phone_number;
                }else{
                    $setting        = new $this->model;
                    $setting->name  = 'twilio_phone_number';
                    $setting->value = $request->twilio_phone_number;
                }
                $setting->save();
            }
            // Recaptcha status end

            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }


    public function updatePaymentGatewaySetting($request)
    {
        try {
            // UPDATE OR CREATE STRIPE PAYMENT GATEWAY CREDENTIALS
            if($request->payment_gateway == 'Stripe') {
                if ($request->filled('stripe_key')) {
                    $setting = $this->model::where('name', 'stripe_key')->first();


                    if($setting) {
                        $setting->value = $request->stripe_key;
                    }else{
                        $setting        = new $this->model;
                        $setting->name  = 'stripe_key';
                        $setting->value = $request->stripe_key;
                    }
                    $setting->save();
                }

                if($request->filled('stripe_secret')) {
                    $setting = $this->model::where('name', 'stripe_secret')->first();


                    if($setting) {
                        $setting->value = $request->stripe_secret;
                    }else{
                        $setting        = new $this->model;
                        $setting->name  = 'stripe_secret';
                        $setting->value = $request->stripe_secret;
                    }
                    $setting->save();
                }
            }


            // UPDATE OR CREATE PAYPAL PAYMENT GATEWAY CREDENTIALS
            if ($request->payment_gateway == 'PayPal') {
                $setting = $this->model::where('name', 'paypal_payment_mode')->first();

                if($setting) {
                    $setting->value = $request->paypal_payment_mode;
                }else{
                    $setting        = new $this->model;
                    $setting->name  = 'paypal_payment_mode';
                    $setting->value = $request->paypal_payment_mode;
                }
                $setting->save();
            }



            // UPDATE OR CREATE PAYPAL PAYMENT GATEWAY SANDBOX CREDENTIALS
            if($request->payment_gateway == 'PayPal' && $request->paypal_payment_mode == 'Sandbox') {

                if ($request->filled('paypal_sandbox_api_username')) {
                    $setting = $this->model::where('name', 'paypal_sandbox_api_username')->first();

                    if($setting) {
                        $setting->value = $request->paypal_sandbox_api_username;
                    }else{
                        $setting        = new $this->model;
                        $setting->name  = 'paypal_sandbox_api_username';
                        $setting->value = $request->paypal_sandbox_api_username;
                    }
                    $setting->save();
                }

                if ($request->filled('paypal_sandbox_api_password')) {
                    $setting = $this->model::where('name', 'paypal_sandbox_api_password')->first();

                    if($setting) {
                        $setting->value = $request->paypal_sandbox_api_password;
                    }else{
                        $setting        = new $this->model;
                        $setting->name  = 'paypal_sandbox_api_password';
                        $setting->value = $request->paypal_sandbox_api_password;
                    }
                    $setting->save();
                }

                if ($request->filled('paypal_sandbox_api_secret')) {
                    $setting = $this->model::where('name', 'paypal_sandbox_api_secret')->first();

                    if($setting) {
                        $setting->value = $request->paypal_sandbox_api_secret;
                    }else{
                        $setting        = new $this->model;
                        $setting->name  = 'paypal_sandbox_api_secret';
                        $setting->value = $request->paypal_sandbox_api_secret;
                    }
                    $setting->save();
                }

                if ($request->filled('paypal_sandbox_api_certificate')) {
                    $setting = $this->model::where('name', 'paypal_sandbox_api_certificate')->first();

                    if($setting) {
                        $setting->value = $request->paypal_sandbox_api_certificate;
                    }else{
                        $setting        = new $this->model;
                        $setting->name  = 'paypal_sandbox_api_certificate';
                        $setting->value = $request->paypal_sandbox_api_certificate;
                    }
                    $setting->save();
                }
            }



            // UPDATE OR CREATE PAYPAL PAYMENT GATEWAY LIVE CREDENTIALS
            if($request->payment_gateway == 'PayPal' && $request->paypal_payment_mode == 'Live') {

                if ($request->filled('paypal_live_api_username')) {
                    $setting = $this->model::where('name', 'paypal_live_api_username')->first();

                    if($setting) {
                        $setting->value = $request->paypal_live_api_username;
                    }else{
                        $setting        = new $this->model;
                        $setting->name  = 'paypal_live_api_username';
                        $setting->value = $request->paypal_live_api_username;
                    }
                    $setting->save();
                }

                if ($request->filled('paypal_live_api_password')) {
                    $setting = $this->model::where('name', 'paypal_live_api_password')->first();

                    if($setting) {
                        $setting->value = $request->paypal_live_api_password;
                    }else{
                        $setting        = new $this->model;
                        $setting->name  = 'paypal_live_api_password';
                        $setting->value = $request->paypal_live_api_password;
                    }
                    $setting->save();
                }

                if ($request->filled('paypal_live_api_secret')) {
                    $setting = $this->model::where('name', 'paypal_live_api_secret')->first();

                    if($setting) {
                        $setting->value = $request->paypal_live_api_secret;
                    }else{
                        $setting        = new $this->model;
                        $setting->name  = 'paypal_live_api_secret';
                        $setting->value = $request->paypal_live_api_secret;
                    }
                    $setting->save();
                }

                if ($request->filled('paypal_live_api_certificate')) {
                    $setting = $this->model::where('name', 'paypal_live_api_certificate')->first();

                    if($setting) {
                        $setting->value = $request->paypal_live_api_certificate;
                    }else{
                        $setting        = new $this->model;
                        $setting->name  = 'paypal_live_api_certificate';
                        $setting->value = $request->paypal_live_api_certificate;
                    }
                    $setting->save();
                }
            }

            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function storageSettingUpdate($request)
    {
        try {
            // Application name start
            if($request->has('file_system')){
                $setting            = $this->model::where('name', 'file_system')->first();
                if($setting){
                    $setting->value = $request->file_system;
                }else{
                    $setting        = new $this->model;
                    $setting->name  = 'file_system';
                    $setting->value = $request->file_system;
                }
                $setting->save();
            }
            // Application name end

            if ($request->has('aws_access_key_id') && $request->file_system == 's3') {
                // aws_access_key start
                if($request->has('aws_access_key_id')){
                    $setting            = $this->model::where('name', 'aws_access_key_id')->first();
                    if($setting){
                        $setting->value = $request->aws_access_key_id;
                    }else{
                        $setting        = new $this->model;
                        $setting->name  = 'aws_access_key_id';
                        $setting->value = $request->aws_access_key_id;
                    }
                    $setting->save();

                }
                // aws_access_key end

                // aws_secret_key start
                if($request->has('aws_secret_key')){
                    $setting            = $this->model::where('name', 'aws_secret_key')->first();
                    if($setting){
                        $setting->value = $request->aws_secret_key;
                    }else{
                        $setting        = new $this->model;
                        $setting->name  = 'aws_secret_key';
                        $setting->value = $request->aws_secret_key;
                    }
                    $setting->save();
                }
                // aws_secret_key end

                // aws_region start
                if($request->has('aws_region')){
                    $setting            = $this->model::where('name', 'aws_region')->first();
                    if($setting){
                        $setting->value = $request->aws_region;
                    }else{
                        $setting        = new $this->model;
                        $setting->name  = 'aws_region';
                        $setting->value = $request->aws_region;
                    }
                    $setting->save();
                }
                // aws_region end

                // aws_bucket start
                if($request->has('aws_bucket')){
                    $setting            = $this->model::where('name', 'aws_bucket')->first();
                    if($setting){
                        $setting->value = $request->aws_bucket;
                    }else{
                        $setting        = new $this->model;
                        $setting->name  = 'aws_bucket';
                        $setting->value = $request->aws_bucket;
                    }
                    $setting->save();
                }
                // aws_bucket end

                // aws_endpoint start
                if($request->has('aws_endpoint')){
                    $setting            = $this->model::where('name', 'aws_endpoint')->first();
                    if($setting){
                        $setting->value = $request->aws_endpoint;
                    }else{
                        $setting        = new $this->model;
                        $setting->name  = 'aws_endpoint';
                        $setting->value = $request->aws_endpoint;
                    }
                    $setting->save();
                }
                // aws_endpoint end
            }


            // recaptcha write in env
            $this->setEnvironmentValue('AWS_ACCESS_KEY_ID',           $request->aws_access_key_id);
            $this->setEnvironmentValue('AWS_SECRET_ACCESS_KEY',       $request->aws_secret_key);
            $this->setEnvironmentValue('AWS_DEFAULT_REGION',          $request->aws_region);
            $this->setEnvironmentValue('AWS_BUCKET',                  $request->aws_bucket);
            $this->setEnvironmentValue('AWS_ENDPOINT',                $request->aws_endpoint);
            // recaptcha write in env


        } catch (\Throwable $th) {
            return false;
        }
    }
    public function updateMailSetting($request)
    {
        try {
            // Mail drive start
            if($request->has('mail_drive')){
                $setting            = $this->model::where('name', 'mail_drive')->first();
                if($setting){
                    $setting->value = $request->mail_drive;
                }else{
                    $setting        = new $this->model;
                    $setting->name  = 'mail_drive';
                    $setting->value = $request->mail_drive;
                }
                $setting->save();
            }
            // Mail drive end

            // Mail Host start
            if($request->has('mail_host')){
                $setting            = $this->model::where('name', 'mail_host')->first();
                if($setting){
                    $setting->value = $request->mail_host;
                }else{
                    $setting        = new $this->model;
                    $setting->name  = 'mail_host';
                    $setting->value = $request->mail_host;
                }
                $setting->save();
            }
            // Mail Host end

            // Mail Host start
            if($request->has('mail_port')){
                $setting            = $this->model::where('name', 'mail_port')->first();
                if($setting){
                    $setting->value = $request->mail_port;
                }else{
                    $setting        = new $this->model;
                    $setting->name  = 'mail_port';
                    $setting->value = $request->mail_port;
                }
                $setting->save();
            }
            // Mail Host end

            // Mail Address start
            if($request->has('mail_address')){
                $setting            = $this->model::where('name', 'mail_address')->first();
                if($setting){
                    $setting->value = $request->mail_address;
                }else{
                    $setting        = new $this->model;
                    $setting->name  = 'mail_address';
                    $setting->value = $request->mail_address;
                }
                $setting->save();
            }
            // Mail Address end

            // Form Name start
            if($request->has('from_name')){
                $setting            = $this->model::where('name', 'from_name')->first();
                if($setting){
                    $setting->value = $request->from_name;
                }else{
                    $setting        = new $this->model;
                    $setting->name  = 'from_name';
                    $setting->value = $request->from_name;
                }
                $setting->save();
            }
            // Form Name end

            // Mail UserName start
            if($request->has('mail_username')){
                $setting            = $this->model::where('name', 'mail_username')->first();
                if($setting){
                    $setting->value = $request->mail_username;
                }else{
                    $setting        = new $this->model;
                    $setting->name  = 'mail_username';
                    $setting->value = $request->mail_username;
                }
                $setting->save();
            }
            // Mail UserName end

            // Mail UserName start
            if($request->has('mail_password') && $request->mail_password != ""){
                $setting            = $this->model::where('name', 'mail_password')->first();
                if($setting){
                    $setting->value = Crypt::encrypt($request->mail_password);
                }else{
                    $setting        = new $this->model;
                    $setting->name  = 'mail_password';
                    $setting->value = Crypt::encrypt($request->mail_password);
                }
                $setting->save();
            }
            // Mail UserName end

            //Encryption start
            if($request->has('encryption')){
                $setting            = $this->model::where('name', 'encryption')->first();
                if($setting){
                    $setting->value = $request->encryption;
                }else{
                    $setting        = new $this->model;
                    $setting->name  = 'encryption';
                    $setting->value = $request->encryption;
                }
                $setting->save();
            }
            //Encryption end

            // email write in env
            // $this->setEnvironmentValue('MAIL_MAILER',           $request->mail_drive);
            $this->setEnvironmentValue('MAIL_HOST',             $request->mail_host);
            $this->setEnvironmentValue('MAIL_PORT',             $request->mail_port);
            $this->setEnvironmentValue('MAIL_USERNAME',         $request->mail_username);
            // $this->setEnvironmentValue('MAIL_PASSWORD',         Crypt::encrypt($request->mail_password));
            $this->setEnvironmentValue('MAIL_ENCRYPTION',       $request->encryption);
            $this->setEnvironmentValue('MAIL_FROM_ADDRESS',     $request->mail_address);
            $this->setEnvironmentValue('MAIL_FROM_NAME',        $request->from_name);
            // email write in env

            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function allTranslate(){
        return SettingTranslate::where('from', 'general_settings')->get();
    }

    public function translates()
    {
        return $this->setting_trans->get()->groupBy('locale');
    }

    public function translateUpdate($request)
    {
        DB::beginTransaction();
        try {

            foreach($request->application_name as $key => $name){
                $setting = $this->model->where('name' ,'application_name')->firstOrFail();
                $row                   = $this->setting_trans->where(['name'=>'application_name', 'locale'=> $key])->first();
                if(!$row){
                    $row = new $this->setting_trans;
                }
                $row->setting_id = $setting->id;
                $row->value = $request->application_name[$key];
                $row->locale = $key;
                $row->save();
            }


            foreach($request->footer_text as $key => $name){
                $setting = $this->model->where('name' ,'footer_text')->firstOrFail();
                $row                   = $this->setting_trans->where(['name'=>'footer_text', 'locale'=> $key])->first();
                if(!$row){
                    $row = new $this->setting_trans;
                }
                $row->setting_id = $setting->id;
                $row->value = $request->footer_text[$key];
                $row->locale = $key;
                $row->save();
            }



            foreach($request->address as $key => $name){

                $setting = $this->model->where('name' ,'address')->firstOrFail();

                $row                   = $this->setting_trans->where(['name'=>'address', 'locale'=> $key])->first();
                if(!$row){
                    $row = new $this->setting_trans;
                }
                $row->setting_id = $setting->id;
                $row->value = $request->address[$key];
                $row->locale = $key;
                $row->save();
            }


            foreach($request->phone as $key => $name){

                $setting = $this->model->where('name' ,'phone')->firstOrFail();

                $row                   = $this->setting_trans->where(['name'=>'phone', 'locale'=> $key])->first();
                if(!$row){
                    $row = new $this->setting_trans;
                }
                $row->setting_id = $setting->id;
                $row->value = $request->phone[$key];
                $row->locale = $key;
                $row->save();
            }


            foreach($request->school_about as $key => $name){

                $setting = $this->model->where('name' ,'school_about')->firstOrFail();

                $row                   = $this->setting_trans->where(['name'=>'school_about', 'locale'=> $key])->first();
                if(!$row){
                    $row = new $this->setting_trans;
                }
                $row->setting_id = $setting->id;
                $row->value = $request->school_about[$key];
                $row->locale = $key;
                $row->save();
            }

            DB::commit();
            return $this->responseWithSuccess(___('alert.updated_successfully'), []);
        } catch (\Throwable $th) {
            dd($th);
            DB::rollBack();
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function updateTaxSetting($request)
    {
        try {
            if ($request->has('tax_percentage')){
                $setting = $this->model::where('name', 'tax_percentage')->first();
                if (!$setting) {
                    $setting = new $this->model;
                    $setting->name = 'tax_percentage';
                }
                $setting->value = $request->tax_percentage;
                $setting->save();
            }

            if ($request->has('tax_income_head')) {
                $setting = $this->model::where('name', 'tax_income_head')->first();

                $oldHead = $setting ? $setting->value : null;

                if (!$setting) {
                    $setting = new $this->model;
                    $setting->name = 'tax_income_head';
                }

                $setting->value = $request->tax_income_head;
                $setting->save();

                if ($oldHead) {
                    // Update existing AccountHead with old name
                    $accountHead = AccountHead::where('name', $oldHead)->first();
                } else {
                    // Try to find one with new name just in case
                    $accountHead = AccountHead::where('name', $request->tax_income_head)->first();
                }

                if (!$accountHead) {
                    // Store new AccountHead if not found
                    $headStore = new AccountHead;
                    $headStore->name = $request->tax_income_head;
                    $headStore->type = 1;
                    $headStore->status = 1;
                    $headStore->save();
                } else {
                    // Update existing AccountHead
                    $accountHead->name = $request->tax_income_head;
                    $accountHead->save();
                }
            }


            if ($request->has('tax_min_amount')) {
                $setting = $this->model::where('name', 'tax_min_amount')->first();
                if (!$setting) {
                    $setting = new $this->model;
                    $setting->name = 'tax_min_amount';
                }
                $setting->value = $request->tax_min_amount;
                $setting->save();
            }

            if ($request->has('tax_max_amount')) {
                $setting = $this->model::where('name', 'tax_max_amount')->first();
                if (!$setting) {
                    $setting = new $this->model;
                    $setting->name = 'tax_max_amount';
                }
                $setting->value = $request->tax_max_amount;
                $setting->save();
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
