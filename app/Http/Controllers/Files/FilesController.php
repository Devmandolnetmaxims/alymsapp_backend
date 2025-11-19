<?php

namespace App\Http\Controllers\Files;

use App\Http\Requests\Files\FilesCreateRequest;
use App\Http\Repository\Files\FilesRepository;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FilesController extends Controller
{
     // Create Files.
     public function createFile(Request $request) {
        //Check the required Validation.
        $validation = FilesCreateRequest::FilesCreateValidation($request);
        if($validation === true) {
            //Create new Employee.
            return FilesRepository::AddFiles($request);
        } else {
            return $validation;
        }
    }
}
