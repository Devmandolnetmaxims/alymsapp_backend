<?php
namespace App\Http\Repository\Role;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
class RoleRepository 
{
    // Add Role

    public static function AddRole($request) {
        // Create a new role.
        $role = Role::create(['name' => $request->name, 'label' => $request->label]);

        // set permission on estimate.
        foreach($request->estimate as $key => $value) {
            if($value == 1) {
                $permission_ids[] = Permission::where('name', $key." "."Estimate")->pluck('id')->first();
            }
        }

        // set permission on job.
        foreach($request->job as $key => $value) {
            if($value == 1) {
                $permission_ids[] = Permission::where('name', $key." "."Job")->pluck('id')->first();
            }
        }

        // set permission on team.
        foreach($request->team as $key => $value) {
            if($value == 1) {
                $permission_ids[] = Permission::where('name', $key." "."Team")->pluck('id')->first();
            }
        }

        // set permission on customer.
        foreach($request->customer as $key => $value) {
            if($value == 1) {
                $permission_ids[] = Permission::where('name', $key." "."Customer")->pluck('id')->first();
            }
        }

        // set permission on services.
        foreach($request->services as $key => $value) {
            if($value == 1) {
                $permission_ids[] = Permission::where('name', $key." "."Services")->pluck('id')->first();
            }
        }

        // set permission on invoice.
        foreach($request->invoice as $key => $value) {
            if($value == 1) {
                $permission_ids[] = Permission::where('name', $key." "."Services")->pluck('id')->first();
            }
        }
        $permissions = Permission::whereIn('id', $permission_ids)->get();
        // Attach Permissions to the role.
        $role->syncPermissions($permissions);
        if(!empty($role)) {
            return response()->json(['data' => [], 'status' => 1, 'message' => 'Role Created Successfuly!!'], 200);
        } else {
            return response()->json(['data' => [], 'status' => 0, 'message' => 'Somethiing Wrong!!'], 400);
        }
    }

    // Update Role
    public static function UpdateRole($request, $id) {
        $role = Role::find($id);
        if(!empty($role)) {
            $permissions = $role->permissions;
            // Remove Permissions from the role.
            $role->revokePermissionTo($permissions);
            $role->update(['name' => $request->name, 'label' => $request->label]);

         // set permission on estimate.
         foreach($request->estimate as $key => $value) {
            if($value == 1) {
                $permission_ids[] = Permission::where('name', $key." "."Estimate")->pluck('id')->first();
            }
        }

        // set permission on job.
        foreach($request->job as $key => $value) {
            if($value == 1) {
                $permission_ids[] = Permission::where('name', $key." "."Job")->pluck('id')->first();
            }
        }

        // set permission on team.
        foreach($request->team as $key => $value) {
            if($value == 1) {
                $permission_ids[] = Permission::where('name', $key." "."Team")->pluck('id')->first();
            }
        }

        // set permission on customer.
        foreach($request->customer as $key => $value) {
            if($value == 1) {
                $permission_ids[] = Permission::where('name', $key." "."Customer")->pluck('id')->first();
            }
        }

        // set permission on services.
        foreach($request->services as $key => $value) {
            if($value == 1) {
                $permission_ids[] = Permission::where('name', $key." "."Services")->pluck('id')->first();
            }
        }

        // set permission on invoice.
        foreach($request->invoice as $key => $value) {
            if($value == 1) {
                $permission_ids[] = Permission::where('name', $key." "."Invoice")->pluck('id')->first();
            }
        }
    
        // Attach Permissions to the role.
        if(!empty($permission_ids)) {
            $permissions = Permission::whereIn('id', $permission_ids)->get();
            $role->syncPermissions($permissions);
        }
            return response()->json(['data' => [], 'status' => 1, 'message' => 'Role Updated Successfully!!'], 200);
        } else {
            return response()->json(['data' => [], 'status' => 0, 'message' => 'Something Wrong!!'], 400);
        }
    }

    public static function ListRole($id = null) {
        if($id) {
            return RoleRepository::GetRoleById($id);
        } else{
            // get roles with all permissions.
            $rolesWithPermissions = Role::with('permissions')->whereNot('id', 1)->get();
            if(!empty($rolesWithPermissions)) {
                return response()->json(['data' => $rolesWithPermissions, 'status' => 1, 'message' => ''], 200);
            } else {
                return response()->json(['data' => [], 'status' => 0, 'message' => ''], 400);
            }
        }
    }
    

    // Get role by id
    public static function GetRoleById($id) {
        $role = Role::with('permissions')->where('id', $id)->whereNot('id', 1)->first();
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
            return response()->json(['data' => $role, 'status' => 1, 'message' => 'Role list!!!'], 200);
        } else {
            return response()->json(['data' => [], 'status' => 0, 'message' => 'Smething wrong!!'], 400);
        }
    }

    // Delete Role
    public static function DeleteRole($id) {
        $role = Role::find($id);
        if(!empty($role)) {
            $role->delete();
            return response()->json(['data' => [], 'status' => 1, 'message' => 'Role Deleted Successfully!!'], 200);
        } else {
            return response()->json(['data' => [], 'status' => 0, 'message' => 'Something Wrong!!'], 400);
        }
    }
}