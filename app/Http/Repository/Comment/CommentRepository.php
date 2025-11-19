<?php

namespace App\Http\Repository\Comment;

use  App\Models\Comment;
use  App\Models\User;
use Auth;
class CommentRepository
{
    // Add Comment.
    public static function AddComment($request) {
        $request['user_id'] = Auth::id();
        $comment = Comment::create($request->all());
        if($comment) {
            return response()->json(['data' => [], 'status' => 1, 'message' => "Comment created successfuly!!"], 200);
        } else {
            return response()->json(['data' => [], 'status' => 0, 'message' => "Un-processable Data!!"], 400);
        }
    }

    // Get Comment.
    public static function GetComment($request) {
        $comment = Comment::select('comments.*');
        if(!empty($request['id'])) {
            $comment = $comment->where('id', $request['id']);
        }
        if(!empty($request['activity'])) {
            $comment = $comment->where('activity', $request['activity']);
        }
        if(!empty($request['instance_id'])) {
            $comment = $comment->where('instance_id', $request['instance_id']);
        }
        $comment = $comment->orderBy('id', 'desc')->get();
        foreach($comment as $commentData) {
            $user = User::with('roles')->where('id', $commentData['user_id'])->first();
            $commentData['first_name'] = $user->first_name;
            $commentData['last_name'] = $user->last_name;
            $commentData['role'] = $user->getRoleNames()->first();
        }
        return $comment;
    }
}