<?php

namespace App\Http\Controllers\Auth;

use App\Http\Requests\Auth\ForgetPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Repository\Auth\AuthRepository;
use App\Http\Requests\Auth\AuthRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected $authRepository;
    //Constructore class.
    public function __construct() {
        $this->authRepository = new AuthRepository();
    }

    // Authentication function for users.
    public function login(Request $request) {
        //Check for validation.
        $validation = AuthRequest::loginValidation($request);
        if($validation === true) {
            // Authentication logic.
            return $this->authRepository->LoginUser($request);
        } else {
            return $validation;
        }
    }

    // Forget Password Mail.
    public function forgetPassword(Request $request) {
        //Check for validation.
        $validation = ForgetPasswordRequest::ForgetPasswordValidation($request);
        if($validation === true) {
            // forget password logic.
            return $this->authRepository->ResetPasswordMail($request);
        } else {
            return $validation;
        }
    }

    // Forget Password.
    public function resetPassword(Request $request) {
        //Check for validation.
        $validation = ResetPasswordRequest::ResetPasswordValidation($request);
        if($validation === true) {
           // Reset password logic.
            return $this->authRepository->ResetPassword($request);
        } else {
            return $validation;
        }
    }

    // change password
    public function changepassword(Request $request) {
        return $this->authRepository->ChangePassword($request);
    }
}
