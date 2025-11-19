<?php

namespace App\Http\Controllers\Const;

use App\Http\Repository\Const\ConstRepository;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ConstController extends Controller
{
    public function conts(Request $request) {
        return ConstRepository::RegularData($request);
    }
}
