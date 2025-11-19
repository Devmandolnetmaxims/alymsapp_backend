<?php

namespace App\Http\Repository\Employee;

use App\Http\Repository\Email\EmailRepository;
use App\Http\Repository\Auth\AuthRepository;
use App\Models\PasswordReset;
use App\Models\User;
use App\Models\Job;

class EmployeeRepository
{
    // Add Employee.
    public static function AddEmployee($request) {
        // Check Permission.
        $permission = ['Add/Edit Team'];
        if(PermissionCheck($permission) === true) {
            
        } else {
            return PermissionCheck($permission);
        }
        // Genrate random password for account security.
        $request['password'] = (new self)->generateRandomString();

        // Set inital status Pending.
        $request['status'] = User::PENDING_USER;

        // Create employee.
        $user = User::create($request->all());
        if(!empty($user)) {
            //Assign the give role.
            $user->assignRole($request->role);
            // Invitation email send to new employee.
            $email = (new self)->SendInviteMail($user->id, $request);
            return response()->json(['data' => [], 'status' => 1, 'message' => 'User Created Successfuly!!'], 200);
        } else {
            return response()->json(['data' => [], 'status' => 1, 'message' => 'Somethiing Wrong!!'], 500);
        }
    }

    // Edit Employee.
    public static function EditEmployee($request, $id) {
        // Check Permission.
        $permission = ['Add/Edit Team'];
        if(PermissionCheck($permission) === true) {
            
        } else {
            return PermissionCheck($permission);
        }
        $user = User::with('roles')->find($id);
        if($user) {
            // If user is not having any role then assign role.
            if(empty($user->getRoleNames()[0])) {
                $user->assignRole($request->role);
            }
            // If user is not having same role then remove and assign.
            if($request->filled('role') && $request->role !== $user->getRoleNames()[0]) {
                $user->removeRole($user->getRoleNames()[0]);
                $user->assignRole($request->role);
            }
            // Update employee.
            $updated = $user->update($request->all());
            $user['role'] = $user->getRoleNames()[0];
            if($updated) {
                return response()->json(['data' => $user, 'status' => 1, 'message' => 'User Updated Successfully!!'], 200);
            } else {
                return response()->json(['data' => [], 'status' => 0, 'message' => 'Something went wrong!!'], 422);
            }
        } else{
            return response()->json(['data' => [], 'status' => 0, 'message' => 'Un-processable data'], 422);
        }
    }

    // Employee List.
    public static function AllList($request, $id) {
        // Check Permission.
        $permission = ['Show Team'];
        if(PermissionCheck($permission) === true) {
            
        } else {
            return PermissionCheck($permission);
        }
        if($id) {
            return (new self)->ListById($id);
        } elseif((($request->has('status') && $request->status != "") || $request->has('team'))) {
            return (new self)->AllUserList($request);
        }
        else {
            if($request->has('per_page') && $request->per_page) {
                $employee = User::with('roles')->whereDoesntHave('roles', function ($query) {
                    $query->where('name', 'Super admin');
                })->orderBy('id', 'desc')->paginate($request->per_page);
            } else {
                $employee = User::with('roles')->whereDoesntHave('roles', function ($query) {
                    $query->where('name', 'Super admin');
                })
                ->where('status', 1)
                ->orderBy('id', 'desc')->get();
            }
            
            return response()->json(['data' => $employee, 'status' => 1, 'message' => 'Company Data!!'], 200);
        }
    }

    // Employee lisy by id.
    public static function ListById($id) {
        $employee = User::with('roles')->where('id', $id)->first();
        if($employee) {
            return response()->json(['data' => $employee, 'status' => 1, 'message' => 'Company Data!!'], 200);
        } else {
            return response()->json(['data' => [], 'status' => 0, 'message' => 'Un-progressable data'], 422);
        }
    }

    // Delete Employee.
    // public static function RemoveEmployee($id) {
    //     // Check Permission.
    //     $permission = ['Delete Team'];
    //     if(PermissionCheck($permission) === true) {
            
    //     } else {
    //         return PermissionCheck($permission);
    //     }

