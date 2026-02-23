<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use DB;
use App\Models\User;
use Hash;
use Str;
use App\Http\Controllers\API\BaseController as BaseController;

class PasswordController extends BaseController
{
        /**
     * Change Password 
     */
    public function changePassword(ChangePasswordRequest $request)
    {

        try {
            // Get authenticated user via JWT
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return $this->sendError('User not found.', [], 404);
            }

        // Check current password
            if (!Hash::check($request->current_password, $user->password)) {
                return $this->sendError('Current password is incorrect', [], 422);
            }

        // Update password
            $user->password = Hash::make($request->new_password);
            $user->save();

        // Optional: invalidate current token (force re-login)
            JWTAuth::invalidate(JWTAuth::getToken());

           return $this->sendResponse([], 'Password changed successfully. Please login again.');

        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return $this->sendError('Token invalid or not provided', [
                'error' => 'Token invalid or not provided.'
            ], 401);
        }
    }

    /**
     * [forgot description]
     * @param  ForgotPasswordRequest $request [description]
     * @return [type]                         [description]
     */
    public function forgotPassword(ForgotPasswordRequest $request)
    {
        // send reset link
        $status = Password::sendResetLink(
        $request->only('email')
    );

    if ($status === Password::RESET_LINK_SENT) {
        return $this->sendResponse([], 'Password reset link sent successfully.');
    }

    return $this->sendError('Unable to send reset link.', [], 400);
    }

    /**
     * [reset description]
     * @param  ResetPasswordRequest $request [description]
     * @return [type]                        [description]
     */
    // 🔹 RESET PASSWORD
    public function resetPassword(ResetPasswordRequest $request)
    {

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->password = Hash::make($password);
                $user->setRememberToken(Str::random(60));
                $user->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return $this->sendResponse([], 'Password successfully reset.');
        }

        return $this->sendError('Something went wrong.', [
            'message' => __($status)
        ], 400);
    }


}

