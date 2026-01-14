<?php

namespace App\Http\Repository\Job;

use App\Http\Controllers\Notification\NotificationController;
use App\Http\Repository\Twillio\TwillioRepository;
use App\Http\Repository\Invoice\InvoiceRepository;
use App\Http\Repository\Email\EmailRepository;
use App\Http\Repository\log\LogRepository;
use Illuminate\Mail\Mailables\Attachment;
use App\Events\Invoice\MakeInvoiceEvent;
use Illuminate\Support\Facades\Storage;
use App\Models\EstimateService;
use App\Models\VehicleModel;
use App\Services\FCMService;
use App\Models\VehicleMake;
use App\Models\Estimate;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Invoice;
use App\Models\Comment;
use App\Models\Module;
use App\Models\User;
use App\Models\File;
use App\Models\Job;
use App\Models\log;
use Auth;


class JobRepository
{
    // Add Job.
    public static function AddJob($request)
    {
        // Check Permission.
        $permission = ['Add/Edit Job'];
        if (PermissionCheck($permission) === true) {
        } else {
            return PermissionCheck($permission);
        }

        $fileComa = [];
        if (!empty($request['files'])) {
            foreach ($request['files'] as $file) {
                $fileComa[] = $file['id'];
            }
        }
        $request['files'] = implode(",", $fileComa);
        if (!Customer::where('id', $request->user_id)->exists()) {
            return response()->json(['data' => [], 'status' => 0, 'message' => 'Un-processable Data!!'], 200);
        }
        // Add other values.
        $request['created_by'] = Auth::id();

        //Created as 
        $request['module'] = Module::MODULE_JOB;

        // Set balance;
        $request['amount'] = 0;

        $request['status'] = Estimate::APPROVED;

        // Get make and model ids.
        $ids = JobRepository::getMakeAndModelIds($request->make, $request->model);

        $request['make_id'] = $ids['make_id'];
        $request['model_id'] = $ids['model_id'];


        // Add Estimate.
        $estimate = Estimate::create($request->all());
        if ($estimate) {
            // Create service table.
            if ($request->has('services') && !empty($request->services)) {
                foreach ($request->services as $service) {
                    $service['estimate_id'] = $estimate->id;
                    $estimateService = EstimateService::create($service);
                }
            }

            // Find User.
            $customer = Customer::find($request->user_id);
            if (!empty($request['files'])) {
                $image_ids = explode(",", $request['files']);
                foreach ($image_ids as $image_id) {
                    $files = File::find($image_id);
                    $attachments[] = Attachment::fromStorage($files->path);
                }
            } else {
                $attachments = [];
            }
            // Convert estimate to job.
            $request['status'] = 1;
            $request['id'] = $estimate->id;
            $job = JobRepository::EstimateToJob($request);

            if ($job) {
                // Mail a customer.
                $data = [
                    'customer_f_name' => $customer->first_name,
                    'customer_l_name' => $customer->last_name
                ];

                if ($customer->email) {
                    EmailRepository::SendEmail($customer->email, "Car Repairance - " . $request->registration, "/Job/Email/JobSend", $data, $attachments);
                }

                // send sms notification.
                $to = "+44" . $customer->phone;
                $message = "Hi " . $customer->first_name . " " . $customer->last_name . "! Your car repair job has been created. We'll keep you posted with updates along the way. Stay tuned!                                       " . $customer->company_name . ".";
                $sms = sendTwilioSms($to, $message);
            }
        }
        if ($estimate && $estimateService) {
            return response()->json(['data' => [], 'status' => 1, 'message' => 'Job Created Successfuly!!'], 200);
        } else {
            return response()->json(['data' => [], 'status' => 0, 'message' => 'Somethign Wrong!!'], 500);
        }
    }

    public static function getMakeAndModelIds($makeName, $modelName)
    {
        // Convert to lowercase for consistency
        $makeName = strtolower(trim($makeName));
        $modelName = strtolower(trim($modelName));

        // ===== MAKE =====
        $make = VehicleMake::firstOrCreate(['make' => $makeName]);
        $makeId = $make->id;

        // ===== MODEL =====
        $model = VehicleModel::firstOrCreate([
            'model' => $modelName,
            'make'  => $makeId // assuming 'make' is the foreign key column name
        ]);
        $modelId = $model->id;

        // Return both IDs
        return [
            'make_id'  => $makeId,
            'model_id' => $modelId
        ];
    }


    // Convert estimate to job
    public static function EstimateToJob($request)
    {
        // Check Permission.
        $permission = ['Add/Edit Job'];
        if (PermissionCheck($permission) === true) {
        } else {
            return PermissionCheck($permission);
        }
        // Check team is not empty for in-progress and compelete.
        if ($request->has('status') && ($request->status == Job::JOB_INPROGRESS || $request->status == Job::JOB_DONE)) {
            // set validation is job convert to in progress without team.
            // Check is team is assign or not.
            if ($request->team == null) {
                return response()->json(['data' => [], 'status' => 0, "message" => "Please assign team members first !!"], 422);
            }
        }

        // Create job here.
        $job = Job::create([
            'estimate_id' => $request->id,
        ]);

        // Now change the status.
        if ($request->has('status') && !empty($request->status)) {
            $request['status_change'] = $request->status;
            $jobStatus = JobRepository::UpdateStatus($request, $job->id);
            if ($jobStatus === true) {
                //
            } else {
                return $jobStatus;
            }
        }

        if ($request->has('team') && !empty($request->team)) {
            JobRepository::AssignTeam($request, $job->id);
        }
        if ($request->module == Module::MODULE_JOB) {
            // Add log for job create.
            $data = [
                'activity' => 2,
                'instance_id' => $job->id,
                'user_id' => Auth::id(),
                'action' => log::ACTION_CREATE,
            ];
            // Call log function.
            get_log($data);
        } else {
            // Add log for Estimate convert.
            $data = [
                'activity' => 2,
                'instance_id' => $job->id,
                'user_id' => Auth::id(),
                'action' => "Converted",
            ];
            // Call log function.
            get_log($data);
        }
        return response()->json(['data' => [], 'status' => 1, 'message' => 'Job Created Successfuly!!'], 200);
    }

