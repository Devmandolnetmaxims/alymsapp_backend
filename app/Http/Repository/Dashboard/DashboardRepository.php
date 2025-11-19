<?php
namespace App\Http\Repository\Dashboard;

use App\Models\EstimateService;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use App\Models\Service;
use App\Models\Job;
use App\Models\log;
use App\Models\VehicleMake;
use App\Models\VehicleModel;
use App\Models\LogRepository;
use App\Models\Estimate;
use App\Models\Module;
use App\Models\Comment;

class DashboardRepository
{
    // get dashboard data.
    public static function GetDashboardData()
    {
        // Job related data.
        $baseQuery = Job::select('jobs.*', 'estimates.module')->join('estimates', 'estimates.id', '=', 'jobs.estimate_id')->join('customer', 'customer.id', '=', 'estimates.user_id');

        // Ongoing jobs (Onsite).
        $onsiteJobCount = (clone $baseQuery)
        ->where('jobs.status', Job::JOB_ONSITE)
        ->count();

        // In-progress jobs.
        $inprogressJobCount = (clone $baseQuery)
            ->where('jobs.status', Job::JOB_INPROGRESS)
            ->count();

        // Completed jobs.
        $completedJobCount = (clone $baseQuery)
            ->where('jobs.status', Job::JOB_DONE)
            ->count();

        $jobs = Job::select('jobs.*', 'estimates.module')->join('estimates', 'estimates.id', '=', 'jobs.estimate_id')->join('customer', 'customer.id', '=', 'estimates.user_id')->whereIn('jobs.status', [Job::JOB_ONSITE, Job::JOB_INPROGRESS, Job::JOB_DONE])
        ->latest('jobs.created_at') // Assuming you want to get the last 5 records based on creation date
        ->take(5)
        ->get();
        foreach($jobs as $job) {
            // Get cutomer details.
            $customer = Customer::select('customer.*')->join('estimates', 'estimates.user_id', '=', 'customer.id')->where('estimates.id', $job->estimate_id)->first();
            if($customer != null) {
                $job->customer = $customer;
                // Set estimate data.
                $estimateData = Estimate::where('id', $job->estimate_id)->first();
                $job->estimateData = $estimateData;
                

                // Set make.
                $job->make = VehicleMake::where('id', $estimateData->make_id)->first();

                //Set model.
                $job->model = VehicleModel::where('id', $estimateData->model_id)->first();

                // Job or estimate created by.
                $createdBy = User::select('first_name', 'last_name')->where('id', $estimateData->created_by)->first();
                $estimateData->created_by =  $createdBy->first_name. " " . $createdBy->last_name;
            }
            // Get team members details.
            if($job->team) {
                $teams = explode(",", $job->team);
                $members = [];
                foreach($teams as $team) {
                    $member = User::where('id', $team)->first();
                    if($member != null) {
                        $memberData['id'] = $member->id;                                  
                        $memberData['first_name'] = $member->first_name;                 
                        $memberData['last_name'] = $member->last_name;                 
                    } else {
                        $memberData = null;
                    }
                    $members[] = $memberData;
                }
            } else {
                $members = [];
            }
            $job->team_members = $members;
        }
        
        $jobsData = [
            'Onsite' => $onsiteJobCount,
            'Inprogress' => $inprogressJobCount,
            'Completed' => $completedJobCount,
            'AllJobs' => $jobs,
        ];

        /////////////////////////////////////////////////////////////////////////////////////////////////
        /////////////////////////////////////////////////////////////////////////////////////////////////
        /////////////////////////////////////////////////////////////////////////////////////////////////        

        // Estimate Related Data.
        $estimates = Estimate::select('estimates.*')
            ->leftJoin('customer', 'customer.id', '=', 'estimates.user_id')
            ->where('module', Module::MODULE_ESTIMATE);
        
        // Count Approved estimate.
        $approvedEstimateCount = (clone $estimates)
            ->where('status', Estimate::APPROVED)
            ->count();

        // Count Pending estimate.
        $pendingEstimateCount = (clone $estimates)
            ->whereIn('status',[Estimate::PENDING,Estimate::DRAFT,Estimate::REVIEW])
            ->count();
        
        $estimates = $estimates->whereIn('status', [Estimate::PENDING,Estimate::DRAFT,Estimate::REVIEW, Estimate::APPROVED])->orderBy('updated_at', 'desc')->get();

        if(!empty($estimates)) {
            foreach($estimates as $estimate) {
                if(!empty($estimate->user_id)) {
                    $customer = Customer::select('first_name', 'last_name', 'email', 'phone')->where('id', $estimate->user_id)->first();
                    $estimate->customer = $customer;

                    // Get approved user details.
                    $user = User::select('first_name', 'last_name', 'email')->where('id', $estimate->approved_by)->first();
                    $estimate->approved_by = $user;

                    // Created by user details
                    $createdByUser = User::select('id', 'first_name', 'last_name', 'email')->with('roles')->where('id', $estimate->created_by)->first();
                    $estimate->created_by = $createdByUser;

                    // Is Estimate already conver to job.
                    $isConvert = Job::where('estimate_id', $estimate->id)->exists();
                    $estimate->already_convert = $isConvert;
                }
            }
        }

        $estimateData = [
            'Approved' => $approvedEstimateCount,
            'Pending' => $pendingEstimateCount,
            'AllEstimates' => $estimates,
        ];

        /////////////////////////////////////////////////////////////////////////////////////////////////
        /////////////////////////////////////////////////////////////////////////////////////////////////
        ///////////////////////////////////////////////////////////////////////////////////////////////// 

        // Invoice and bill related data.
        // Collect invoices.
        $invoices = Invoice::select('invoices.*','invoices.id as invoice_id', 'invoices.updated_at as invoice_updated_at','jobs.id as job_id', 'jobs.*', 'estimates.id as estimate_id', 'estimates.*')->join('jobs', 'jobs.id', '=', 'invoices.job_id')->join('estimates', 'estimates.id', '=', 'jobs.estimate_id')->join('customer', 'customer.id', '=', 'estimates.user_id');

        // Get all invoice.
        $invoices = $invoices->orderby('invoices.created_at', 'desc')->limit(10)->get();

        $totalPending = 0;
        $totalVat = 0;
        $totalPaid = 0;
        $totalDue = 0;

        foreach($invoices as $invoice) {
            // If current day is over the due date.
            if($invoice->type == Invoice::BILL) {
                $balance = $invoice->net_total - $invoice->amount;
                $type = "Bill";
            } else {
                $balance = $invoice->grand_total - $invoice->amount;
                $totalVat = $invoice->net_vat+$totalVat;
                $type = "Invoice";
            }
           
            if($invoice->due_date < date('Y-m-d')) {
                $totalDue = $balance + $totalDue;
            } else {
                $totalPending = $balance + $totalPending;
            }
            $totalPaid = $invoice->amount+$totalPaid;
            $invoice->balance = $balance;
            $invoice->customer = Customer::withTrashed()->find($invoice->user_id);
        }
        $invoiceData = [
            'total_pending' => $totalPending,
            'total_vat' => $totalVat,
            'total_paid' => $totalPaid,
            'total_overdue' => $totalDue,
            'invoice_list' => $invoices,
        ];

        
        /////////////////////////////////////////////////////////////////////////////////////////////////
        /////////////////////////////////////////////////////////////////////////////////////////////////
        ///////////////////////////////////////////////////////////////////////////////////////////////// 

        // Comment related Data.
        //get first 5 comment.
        $comments = Comment::orderBy('id', 'desc')->limit(5)->get();
        foreach($comments as $comment) {
            // check if job and estimate is deleted or not.
            // dump(Job::where('id', $comment->instance_id)->exists(),$comment->instance_id );
            // dump(Estimate::where('id', $comment->instance_id)->exists(), $comment->instance_id);
            if(Job::where('id', $comment->instance_id)->exists() && $comment->activity == 2) {
                $comment->activity = Module::where('id', $comment->activity)->first();
                if($comment->activity['module'] == 'Estimate') {
                    $comment->instance = Estimate::where('id', $comment->instance_id)->first();
                } elseif($comment->activity['module'] == 'Job') {
                    $comment->instance = Job::join('estimates', 'estimates.id', '=', 'jobs.estimate_id')->where('jobs.id', $comment->instance_id)->first();
                }
                $comment->user = User::where('id', $comment->user_id)->first();
            } elseif(Estimate::where('id', $comment->instance_id)->exists() && $comment->activity == 1) {
                $comment->activity = Module::where('id', $comment->activity)->first();
                if($comment->activity['module'] == 'Estimate') {
                    $comment->instance = Estimate::where('id', $comment->instance_id)->first();
                } elseif($comment->activity['module'] == 'Job') {
                    $comment->instance = Job::join('estimates', 'estimates.id', '=', 'jobs.estimate_id')->where('jobs.id', $comment->instance_id)->first();
                }
                $comment->user = User::where('id', $comment->user_id)->first();
            }
            else {
                //
            }
          
        }
// die();
        /////////////////////////////////////////////////////////////////////////////////////////////////
        /////////////////////////////////////////////////////////////////////////////////////////////////
        ///////////////////////////////////////////////////////////////////////////////////////////////// 
        
        // Log related data.
        $logs = log::orderBy('id', 'desc')->limit(5)->get();
        foreach($logs as $log) {
            $log->activity = Module::where('id', $log->activity)->first();
            if($log->activity['module'] == 'Estimate') {
                $log->instance = Estimate::where('id', $log->instance_id)->first();
            } elseif($log->activity['module'] == 'Job') {
                $log->instance = Job::join('estimates', 'estimates.id', '=', 'jobs.estimate_id')->where('jobs.id', $log->instance_id)->first();
            }
        }

        $data = [
            'jobs' => $jobsData,
            'estimates' => $estimateData,
            'invoices' => $invoiceData,
            'comments' => $comments,
            'logs' => $logs,
        ];
        return response()->json(['data' => $data, 'status' => 1, 'message' => 'Dashboard data fetched successfully.'], 200);
    }
}