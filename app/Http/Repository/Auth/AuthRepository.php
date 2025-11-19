<?php

namespace App\Http\Repository\Auth;

use App\Http\Repository\Employee\EmployeeRepository;
use App\Http\Repository\Email\EmailRepository;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use App\Models\PasswordReset;
use App\Models\User;
use Auth;

class AuthRepository
{
    public function LoginUser($request) {
        //User is exists or not.
        if(User::where(['email' => $request->email, 'status' => 1])->exists()) {
            //Attempt to login with email and password.
            if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
                // Authentication successful
                $user = Auth::user();
                // Generate a token for the user
                $token = $user->createToken('login')->accessToken;
                $user['token'] = $token;
                $user['role'] = $user->getRoleNames()[0];
                $roleid = $user->roles()->pluck('id')->first();
                // get role and permission.
                $role = Role::with('permissions')->where('id', $roleid)->whereNot('id', 1)->first();
                if(!empty($role)) {
                    // get all permissoins.
                    $curds = ['Add/Edit', 'Show', 'Delete'];
                    $modules = ['Estimate', 'Job', 'Team', 'Customer', 'Services', 'Invoice'];
                    foreach( $modules as $module) {
                        foreach($curds as $curd) {
                            $permissionToRole[strtolower($module)][$curd == "Add/Edit" ? "Add": $curd] = $role->hasPermissionTo($curd." ".$module);
                        }
        
                    }
                    $role = [
                        "name" => $role->name,
                        "label" => $role->label,
                        "permissions" => $permissionToRole,
        
                    ];
                }
                if(!empty($role)) {
                   $user['role_permission'] = $role;
                } else {
                    $user['role_permission'] = [];
                }
                unset($user['roles']);
                // store the token.
                $userData = User::where('id', $user->id)->first();
                if($request->has('android') && !empty($request->android)) {
                    $userData->android_token = $request->android;
                    $userData->save();
                } elseif($request->has('ios') && !empty($request->ios)) {
                    $userData->ios_token = $request->ios;
                    $userData->save();
                }
                return response()->json(['data' => $user, 'status' => 1, 'message' => 'Authenticated']);
            } else {
                // Authentication failed
                return response()->json(['data' => [], 'status' => 0, 'message' => 'Invalid credentials'], 401);
            }
        } else {
            return response()->json(['data' => [], 'status' => 0, 'message' => 'Invalid credentials'],401);
        }
    }

    // Forget Password send token email.
    public static function ResetPasswordMail($request) {
        // Generate token.
        $token = (new self)->generateRandomString(20);
        $resetlink = 'https://www.almysapp.com/auth/reset-password/'.$token;
        $data = ['resetlink' => $resetlink];
        $subject = "Forget Password";
        $views = 'Auth/Email/forgetpassword';

        // Check is the give email is exists or not.
        if(!User::where('email', $request->email)->exists()) {
            return response()->json(['data' => [], 'status' => 0, 'message' => "Invalid credentials"], 401);
        }
        // Find existing token for the email.
        $resetToken = PasswordReset::where('email', $request->email)->first();
        if($resetToken) {
            // Remove existing token.
            $resetToken->delete();
        }
        // Create new token.
        $resetToken = PasswordReset::create(['email' =>$request->email, 'token' => $token]);

        if($resetToken) {
            // Send email to employee.
            $sendEmail = EmailRepository::SendEmail($request->email, $subject, $views, $data);
            if($sendEmail) {
                return response()->json(['data' => [], 'status' => 1, 'message' => "Mail Send Successfully"], 200);
            } else {
                return response()->json(['data' => [], 'status' => 0, 'message' => "Mail Not Send"], 500);
            }
        } else {
            return response()->json(['data' => [], 'status' => 0, 'message' => "Something went wrong!!"], 500);
        }
    }

    // Reset password
    public static function ResetPassword($request) {
         // Check if only token is provided
        if(!$request->has('password')) {
            $isTokenValid = PasswordReset::where('token', $request->token)->exists();
            return response()->json([
                'data' => [],
                'status' => $isTokenValid ? 1 : 0,
                'message' => $isTokenValid ? "Link is valid." : "Link is not valid."
            ], $isTokenValid ? 200 : 422);
        }
        // Check for blank password.
        if($request->has('password') && ($request->password == "" || $request->password == null)) {
            return response()->json(['data' => [], 'status' => 1, 'message' => "Password is required"],500);
        }
        //Find email by token.
        $user = User::leftJoin('password_reset_tokens', 'password_reset_tokens.email', '=', 'users.email')
        ->where('password_reset_tokens.token', $request->token)->first();
        if($user) {
            $user->password = Hash::make($request->password);
            // Remove used token.
            if($user->status == User::PENDING_USER) {
                $user->status = User::ACTIVE_USER;
            }
            $user->save();
            PasswordReset::where('token', $request->token)->delete();
            return response()->json(['data' => [], 'status' => 1, 'message' => "Password Changed Successfuly!!"],200);
        } else {
            return response()->json(['data' => [], 'status' => 0, 'message' => "Invalid token!!"],422);
        }
    }

    public static function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }

    // change password
    public function ChangePassword($request) {
        $user = Auth::user();
        //Check for validation.
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required', 
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status"=>false,
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        if (!Hash::check($request->current_password, $user->password)) {

           return response()->json(['message' => 'The provided password does not match your current password.',"status"=>false]);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['message' => 'Password changed successfully.',"status"=>true]);
    }
}