    // Edit Job.
    public static function EditJob($request, $id)
    {
        // Check Permission.
        $permission = ['Add/Edit Job'];
        if (PermissionCheck($permission) === true) {
        } else {
            return PermissionCheck($permission);
        }
        // Assign team
        if ($request->has('team') || $request->has('status_change') && !empty($request->status_change)) {
            // Check is status is for inprogress.
            if ($request->has('status_change') && !empty($request->status_change)) {
                $jobStatus = JobRepository::UpdateStatus($request, $id);
                if ($jobStatus === true) {
                    //
                } else {
                    return $jobStatus;
                }
            }

            // Assign team to job.
            if ($request->has('team')) {
                JobRepository::AssignTeam($request, $id);
            }
        } else {
            // Add log for job update.
            $data = [
                'activity' => 2,
                'instance_id' => $id,
                'user_id' => Auth::id(),
                'action' => log::ACTION_UPDATE,
            ];
            // Call log function.
            get_log($data);
            // Edit estimate
            JobRepository::EditJobDtata($request, $id);
        }
        return response()->json(['data' => [], 'status' => 1, 'message' => 'Job Updated Successfuly!!'], 200);
    }

    // Edit job data.
    public static function EditJobDtata($request, $id)
    {
        // Check Permission.
        $permission = ['Add/Edit Job'];
        if (PermissionCheck($permission) === true) {
        } else {
            return PermissionCheck($permission);
        }
        $fileComa = [];
        if (!empty($request['files'])) {
            foreach ($request['files'] as $file) {
                $fileComa[] = $file['id'];
            }
        }
        $request['files'] = implode(",", $fileComa);
        // Update jobs.
        if (Job::where('id', $id)->exists()) {
            $job = Job::find($id);

            $job->update($request->only('status_change', ''));
        }
        $job = Job::where('id', $id)->pluck('estimate_id');
        $id = $job[0];
        // Update Estimate.
        if (Estimate::where('id', $id)->exists()) {

            // Get make and model ids.
            $ids = JobRepository::getMakeAndModelIds($request->make, $request->model);

            $request['make_id'] = $ids['make_id'];
            $request['model_id'] = $ids['model_id'];

            $estimate = Estimate::find($id);
            $estimate->update($request->all());

            // Update estimate services.
            // Create service table.
            if ($request->has('services') && !empty($request->services)) {
                foreach ($request->services as $service) {
                    if (!empty($service['id'])) {
                        $estimateService = EstimateService::where('id', $service['id'])->update($service);
                    } else {
                        $service['estimate_id'] = $estimate->id;
                        $estimateService = EstimateService::create($service);
                    }
                    if ($service['id']) {
                        $presentService[] =  $service['id'];
                    } elseif ($estimateService) {
                        $presentService[] =  $estimateService->id;
                    }
                }
                //delete estimate service table
                // get all estimate service with estimate id and delete if it is not in request
                $estimateServices = EstimateService::where('estimate_id', $estimate->id)->pluck('id')->toArray();

                foreach ($estimateServices as $estimateService) {
                    if (!in_array($estimateService, $presentService)) {
                        $estimateService = EstimateService::find($estimateService);
                        $estimateService->delete();
                    }
                }
            }

            // Find User.
            $customer = Customer::find($request->user_id);
            if (!empty($request['files'])) {
                $image_ids = explode(",", $request['files']);
                foreach ($image_ids as $image_id) {
                    $files = File::find($image_id);
                    $attachments[] = Attachment::fromStorage($files->path);
                }
            } else {
                $attachments = [];
            }
            return response()->json(['data' => [], 'status' => 1, 'message' => 'Job Update Successfuly!!'], 200);
        } else {
            return response()->json(['data' => [], 'status' => 0, 'message' => 'Somethign Wrong!!'], 500);
        }
    }

    // Assign team members to job.
    public static function AssignTeam($request, $id)
    {
        // Check Permission.
        $permission = ['Add/Edit Job'];
        if (PermissionCheck($permission) === true) {
        } else {
            return PermissionCheck($permission);
        }
        $teamids = $request->team;
        // Array convert to coma seprated string.
        $team = implode(",", $request->team);
        $request['team'] = $team;
        $fcmService = app(NotificationController::class);
        // send notification.
        foreach ($teamids as $teamid) {
            $user = User::find($teamid);
            if ($user != null) {
                $title = "Car Repair Job";
                $body = "Hey " . $user->first_name . " " . $user->last_name . "! A new job has been assigned to you. Let's begin working on it!";
                $fcmService->sendAppNotification($title, $body, $teamid);
            }
        }

        // get servies.
        $estimatesData = Job::join('estimates', 'jobs.estimate_id', '=', 'estimates.id')->where('jobs.id', $id)->first();
        $estimateServices = EstimateService::where('estimate_id', $estimatesData->estimate_id)->get();
        foreach ($estimateServices as $estimateService) {
            if ($estimateService->service_id != null && ($estimateService->service_id == 1 || $estimateService->service_id == 10)) {
                foreach ($teamids as $teamid) {
                    $user = User::find($teamid);
                    $title = "Material Requested";
                    $body = $estimateService->description ? $estimateService->description : "Take All Required Material's";
                    $fcmService->sendAppNotification($title, $body, $teamid);
                }
            }
        }
        // Update Job.
        $job = Job::where('id', $id)->update(['team' => $request->team]);
        if ($job) {
            // Add log for job assign team.
            $data = [
                'activity' => 2,
                'instance_id' => $id,
                'user_id' => Auth::id(),
                'action' => "Assign Team",
            ];
            // Call log function.
            get_log($data);

            return response()->json(['data' => [], 'status' => 1, 'message' => 'Team Assign Successfuly!!'], 200);
        } else {
            return response()->json(['data' => [], 'status' => 0, 'message' => 'Somethign Wrong!!'], 500);
        }
    }

