<?php

namespace App\Http\Controllers\Role;

use App\Http\Requests\Role\RoleCreateRequest;
use App\Http\Requests\Role\RoleUpdateRequest;
use App\Http\Repository\Role\RoleRepository;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    // Create Role
    public function createRole(Request $request)
    {
        $validation = RoleCreateRequest::RoleCreateValidation($request);
        if($validation === true) {
            return RoleRepository::AddRole($request);
        } else {
            return $validation;
        }
    }

    // Edit role
    public function updateRole(Request $request, $id)
    {
        $validation = RoleUpdateRequest::RoleUpdateValidation($request, $id);
        if($validation === true) {
            return RoleRepository::UpdateRole($request, $id);
        } else {
            return $validation;
        }
    }

    // list role
    public function allRole($id = null)
    {
        return RoleRepository::ListRole($id);
    }

    // Delete role.
    public function deleteRole($id) {
        return RoleRepository::DeleteRole($id);
    }
}
