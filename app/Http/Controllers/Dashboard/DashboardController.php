<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Repository\Dashboard\DashboardRepository;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    // get dashboard data.
    public function getDashboardData(Request $request) {
        return DashboardRepository::GetDashboardData($request);
    }
}