    // Job AllList.
    public static function JobAllList($request, $id = null)
    {
        // Check Permission.
        $permission = ['Show Job'];
        if (PermissionCheck($permission) === true) {
        } else {
            return PermissionCheck($permission);
        }
        return JobRepository::AllJob($request, $id);
    }

    // public static function AllJob($request, $id = null)
    // {
    //     if ($request->has('per_page') && !empty($request->per_page)) {
    //         //With pagination.
    //         if ($request->has('status') && !empty($request->status) && ($request->status == Job::JOB_ONSITE || $request->status == Job::JOB_INPROGRESS || $request->status == Job::JOB_DONE || $request->status == Job::JOB_COLLECT)) {
    //             $jobs = Job::select('jobs.*', 'estimates.module')
    //                 ->join('estimates', 'estimates.id', '=', 'jobs.estimate_id')
    //                 ->join('customer', 'customer.id', '=', 'estimates.user_id')
    //                 ->where('jobs.status', $request->status);
    //             if ($request->has('search') && !empty($request->search)) {
    //                 $jobs = $jobs->where(function ($query) use ($request) {
    //                     $query->where('first_name', 'LIKE', "%$request->search%")
    //                         ->orWhere('last_name', 'LIKE', "%$request->search%");
    //                 });
    //             }
    //             if ($request->has('customer_id') && !empty($request->customer_id)) {
    //                 $jobs = $jobs->where('customer.id', $request->customer_id);
    //             }
    //             if ($request->has('start_date') && !empty($request->start_date) && $request->has('end_date') && !empty($request->end_date)) {
    //                 $jobs = $jobs
    //                     ->whereBetween('jobs.created_at', [$request->start_date, $request->end_date]);
    //             }
    //             $jobs = $jobs->orderBy('updated_at', 'desc')->paginate($request->per_page);
    //         } elseif ($id) {
    //             $jobs = Job::select('jobs.id as id', 'estimates.id as estimate_id', 'estimates.module')
    //                 ->join('estimates', 'estimates.id', '=', 'jobs.estimate_id')
    //                 ->where(['jobs.id' => $id])
    //                 ->orderBy('jobs.updated_at', 'desc')
    //                 ->paginate($request->per_page);
    //         }
    //     } else {
    //         //Without pagination.
    //         if ($request->has('status') && !empty($request->status) && ($request->status == Job::JOB_ONSITE || $request->status == Job::JOB_INPROGRESS || $request->status == Job::JOB_DONE || $request->status == Job::JOB_COLLECT)) {
    //             if (Auth::user()->roles[0]->id == 2) {
    //                 $jobs = Job::select('jobs.*', 'estimates.module')
    //                     ->join('estimates', 'estimates.id', '=', 'jobs.estimate_id')
    //                     ->join('customer', 'customer.id', '=', 'estimates.user_id')
    //                     ->where('jobs.status', $request->status);
    //             } else {
    //                 $jobs = Job::select('jobs.*', 'estimates.module')
    //                     ->join('estimates', 'estimates.id', '=', 'jobs.estimate_id')
    //                     ->join('customer', 'customer.id', '=', 'estimates.user_id')
    //                     ->where('jobs.status', $request->status)
    //                     ->whereRaw('FIND_IN_SET(?, team)', [Auth::user()->id]);
    //             }

    //             if ($request->has('search') && !empty($request->search)) {
    //                 $jobs = $jobs->where(function ($query) use ($request) {
    //                     $query->where('first_name', 'LIKE', "%$request->search%")
    //                         ->orWhere('last_name', 'LIKE', "%$request->search%");
    //                 });
    //             }
    //             if ($request->has('customer_id') && !empty($request->customer_id)) {
    //                 $jobs = $jobs->where('customer.id', $request->customer_id);
    //             }
    //             if ($request->has('start_date') && !empty($request->start_date) && $request->has('end_date') && !empty($request->end_date)) {
    //                 $jobs = $jobs
    //                     ->whereBetween('jobs.created_at', [$request->start_date, $request->end_date]);
    //             }
    //             $jobs = $jobs->orderBy('updated_at', 'desc')->get();
    //         } elseif ($id) {
    //             $jobs = Job::select('jobs.id as id', 'jobs.*', 'estimates.id as estimate_id', 'estimates.module')->join('estimates', 'estimates.id', '=', 'jobs.estimate_id')->where(['jobs.id' => $id])->orderBy('jobs.updated_at', 'desc')->get();
    //         }
    //     }
    //     foreach ($jobs as $job) {
    //         // Get cutomer details.
    //         $customer = Customer::select('customer.*')->join('estimates', 'estimates.user_id', '=', 'customer.id')->where('estimates.id', $job->estimate_id)->first();
    //         if ($customer != null) {
    //             $job->customer = $customer;
    //             // Set services.
    //             $job->services = EstimateService::where('estimate_id', $job->estimate_id)->get();
    //             foreach ($job->services as $service) {
    //                 if ($service->service_id != null) {
    //                     $service->service_id = Service::where('id', $service->service_id)->first();
    //                 } else {
    //                     $service->service_id = $service->temp_service;
    //                 }
    //             }

    //             // Set estimate data.
    //             $estimateData = Estimate::where('id', $job->estimate_id)->first();
    //             $job->estimateData = $estimateData;
    //             // Set make.
    //             $job->make = VehicleMake::where('id', $estimateData->make_id)->first();

