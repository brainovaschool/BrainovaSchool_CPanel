<?php

namespace App\Http\Controllers\Api;

use App\Enums\RoleEnum;
use App\Models\User;
use App\Mail\ForgotPassword;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\UpdatePasswordRequest;
use App\Traits\ApiReturnFormatTrait;

class AuthController extends Controller
{
    use ApiReturnFormatTrait;


    public function register(Request $request)
    {
        try {
            $data = Validator::make($request->all(),[
                'name'      => 'required',
                'email'     => 'required|string|email|unique:users',
                'password'  => 'required|string|min:8',
            ]);

            if ($data->fails()) {
                return $this->responseWithError(___('alert.validation_error'), $data->errors());
            }

            $user = User::create([
                'name'      => $request->name,
                'email'     => $request->email,
                'password'  => Hash::make($request->password),
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return $this->responseWithSuccess(___('alert.registered_successfully'), [
                'access_token'  => $token,
                'token_type'    => 'Bearer',
            ]);

        } catch (\Throwable $th) {

            return $this->responseWithError(___('alert.something_went_wrong'), $th->getMessage());
        }
    }


    public function login(Request $request)
    {
        try {
            $data = Validator::make($request->all(), [
                'email'     => 'required|email',
                'password'  => 'required',
            ]);

            if ($data->fails()) {
                return $this->responseWithError(___('alert.validation_error'), $data->errors());
            }

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return $this->responseWithError(___('alert.invalid_credentials'));
            }

            if (!in_array($user->role_id, [
                RoleEnum::STUDENT,
                RoleEnum::STAFF,
                RoleEnum::GUARDIAN,
                RoleEnum::TEACHER,
                RoleEnum::DRIVER()
            ])) {
                return $this->responseWithError(___('alert.unauthorized'));
            }

            if (!$user->status) {
                return $this->responseWithError(___('alert.your_account_is_inactive'));
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            $userData = $user->only('id', 'name', 'email', 'phone', 'permissions', 'status', 'role_id', 'upload_id', 'uuid');
            $userData['access_token'] = $token;
            $userData['avatar'] = @globalAsset($user->upload->path, '40X40.webp');

            return $this->responseWithSuccess(___('alert.login_successfully'), [
                'token_type' => 'Bearer',
                'user'       => $userData,
            ]);

        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong'), $th->getMessage());
        }
    }



     public function loginRoleWise(Request $request)
    {
        try {
            $data = Validator::make($request->all(), [
                'email'     => 'required|email',
                'password'  => 'required',
                'role_id'   => 'required|integer',
            ]);

            if ($data->fails()) {
                return $this->responseWithError(___('alert.validation_error'), $data->errors());
            }

            $user = User::where('email', $request->email)->where('role_id', $request->role_id)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return $this->responseWithError(___('alert.invalid_credentials'));
            }

            if (!in_array($user->role_id, [
                RoleEnum::STUDENT,
                RoleEnum::STAFF,
                RoleEnum::GUARDIAN,
                RoleEnum::TEACHER,
                RoleEnum::DRIVER()
            ])) {
                return $this->responseWithError(___('alert.unauthorized'));
            }

            if (!$user->status) {
                return $this->responseWithError(___('alert.your_account_is_inactive'));
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            $userData = $user->only('id', 'name', 'email', 'phone', 'permissions', 'status', 'role_id', 'upload_id', 'uuid');
            $userData['access_token'] = $token;
            $userData['avatar'] = @globalAsset($user->upload->path, '40X40.webp');

            return $this->responseWithSuccess(___('alert.login_successfully'), [
                'token_type' => 'Bearer',
                'user'       => $userData,
            ]);

        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong'), $th->getMessage());
        }
    }

    /** Staff "Team" mobile login — unlike loginRoleWise, this does NOT filter
     *  by an exact role_id. Staff designations like Coordinator/Content
     *  Creator are custom Role rows created via the Roles screen (see
     *  seedCoordinatorRole()), so their role_id is whatever auto-increment
     *  value that role got, never a fixed number the app could guess ahead
     *  of time. Access into Team Portal itself is already gated by the
     *  portal_access permission on the API routes, so this only needs to
     *  keep the three admin-type roles (SuperAdmin/Admin/Accounting)
     *  website-only, same as loginRoleWise does for everyone else. */
    public function loginTeam(Request $request)
    {
        try {
            $data = Validator::make($request->all(), [
                'email'     => 'required|email',
                'password'  => 'required',
            ]);

            if ($data->fails()) {
                return $this->responseWithError(___('alert.validation_error'), $data->errors());
            }

            $user = User::where('email', $request->email)
                ->get()
                ->first(fn ($candidate) => Hash::check($request->password, $candidate->password));

            if (!$user) {
                return $this->responseWithError(___('alert.invalid_credentials'));
            }

            if (in_array((int) $user->role_id, [RoleEnum::SUPERADMIN, RoleEnum::ADMIN, RoleEnum::ACCOUNTING], true)) {
                return $this->responseWithError(___('alert.unauthorized'));
            }

            if (!$user->status) {
                return $this->responseWithError(___('alert.your_account_is_inactive'));
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            $userData = $user->only('id', 'name', 'email', 'phone', 'permissions', 'status', 'role_id', 'upload_id', 'uuid');
            $userData['access_token'] = $token;
            $userData['avatar'] = @globalAsset($user->upload->path, '40X40.webp');

            return $this->responseWithSuccess(___('alert.login_successfully'), [
                'token_type' => 'Bearer',
                'user'       => $userData,
            ]);

        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong'), $th->getMessage());
        }
    }


    public function forgotPassword(Request $request)
    {
        try {

            $data = Validator::make($request->all(),[
                'email' => 'required'
            ]);

            if ($data->fails()) {
                return $this->responseWithError(___('alert.validation_error'), $data->errors());
            }

            $user = User::where('email', $request['email'])->first();

            if (!$user) {
                return $this->responseWithError(___('alert.user_not_found'), []);
            }

            $otp = rand(111111, 999999);

            $data = [
                'email' => $user->email,
                'otp'   => $otp
            ];

            $user->update(['reset_password_otp' => $otp]);

            \Config::set('mail.mailers.smtp.password', \Crypt::decrypt(setting('mail_password')));

            $forgotPassword = new ForgotPassword($data);

            Mail::to($user->email)->send($forgotPassword);

            return $this->responseWithSuccess(___('alert.we_will_send_you_an_otp_on_this_email'), ['otp' => $otp]);

        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong'), $th->getMessage());
        }
    }


    public function resetPassword(Request $request)
    {
        try {
            $data = Validator::make($request->all(),[
                'email'     => 'required',
                'otp'       => 'required',
                'password'  => 'required|confirmed',
            ]);

            if ($data->fails()) {
                return $this->responseWithError(___('alert.validation_error'), $data->errors());
            }

            $user = User::where('email', $request['email'])->first();

            if (!$user) {
                return $this->responseWithError(___('alert.user_not_found'), []);
            }

            if (@$user->reset_password_otp != $request['otp']) {
                return $this->responseWithError(___('alert.otp_is_invalid'), []);
            }

            $user->update([
                'password' => Hash::make($request['password']),
                'reset_password_otp' => null
            ]);

            return $this->responseWithSuccess(___('alert.password_has_been_updated_successfully'), []);

        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong'), $th->getMessage());
        }
    }


    public function updatePassword(UpdatePasswordRequest $request)
    {
        try {

            User::where('id', auth()->id())->update(['password' => Hash::make($request->password)]);

            return $this->responseWithSuccess(___('alert.password_has_been_changed_successfully'), []);

        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong'), $th->getMessage());
        }
    }


    public function logout(Request $request)
    {
        try {
            $request->user()->tokens()->delete();
            return $this->responseWithSuccess(___('alert.you_have_successfully_logged_out'), []);

        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong'), $th->getMessage());
        }
    }
}