    //     $user = User::find($id);
    //     // Check if user is super admin.
    //     if($user && $user->getRoleNames()[0] !== "Super Admin") {
    //         $jobs = Job::whereRaw("FIND_IN_SET(?, team)", [$id])->where(['completed_on'=> null, 'collected_on' => null])->first();
    //         if($jobs) {
    //             return response()->json(['data'=>$jobs, 'status' => 0, 'message' => 'This employee is assigned to a job.'], 422);
    //         }
    //         $user->delete();
    //         return response()->json(['data'=>[], 'status' => 1, 'message' => 'Employee Delete Successfuly!!'], 200);
    //     } else {
    //         return response()->json(['data'=>[], 'status' => 0, 'message' => 'Un-progressable data'], 422);
    //     }
    // }

    public static function RemoveEmployee($id)
    {
        // ✅ Check Permission
        $permission = ['Delete Team'];
        if (PermissionCheck($permission) !== true) {
            return PermissionCheck($permission);
        }

        // ✅ Find user
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'data' => [],
                'status' => 0,
                'message' => 'Un-processable data!'
            ], 422);
        }

        // ✅ Prevent deleting Super Admin
        if ($user->getRoleNames()[0] === "Super Admin") {
            return response()->json([
                'data' => [],
                'status' => 0,
                'message' => 'You cannot delete Super Admin!'
            ], 422);
        }

        // ✅ Check if employee is assigned to any active job
        $jobs = Job::whereRaw("FIND_IN_SET(?, team)", [$id])
            ->whereNull('completed_on')
            ->whereNull('collected_on')
            ->first();

        if ($jobs) {
            return response()->json([
                'data' => $jobs,
                'status' => 0,
                'message' => 'This employee is assigned to a job.'
            ], 422);
        }

        // ✅ Prepare unique suffix
        $suffix = '_' . ($user->phone ?? 'NA') . '_' . $user->id;

        // ✅ Update email and phone before soft delete
        $user->update([
            'phone' => '0',
            'email' => $user->email ? $user->email . $suffix : null,
        ]);

        // ✅ Soft delete
        $user->delete();

        return response()->json([
            'data' => [],
            'status' => 1,
            'message' => 'Employee deleted successfully!'
        ], 200);
    }


    //Genrate Random Password.
    public static function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()-_=+';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }

    // Invitation send.
    public static function SendInviteMail($id, $request) {
        $user = user::find($id);
        if($user) {
            $token = AuthRepository::generateRandomString(20);
            $resetlink = $request->url.$token;
            $data = [
                'resetlink' => $resetlink,
                'user' => $user->first_name. " ". $user->last_name
        ];
            $subject = "New Registration";
            $views = "Employee/Email/SendInvitation";
            // Find existing token for the email.
            $resetToken = PasswordReset::where('email', $user->email)->first();
            if($resetToken) {
                // Remove existing token.
                $resetToken->delete();
            }
            // Create new token.
            $resetToken = PasswordReset::create(['email' =>$user->email, 'token' => $token]);
            if($resetToken) {
                $emailData = EmailRepository::SendEmail($user->email, "New Employee Register", $views, $data , $attachment = []);
                if($emailData) {
                    return response()->json(['data' => [], 'status' => 1, 'message' => 'Invitation send successfuly!!'], 200);
                } else {
                    return response()->json(['data' => [], 'status' => 0, 'message' => 'Email not send!!'], 400);
                }
                
            }
        } else {
            return response()->json(['data' => [], 'status' => 0, 'message' => 'Something wrong !!'], 500);
        }
    }

    public static function AllUserList($request) {
        $employee = User::with('roles')->whereDoesntHave('roles', function ($query) {
            $query->where('name', 'Super admin');
        });
        if(($request->has('status') && $request->status != "") && ($request->status == User::ACTIVE_USER || $request->status == User::INACTIVE_USER || $request->status == User::PENDING_USER)) {
            $employee = $employee->where('status', $request->status);
        }
        // if get team person name.
        if($request->has('team') && !empty($request->team)) {
            $employee = $employee->where(function ($query) use ($request) {
                $query->where('first_name', 'like', "%{$request->team}%")
                    ->orWhere('last_name', 'like', "%{$request->team}%");
                });
        }
        if($request->has('per_page') && !empty($request->per_page)) {
            $employee = $employee->orderBy('id', 'desc')->paginate($request->per_page);
        } else {
            $employee = $employee->orderBy('id', 'desc')->get();
        }
        return response()->json(['data' => $employee, 'status' => 1, 'message' => 'Team List'], 200);
    }
}