    //             //Set model.
    //             $job->model = VehicleModel::where('id', $estimateData->model_id)->first();

    //             // Jon or estimate created by.
    //             $createdBy = User::select('first_name', 'last_name')->where('id', $estimateData->created_by)->first();
    //             $estimateData->created_by =  $createdBy->first_name . " " . $createdBy->last_name;

    //             // files
    //             if (!empty($estimateData->files)) {
    //                 $files = explode(",", $estimateData->files);
    //                 if (!empty($estimateData->files)) {
    //                     foreach ($files as $file) {
    //                         $filedata = File::find($file);
    //                         if (!empty($filedata)) {
    //                             $image[] = [
    //                                 'id' => $filedata->id,
    //                                 'path' => url(Storage::url($filedata->path)),
    //                             ];
    //                         }
    //                     }
    //                     if (!empty($image)) {
    //                         $job->estimateData->filesData = $image;
    //                     }
    //                 }
    //             }
    //         }
    //         // Get team members details.
    //         if ($job->team) {
    //             $teams = explode(",", $job->team);
    //             $members = [];
    //             foreach ($teams as $team) {
    //                 $member = User::where('id', $team)->first();
    //                 if ($member != null) {
    //                     $memberData['id'] = $member->id;
    //                     $memberData['first_name'] = $member->first_name;
    //                     $memberData['last_name'] = $member->last_name;
    //                 } else {
    //                     $memberData = null;
    //                 }
    //                 $members[] = $memberData;
    //             }
    //         } else {
    //             $members = [];
    //         }
    //         $job->team_members = $members;

    //         //Get job log.
    //         $data = [
    //             'activity' => [$job->module],
    //             'instance_id' => $job->id,
    //         ];
    //         $logs = LogRepository::GetLog($data);
    //         // Make message for log show.

    //         foreach ($logs as $log) {

    //             $user = User::find($log->user_id);
    //             // $roleName = $user->getRoleNames();
    //             $module = Module::find($log->activity);

    //             // $log['message'] = $user->first_name. " " . $user->last_name. " has " .  $log->action. " " . $module->module;

    //             $log['message'] = ($user ? $user->first_name . " " . $user->last_name : 'Unknown User')
    //                 . " has " . $log->action
    //                 . " " . ($module ? $module->module : 'Unknown Module');

    //             $logdata[] = $log;
    //         }
    //         if (empty($logdata)) {
    //             $job->logs = [];
    //         } else {
    //             $job->logs = $logdata;
    //         }
    //     }
    //     return response()->json(['data' => $jobs, 'status' => 1, 'message' => 'Job List'], 200);
    // }

    // public static function AllJob($request, $id = null)
    // {
    //     if ($request->has('per_page') && !empty($request->per_page)) {
    //         //With pagination.
    //         if ($request->has('status') && !empty($request->status) && ($request->status == Job::JOB_ONSITE || $request->status == Job::JOB_INPROGRESS || $request->status == Job::JOB_DONE || $request->status == Job::JOB_COLLECT)) {
    //             $jobs = Job::select('jobs.*', 'estimates.module')
    //                 ->join('estimates', 'estimates.id', '=', 'jobs.estimate_id')
    //                 ->join('customer', 'customer.id', '=', 'estimates.user_id')
    //                 ->join('vehicle_make', 'vehicle_make.id', '=', 'estimates.make_id')
    //                 ->join('vehicle_model', 'vehicle_model.id', '=', 'estimates.model_id')
    //                 ->where('jobs.status', $request->status);

    //             // 🔍 SEARCH FILTER
    //             if ($request->has('search') && !empty($request->search)) {
    //                 $jobs = $jobs->where(function ($q) use ($request) {
    //                     $q->where('customer.first_name', 'LIKE', "%{$request->search}%")
    //                         ->orWhere('customer.last_name', 'LIKE', "%{$request->search}%")
    //                         ->orWhere('customer.phone', 'LIKE', "%{$request->search}%")
    //                         ->orWhere('estimates.registration', 'LIKE', "%{$request->search}%")
    //                         ->orWhere('vehicle_make.make', 'LIKE', "%{$request->search}%")
    //                         ->orWhere('vehicle_model.model', 'LIKE', "%{$request->search}%");
    //                 });
    //             }



    //             // if ($request->has('search') && !empty($request->search)) {
    //             //     $jobs = $jobs->where(function ($query) use ($request) {
    //             //         $query->where('first_name', 'LIKE', "%$request->search%")
    //             //             ->orWhere('last_name', 'LIKE', "%$request->search%");
    //             //     });
    //             // }
    //             if ($request->has('customer_id') && !empty($request->customer_id)) {
    //                 $jobs = $jobs->where('customer.id', $request->customer_id);
    //             }
    //             if ($request->has('start_date') && !empty($request->start_date) && $request->has('end_date') && !empty($request->end_date)) {
    //                 $jobs = $jobs
    //                     ->whereBetween('jobs.created_at', [$request->start_date, $request->end_date]);
    //             }
    //             $jobs = $jobs->orderBy('updated_at', 'desc')->paginate($request->per_page);
    //         } elseif ($id) {
    //             $jobs = Job::select('jobs.id as id', 'estimates.id as estimate_id', 'estimates.module')
    //                 ->join('estimates', 'estimates.id', '=', 'jobs.estimate_id')
    //                 ->where(['jobs.id' => $id])
    //                 ->orderBy('jobs.updated_at', 'desc')
    //                 ->paginate($request->per_page);
    //         }
    //     } else {
    //         //Without pagination.
    //         if ($request->has('status') && !empty($request->status) && ($request->status == Job::JOB_ONSITE || $request->status == Job::JOB_INPROGRESS || $request->status == Job::JOB_DONE || $request->status == Job::JOB_COLLECT)) {
    //             if (Auth::user()->roles[0]->id == 2) {
    //                 $jobs = Job::select('jobs.*', 'estimates.module')
    //                     ->join('estimates', 'estimates.id', '=', 'jobs.estimate_id')
    //                     ->join('customer', 'customer.id', '=', 'estimates.user_id')
    //                     ->join('vehicle_make', 'vehicle_make.id', '=', 'estimates.make_id')
    //                     ->join('vehicle_model', 'vehicle_model.id', '=', 'estimates.model_id')
    //                     ->where('jobs.status', $request->status);
    //             } else {
    //                 $jobs = Job::select('jobs.*', 'estimates.module')
    //                     ->join('estimates', 'estimates.id', '=', 'jobs.estimate_id')
    //                     ->join('customer', 'customer.id', '=', 'estimates.user_id')
    //                     ->join('vehicle_make', 'vehicle_make.id', '=', 'estimates.make_id')
    //                     ->join('vehicle_model', 'vehicle_model.id', '=', 'estimates.model_id')
    //                     ->where('jobs.status', $request->status)
    //                     ->whereRaw('FIND_IN_SET(?, team)', [Auth::user()->id]);
    //             }

