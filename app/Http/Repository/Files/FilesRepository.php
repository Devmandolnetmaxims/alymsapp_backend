<?php

namespace App\Http\Repository\Files;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Illuminate\Http\Request;
use App\Models\File;
use Auth;

class FilesRepository
{
    // Add Files.
    public static function AddFiles($request) {
        $files = $request->file('file');
        $data = [];

        foreach($files as $file) {
            $upload = [];
            $upload['user_id'] = Auth::id();
            $upload['file_name'] = uniqid() . '.' . $file->getClientOriginalExtension();
            $upload['original_name'] = $file->getClientOriginalName();
            $upload['extension'] = $file->getClientOriginalExtension();
            $upload['module'] = $request->module;

            // Store the file
            $path = $file->storeAs('public/'.$request->module, $upload['file_name']);
            $upload['path'] = $path;
            // Resize the image
            $image = Image::make(storage_path('app/'.$path))->resize(300, null, function ($constraint) {
                $constraint->aspectRatio();
            });

            // Save the resized image with a quality of 80
            $image->save(public_path('/storage//'.$request->module.'/'.$upload['file_name']), 80);
            // Submit data in database.
            $fileModel = File::create($upload);
            if($fileModel) {
                $data[] = [
                    'id' => $fileModel->id,
                    'path' => url(Storage::url($path)),
                ];
            }
        }

        $status = !empty($data) ? 1 : 0;
        $message = !empty($data) ? 'File Upload Successfuly!!' : 'Something Wrong!!';
        return response()->json(['data' => $data, 'status' => $status, 'message' => $message], $status ? 200 : 500);
    }
}
