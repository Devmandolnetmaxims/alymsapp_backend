<?php

namespace App\Http\Controllers\Comment;

use App\Http\Repository\Comment\CommentRepository;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    // Add comment.
    public function createComment(Request $request)
    {
        return CommentRepository::AddComment($request);
    }

    // Get comment.
    public function allComment(Request $request)
    {
        return CommentRepository::GetComment($request);
    }
}