    //             // 🔍 SEARCH FILTER
    //             if ($request->has('search') && !empty($request->search)) {
    //                 $jobs = $jobs->where(function ($q) use ($request) {
    //                     $q->where('customer.first_name', 'LIKE', "%{$request->search}%")
    //                         ->orWhere('customer.last_name', 'LIKE', "%{$request->search}%")
    //                         ->orWhere('customer.phone', 'LIKE', "%{$request->search}%")
    //                         ->orWhere('estimates.registration', 'LIKE', "%{$request->search}%")
    //                         ->orWhere('vehicle_make.make', 'LIKE', "%{$request->search}%")
    //                         ->orWhere('vehicle_model.model', 'LIKE', "%{$request->search}%");
    //                 });
    //             }

    //             // if ($request->has('search') && !empty($request->search)) {
    //             //     $jobs = $jobs->where(function ($query) use ($request) {
    //             //         $query->where('first_name', 'LIKE', "%$request->search%")
    //             //             ->orWhere('last_name', 'LIKE', "%$request->search%");
    //             //     });
    //             // }
    //             if ($request->has('customer_id') && !empty($request->customer_id)) {
    //                 $jobs = $jobs->where('customer.id', $request->customer_id);
    //             }
    //             if ($request->has('start_date') && !empty($request->start_date) && $request->has('end_date') && !empty($request->end_date)) {
    //                 $jobs = $jobs
    //                     ->whereBetween('jobs.created_at', [$request->start_date, $request->end_date]);
    //             }
    //             $jobs = $jobs->orderBy('updated_at', 'desc')->get();
    //         } elseif ($id) {
    //             $jobs = Job::select('jobs.id as id', 'jobs.*', 'estimates.id as estimate_id', 'estimates.module')->join('estimates', 'estimates.id', '=', 'jobs.estimate_id')->where(['jobs.id' => $id])->orderBy('jobs.updated_at', 'desc')->get();
    //         }
    //     }
    //     foreach ($jobs as $job) {
    //         // Get cutomer details.
    //         $customer = Customer::select('customer.*')->join('estimates', 'estimates.user_id', '=', 'customer.id')->where('estimates.id', $job->estimate_id)->first();
    //         if ($customer != null) {
    //             $job->customer = $customer;
    //             // Set services.
    //             $job->services = EstimateService::where('estimate_id', $job->estimate_id)->get();
    //             foreach ($job->services as $service) {
    //                 if ($service->service_id != null) {
    //                     $service->service_id = Service::where('id', $service->service_id)->first();
    //                 } else {
    //                     $service->service_id = $service->temp_service;
    //                 }
    //             }

    //             // Set estimate data.
    //             $estimateData = Estimate::where('id', $job->estimate_id)->first();
    //             $job->estimateData = $estimateData;
    //             // Set make.
    //             $job->make = VehicleMake::where('id', $estimateData->make_id)->first();

    //             //Set model.
    //             $job->model = VehicleModel::where('id', $estimateData->model_id)->first();

    //             // Jon or estimate created by.
    //             $createdBy = User::select('first_name', 'last_name')->where('id', $estimateData->created_by)->first();
    //             $estimateData->created_by =  $createdBy->first_name . " " . $createdBy->last_name;

    //             // files
    //             if (!empty($estimateData->files)) {
    //                 $files = explode(",", $estimateData->files);
    //                 if (!empty($estimateData->files)) {
    //                     foreach ($files as $file) {
    //                         $filedata = File::find($file);
    //                         if (!empty($filedata)) {
    //                             $image[] = [
    //                                 'id' => $filedata->id,
    //                                 'path' => url(Storage::url($filedata->path)),
    //                             ];
    //                         }
    //                     }
    //                     if (!empty($image)) {
    //                         $job->estimateData->filesData = $image;
    //                     }
    //                 }
    //             }
    //         }
    //         // Get team members details.
    //         if ($job->team) {
    //             $teams = explode(",", $job->team);
    //             $members = [];
    //             foreach ($teams as $team) {
    //                 $member = User::where('id', $team)->first();
    //                 if ($member != null) {
    //                     $memberData['id'] = $member->id;
    //                     $memberData['first_name'] = $member->first_name;
    //                     $memberData['last_name'] = $member->last_name;
    //                 } else {
    //                     $memberData = null;
    //                 }
    //                 $members[] = $memberData;
    //             }
    //         } else {
    //             $members = [];
    //         }
    //         $job->team_members = $members;

    //         //Get job log.
    //         $data = [
    //             'activity' => [$job->module],
    //             'instance_id' => $job->id,
    //         ];
    //         $logs = LogRepository::GetLog($data);
    //         // Make message for log show.

    //         foreach ($logs as $log) {

    //             $user = User::find($log->user_id);
    //             // $roleName = $user->getRoleNames();
    //             $module = Module::find($log->activity);

    //             // $log['message'] = $user->first_name. " " . $user->last_name. " has " .  $log->action. " " . $module->module;

    //             $log['message'] = ($user ? $user->first_name . " " . $user->last_name : 'Unknown User')
    //                 . " has " . $log->action
    //                 . " " . ($module ? $module->module : 'Unknown Module');

    //             $logdata[] = $log;
    //         }
    //         if (empty($logdata)) {
    //             $job->logs = [];
    //         } else {
    //             $job->logs = $logdata;
    //         }
    //     }
    //     return response()->json(['data' => $jobs, 'status' => 1, 'message' => 'Job List'], 200);
    // }

    public static function AllJob($request, $id = null)
    {
        $jobs = collect();

        if ($request->filled('per_page') && !empty($request->per_page)) {
            // With pagination
            if ($request->filled('status') && in_array($request->status, [
                Job::JOB_ONSITE,
                Job::JOB_INPROGRESS,
                Job::JOB_DONE,
                Job::JOB_COLLECT,
                Job::ALL_STATUS
            ])) {
                $jobs = Job::select('jobs.*', 'estimates.module')
                    ->join('estimates', 'estimates.id', '=', 'jobs.estimate_id')
                    ->join('customer', 'customer.id', '=', 'estimates.user_id')
                    ->join('vehicle_make', 'vehicle_make.id', '=', 'estimates.make_id')
                    ->join('vehicle_model', 'vehicle_model.id', '=', 'estimates.model_id');

                if ($request->status != 5) { // only filter if not 5
                    $jobs->where('jobs.status', $request->status);
                }

                // ADD ONLY THIS LINE
                self::applyCommonFilters($jobs, $request);

                if (Auth::user()->roles[0]->id != 2) {
                    $jobs->where(function ($q) {
                        $q->whereNull('team')
                            ->orWhere('team', '')
                            ->orWhereRaw('FIND_IN_SET(?, team)', [Auth::user()->id]);
                    });
                }

                if ($request->filled('search')) {
                    $jobs->where(function ($q) use ($request) {
                        $q->where('customer.first_name', 'LIKE', "%{$request->search}%")
                            ->orWhere('customer.last_name', 'LIKE', "%{$request->search}%")
                            ->orWhere('customer.phone', 'LIKE', "%{$request->search}%")
                            ->orWhere('estimates.registration', 'LIKE', "%{$request->search}%")
                            ->orWhere('vehicle_make.make', 'LIKE', "%{$request->search}%")
                            ->orWhere('vehicle_model.model', 'LIKE', "%{$request->search}%");
                    });
                }

                if ($request->filled('customer_id')) {
                    $jobs->where('customer.id', $request->customer_id);
                }

                if ($request->filled('start_date') && $request->filled('end_date')) {
                    $jobs->whereBetween('jobs.created_at', [$request->start_date, $request->end_date]);
                }

                $jobs = $jobs->orderBy('updated_at', 'desc')->paginate($request->per_page);
            } elseif ($id) {
                $jobs = Job::select('jobs.id as id', 'estimates.id as estimate_id', 'estimates.module')
                    ->join('estimates', 'estimates.id', '=', 'jobs.estimate_id')
                    ->where('jobs.id', $id)
                    ->paginate($request->per_page);
            }
        } else {
            // Without pagination
            if ($request->filled('status') && in_array($request->status, [
                Job::JOB_ONSITE,
                Job::JOB_INPROGRESS,
                Job::JOB_DONE,
                Job::JOB_COLLECT,
                Job::ALL_STATUS
            ])) {
                $jobs = Job::select('jobs.*', 'estimates.module')
                    ->join('estimates', 'estimates.id', '=', 'jobs.estimate_id')
                    ->join('customer', 'customer.id', '=', 'estimates.user_id')
                    ->join('vehicle_make', 'vehicle_make.id', '=', 'estimates.make_id')
                    ->join('vehicle_model', 'vehicle_model.id', '=', 'estimates.model_id');

                if ($request->status != 5) { // only filter if not 5
                    $jobs->where('jobs.status', $request->status);
                }

                // 🔽 ADD ONLY THIS LINE
                self::applyCommonFilters($jobs, $request);

                if (Auth::user()->roles[0]->id != 2) {
                    $jobs->where(function ($q) {
                        $q->whereNull('team')
                            ->orWhere('team', '')
                            ->orWhereRaw('FIND_IN_SET(?, team)', [Auth::user()->id]);
                    });
                }

                if ($request->filled('search')) {
                    $jobs->where(function ($q) use ($request) {
                        $q->where('customer.first_name', 'LIKE', "%{$request->search}%")
                            ->orWhere('customer.last_name', 'LIKE', "%{$request->search}%")
                            ->orWhere('customer.phone', 'LIKE', "%{$request->search}%")
                            ->orWhere('estimates.registration', 'LIKE', "%{$request->search}%")
                            ->orWhere('vehicle_make.make', 'LIKE', "%{$request->search}%")
                            ->orWhere('vehicle_model.model', 'LIKE', "%{$request->search}%");
                    });
                }

                if ($request->filled('customer_id')) {
                    $jobs->where('customer.id', $request->customer_id);
                }

                if ($request->filled('start_date') && $request->filled('end_date')) {
                    $jobs->whereBetween('jobs.created_at', [$request->start_date, $request->end_date]);
                }

                $jobs = $jobs->orderBy('updated_at', 'desc')->get();
            } elseif ($id) {
                $jobs = Job::select('jobs.id as id', 'jobs.*', 'estimates.id as estimate_id', 'estimates.module')
                    ->join('estimates', 'estimates.id', '=', 'jobs.estimate_id')
                    ->where('jobs.id', $id)
                    ->orderBy('jobs.updated_at', 'desc')
                    ->get();
            }
        }

        // ===== Fallback: No status & no ID =====
        if ($jobs->count() === 0 && !$request->filled('status') && !$id) {
            $query = Job::select('jobs.*', 'estimates.module')
                ->join('estimates', 'estimates.id', '=', 'jobs.estimate_id')
                ->join('customer', 'customer.id', '=', 'estimates.user_id')
                ->join('vehicle_make', 'vehicle_make.id', '=', 'estimates.make_id')
                ->join('vehicle_model', 'vehicle_model.id', '=', 'estimates.model_id')
                ->orderBy('jobs.updated_at', 'desc');

            // ADD ONLY THIS LINE
            self::applyCommonFilters($query, $request);

            if (Auth::user()->roles[0]->id != 2) {
                $query->where(function ($q) {
                    $q->whereNull('team')
                        ->orWhere('team', '')
                        ->orWhereRaw('FIND_IN_SET(?, team)', [Auth::user()->id]);
                });
            }

            $jobs = $request->filled('per_page') ? $query->paginate($request->per_page) : $query->get();
        }

        // ========================== ENRICH JOB DATA ==========================
        foreach ($jobs as $job) {
            // Customer
            $customer = Customer::select('customer.*')
                ->join('estimates', 'estimates.user_id', '=', 'customer.id')
                ->where('estimates.id', $job->estimate_id)
                ->first();
            $job->customer = $customer;

            // Services
            $job->services = EstimateService::where('estimate_id', $job->estimate_id)->get();
            foreach ($job->services as $service) {
                $service->service_id = $service->service_id ? Service::find($service->service_id) : $service->temp_service;
            }

            // Estimate, Make, Model, Created by
            $estimateData = Estimate::find($job->estimate_id);
            $job->estimateData = $estimateData;

            if ($estimateData) {
                $job->make = VehicleMake::find($estimateData->make_id);
                $job->model = VehicleModel::find($estimateData->model_id);
                $createdBy = User::find($estimateData->created_by);
                $estimateData->created_by = $createdBy ? $createdBy->first_name . ' ' . $createdBy->last_name : null;

                // Files
                if (!empty($estimateData->files)) {
                    $files = explode(',', $estimateData->files);
                    $image = [];
                    foreach ($files as $file) {
                        $filedata = File::find($file);
                        if ($filedata) {
                            $image[] = ['id' => $filedata->id, 'path' => url(Storage::url($filedata->path))];
                        }
                    }
                    $job->estimateData->filesData = $image;
                }
            } else {
                // Safe defaults if estimate not found
                $job->make = null;
                $job->model = null;
                $job->estimateData = null;
            }

            // Team members
            $members = [];
            if ($job->team) {
                foreach (explode(',', $job->team) as $team) {
                    $member = User::find($team);
                    $members[] = $member ? ['id' => $member->id, 'first_name' => $member->first_name, 'last_name' => $member->last_name] : null;
                }
            }
            $job->team_members = $members;

            // Logs
            $logdata = [];
            $logs = LogRepository::GetLog(['activity' => [$job->module], 'instance_id' => $job->id]);
            foreach ($logs as $log) {
                $user = User::find($log->user_id);
                $module = Module::find($log->activity);
                $log['message'] = ($user ? $user->first_name . ' ' . $user->last_name : 'Unknown User')
                    . ' has ' . $log->action
                    . ' ' . ($module ? $module->module : 'Unknown Module');
                $logdata[] = $log;
            }
            $job->logs = $logdata ?: [];
        }


        return response()->json([
            'status' => 1,
            'message' => 'Job List',
            'count' => is_object($jobs) && method_exists($jobs, 'total') ? $jobs->total() : $jobs->count(),
            'data' => $jobs,
        ], 200);
    }

    private static function applyCommonFilters($query, $request)
    {
        // Customer Name (first, last, or full name)
        if ($request->filled('customer_name')) {
            $name = trim($request->customer_name);

            $query->where(function ($q) use ($name) {

                // Full name search (e.g. "Md Imtiyaj")
                if (str_contains($name, ' ')) {
                    $parts = array_filter(explode(' ', $name));

                    $q->where(function ($sub) use ($parts) {
                        $sub->where('customer.first_name', 'LIKE', '%' . $parts[0] . '%')
                            ->where('customer.last_name', 'LIKE', '%' . end($parts) . '%');
                    });
                }

                // First OR last name search
                $q->orWhere('customer.first_name', 'LIKE', "%{$name}%")
                ->orWhere('customer.last_name', 'LIKE', "%{$name}%");
            });
        }


        // Registration Number
        if ($request->filled('registration_no')) {
            $query->where('estimates.registration', 'LIKE', "%{$request->registration_no}%");
        }

        // Combined Make + Model Name Filter
        if ($request->filled('vehicle_name')) {
            $vehicleName = trim($request->vehicle_name);
            $parts = explode(' ', $vehicleName);

            $query->where(function ($q) use ($parts) {
                foreach ($parts as $part) {
                    $q->where(function ($sub) use ($part) {
                        $sub->where('vehicle_make.make', 'LIKE', "%{$part}%")
                            ->orWhere('vehicle_model.model', 'LIKE', "%{$part}%");
                    });
                }
            });
        }

        // Date Range
        if ($request->filled('date')) {
            $query->where('jobs.updated_at', 'LIKE', "%{$request->date}%");
        }

        return $query;
    }

    public static function UpdateStatus($request, $id)
    {
        // Check Permission.
        $permission = ['Add/Edit Job'];
        if (PermissionCheck($permission) === true) {
        } else {
            return PermissionCheck($permission);
        }
        if ($request->has('status_change') && !empty($request->status_change) && ($request->status_change == Job::JOB_ONSITE || $request->status_change == Job::JOB_INPROGRESS || $request->status_change == Job::JOB_DONE || $request->status_change == Job::JOB_COLLECT)) {
            if ($request->status_change == Job::JOB_ONSITE) {
                $jopStatus =  Job::where('id', $id)->update(['status' => $request->status_change]);
                $logStatus = Job::ONSITE;
                // Add log for job update.
                $data = [
                    'activity' => 2,
                    'instance_id' => $id,
                    'user_id' => Auth::id(),
                    'action' => $logStatus,
                ];
                // Call log function.
                get_log($data);
                return true;
            } elseif ($request->status_change == Job::JOB_INPROGRESS) {
                // set validation is job convert to in progress without team.
                // Check is team is assign or not.
                if ($request->team == null) {
                    return response()->json(['data' => [], 'status' => 0, "message" => "Please assign team members first !!"], 422);
                } else {
                    $jopStatus =  Job::where('id', $id)->update(['status' => $request->status_change]);
                    $logStatus = Job::INPROGRESS;
                    // Add log for job update.
                    $data = [
                        'activity' => 2,
                        'instance_id' => $id,
                        'user_id' => Auth::id(),
                        'action' => $logStatus,
                    ];
                    // Call log function.
                    get_log($data);
                    return true;
                }
            } elseif ($request->status_change == Job::JOB_DONE) {
                // set validation is job convert to in progress without team.
                // Check is team is assign or not.
                if ($request->has('team') && $request->team == null) {
                    return response()->json(['data' => [], 'status' => 0, "message" => "Please assign team members first !!"], 422);
                } else {
                    $jopStatus =  Job::where('id', $id)->update(['status' => $request->status_change, 'completed_on' =>  date('Y/m/d h:i:s', time())]);
                    $logStatus = Job::DONE;
                    // Add log for job update.
                    $data = [
                        'activity' => 2,
                        'instance_id' => $id,
                        'user_id' => Auth::id(),
                        'action' => $logStatus,
                    ];
                    // Call log function.
                    get_log($data);
                    // Call the event for creating bill/Invoice.
                    $eventData = [
                        'job_id' => $id,
                        'type' => $request->type,
                    ];
                    event(new MakeInvoiceEvent($eventData));

                    // get customer details.
                    $customer = Job::select('customer.*')->join('estimates', 'jobs.estimate_id', '=', 'estimates.id')->join('customer', 'estimates.user_id', '=', 'customer.id')->where('jobs.id', $id)->first();
                    // send sms notification for job done.
                    $to = "+44" . $customer->phone;
                    $message = "Hi " . $customer->first_name . " " . $customer->last_name . "Your car's service is now complete. You can pick up your vehicle at your earliest convenience. Feel free to contact us if you have any queries..";
                    $sms = sendTwilioSms($to, $message);

                    // send sms for invoice sending.
                    $to = "+44" . $customer->phone;
                    $message = "Hi " . $customer->first_name . " " . $customer->last_name . "Your car repair invoice has been sent to your email. Please check and confirm. Thank you! - " . $customer->company_name . ".";
                    $sms = sendTwilioSms($to, $message);

                    // send email for invoice sending.
                    // get invoice details.
                    $invoice = Invoice::select('invoices.id as invoice_id')->where('job_id', $id)->first();
                    $data = new \stdclass();
                    $data->invoice_ids = [$invoice->invoice_id];
                    InvoiceRepository::SendInvoice($data);
                    return true;
                }
            } elseif ($request->status_change == Job::JOB_COLLECT) {
                // set validation is job convert to in progress without team.
                // Check is team is assign or not.
                if ($request->has('team') && $request->team == null) {
                    return response()->json(['data' => [], 'status' => 0, "message" => "Please assign team members first !!"], 422);
                } else {
                    $jopStatus =  Job::where('id', $id)->update(['status' => $request->status_change, 'collected_on' => date('Y/m/d h:i:s', time())]);
                    $logStatus = Job::COLLECT;
                    // Add log for job update.
                    $data = [
                        'activity' => 2,
                        'instance_id' => $id,
                        'user_id' => Auth::id(),
                        'action' => $logStatus,
                    ];
                    // Call log function.
                    get_log($data);
                    return true;
                }
            }

            return response()->json(['data' => [], 'status' => 1, 'message' => 'Job status updated successfully.'], 200);
        }
    }

    // remove job.
    public static function RemoveJob($id)
    {
        // Check Permission.
        $permission = ['Delete Job'];
        if (PermissionCheck($permission) === true) {
        } else {
            return PermissionCheck($permission);
        }
        $job = Job::find($id);
        if ($job) {
            // estimate delete
            $estimate = Estimate::find($job->estimate_id)->delete();

            // comment delete
            $comment = Comment::where(['instance_id' => $id, 'activity' => 2])->delete();

            // delete job
            $job->delete();
            return response()->json(['data' => [], 'status' => 1, 'message' => 'Job Delete Successfuly!!'], 200);
        } else {
            return response()->json(['data' => [], 'status' => 0, 'message' => 'Un-progressable data'], 422);
        }
    }

    public static function getJobHistory($jobId, $module)
    {
        $history = [];

        $logs = LogRepository::GetLog([
            'activity' => [$module],
            'instance_id' => $jobId,
        ]);

        foreach ($logs as $log) {
            // Include only status-like actions (skip 'Create', etc.)
            if (in_array($log->action, ['Onsite', 'In Progress', 'Compeleted', 'Collected'])) {
                $history[] = [
                    'status' => ucfirst(str_replace('_', ' ', $log->action)),
                    'datetime' => $log->created_at,
                ];
            }
        }

        return $history;
    }

}
