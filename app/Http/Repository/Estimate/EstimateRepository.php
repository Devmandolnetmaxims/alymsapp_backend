<?php

namespace App\Http\Repository\Estimate;

use App\Http\Repository\Comment\CommentRepository;
use App\Http\Repository\Email\EmailRepository;
use App\Http\Repository\log\LogRepository;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Support\Facades\Storage;
use App\Models\EstimateService;
use App\Models\VehicleModel;
use App\Services\FCMService;
use App\Models\VehicleMake;
use App\Models\Estimate;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Comment;
use App\Models\Module;
use App\Models\User;
use App\Models\File;
use App\Models\log;
use App\Models\Job;
use App\Models\Role;
use Auth;

class EstimateRepository
{
    // Add Estimate.
    public static function AddEstimate($request)
    {
        // Check Permission.
        $permission = ['Add/Edit Estimate'];
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

        // create by
        $request['created_by'] = Auth::id();

        //Created as 
        $request['module'] = Module::MODULE_ESTIMATE;

        // Set balance;
        $request['amount'] = 0;

        // Get role name.
        $role = User::where('id', Auth::id())->first();
        $roleName = $role->getRoleNames()->first();
        if ($roleName == Role::SUPER_ADMIN || $roleName == Role::CHIEF) {
            // Add other values.
            if ($request->has('draft') && $request->draft == 1) {
                $request['status'] = Estimate::DRAFT;
            } elseif ($request->has('draft') && $request->draft == 0) {
                $request['status'] = Estimate::PENDING;
            }
        } elseif ($roleName == Role::TECHNICIAN) {
            $request['status'] = Estimate::REVIEW;
        } else {
            $request['status'] = Estimate::REVIEW;
        }

        // Get make and model ids.
        $ids = EstimateRepository::getMakeAndModelIds($request->make, $request->model);

        $request['make_id'] = $ids['make_id'];
        $request['model_id'] = $ids['model_id'];

        // Add Estimate.
        $estimate = Estimate::create($request->all());
        // Add log.
        $data = [
            'activity' => 1,
            'instance_id' => $estimate->id,
            'user_id' => Auth::id(),
            'action' => log::ACTION_CREATE,
        ];
        // Call log function.
        get_log($data);
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
            if ($request->has('draft') && $request->draft == 0) {
                // Mail a customer.
                $data = [
                    'customer_f_name' => $customer->first_name,
                    'customer_l_name' => $customer->last_name
                ];

                /* pdf attach with estimate. */
                $htmlContent = self::estimateAttchPdfTempate($estimate, $customer);
                $data = ['html_content' => $htmlContent];
                EmailRepository::SendEmail($customer->email, "Estimate Mail", "/Estimate/Email/EstimateSend", $data, $attachments);
            }
        }
        if ($estimate->draft == 0) {
            // Add log.
            $data = [
                'activity' => 1,
                'instance_id' => $estimate->id,
                'user_id' => Auth::id(),
                'action' => Estimate::PENDING,
            ];
            // Call log function.
            get_log($data);
        } elseif ($estimate->draft == 1) {
            // Add log.
            $data = [
                'activity' => 1,
                'instance_id' => $estimate->id,
                'user_id' => Auth::id(),
                'action' => Estimate::DRAFT,
            ];
            // Call log function.
            get_log($data);
        }
        if ($estimate && $estimateService) {
            // send sms notification.
            $to = "+44".$customer->phone;
            $make = VehicleMake::select('make')->where('id', $request->make_id)->first();
            $model = VehicleModel::select('model')->where('id', $request->model_id)->first();
            // $message = "Hi " . $customer->first_name . " " . $customer->last_name . "! An estimate for your car repair has been created. You can regitration number is ".$request->registration.". Let us know if you have any questions.                                     " . $customer->company_name . ".";
            $message = "We’ve prepared an estimate for your vehicle, a $make->make $model->model with the registration number $request->registration. Please feel free to reach out if you have any questions or need further assistance.                                     $customer->company_name";
            $sms = sendTwilioSms($to, $message);
            return response()->json(['data' => [], 'status' => 1, 'message' => 'Estimate Created Successfuly!!'], 200);
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

    public static function estimateAttchPdfTempate($estimate, $customer)
    {
        $estimateServices = EstimateService::where('estimate_id', $estimate->id)->get();
        $htmlContent = '<!DOCTYPE html>
        <html lang="en">
        <head>
          <meta charset="UTF-8">
          <meta name="viewport" content="width=device-width, initial-scale=1.0">
          <title>Billing PDF</title>
          <link rel="preconnect" href="https://fonts.googleapis.com">
        </head>
        <body style="font-family: Roboto, sans-serif; padding: 24px; background-color: #FFFFFF;">
          <div style="border: 1px solid #eaeaea; padding: 20px !important; display: block; border-radius: 29px; box-shadow: 0px 0px 20px #d2d2d2a8; width: 100%;">
            <table style="width: 100%; border-collapse: separate; border-spacing: 10px;">
              <tr>
                <td style="width: 50%;">
                  <img src="https://www.almysapp.com/assets/errors/logo/logo.jpg" alt="Brand Logo" style="width: 80%;">
                </td>
                <td style="width: 50%; text-align: right;">
                    <p style="font-size: 16px; margin-top: 5px;"><b style="font-weight: 500;">Quote #</b> <span> ' . htmlspecialchars($estimate->registration) . '</span></p>
                    <p style="font-size: 16px;  margin-top: 5px;"><b style="font-weight: 500;">Due date</b> : <span> ' . date('d-m-Y', strtotime($estimate->created_at)) . '</span></p>
                </td>
              </tr>
            </table>
        
        
        
             <table style="width: 100%; border-collapse: separate; border-spacing: 10px;">
              <tr>
              <th style="border: 1px solid #EEEEEE; background: #f9fafb; padding: 15px; font-size: 17px; font-weight: 500; text-align:left; width: 33.33%;">Quote from</th>
              <th style="border: 1px solid #EEEEEE; background: #f9fafb; padding: 15px; font-size: 17px; font-weight: 500; text-align:left; width: 33.33%;">Customer</th>
              </tr>
              <tr>
                <td style="padding: 16px; font-size: 16px; color: #404C58; background: #FFF; border-radius: 8px;">
                  <p style="margin: 0; color: #000; margin-bottom: 10px; font-weight:400;">Almys auto</p>
                  <p style="margin: 0;">Street King William, 123 Level 2, San Francisco, CA, USA</p>
                  <p style="margin: 0;">Support@Construction.com</p>
                  <p style="margin: 0;">(437)836-0796</p>
                </td>
                <td style="padding: 16px; font-size: 16px; color: #404C58; background: #FFF; border-radius: 8px;">
                  <p style="margin: 0; color: #000; margin-bottom: 10px; font-weight:400;">' . $customer->first_name . ' ' . $customer->last_name . '</p>
                  <p style="margin: 0;"> ' . ($customer->street ?? '') . ' ' . ($customer->area ?? '') . ' ' . ($customer->town ?? '') . ' ' . ($customer->post_code ?? '') . ' </p>
                  <p style="margin: 0;">' . $customer->email . '</p>
                  <p style="margin: 0;">' . $customer->phone . '</p>
                </td>
              </tr>
            </table>
        
            <table style="width: 100%; border-collapse: collapse; margin-top: 32px; border: 1px solid #eaeaea;">
              <thead>
                <tr>
                 <th style="border: 1px solid #EEEEEE; background: #f9fafb; padding: 15px; font-size: 17px; font-weight: 500; text-align:left;">Service name</th>
                  <th style="border: 1px solid #EEEEEE; background: #f9fafb; padding: 15px; font-size: 17px; font-weight: 500; text-align:left;">Registation No.</th>
                  <th style="border: 1px solid #EEEEEE; background: #f9fafb; padding: 15px; font-size: 17px; font-weight: 500; text-align:left;">Make</th>
                  <th style="border: 1px solid #EEEEEE; background: #f9fafb; padding: 15px; font-size: 17px; font-weight: 500; text-align:left;">Model</th>
                  <th style="border: 1px solid #EEEEEE; background: #f9fafb; padding: 15px; font-size: 17px; font-weight: 500; text-align:right;">Qty/Hrs.</th>
                  <th style="border: 1px solid #EEEEEE; background: #f9fafb; padding: 15px; font-size: 17px; font-weight: 500; text-align:right;">Rate</th>
                  </tr>
              </thead>
              <tbody>';
        if (count($estimateServices) > 0) {
            foreach ($estimateServices as $service) {
                $htmlContent .= '
                       <tr>
                          <td style="border: 1px solid #EEEEEE; padding: 15px; font-size: 15px; font-weight: 400;">' . Service::where('id',$service->service_id)->pluck('service')->first()  . '</td>
                          <td style="border: 1px solid #EEEEEE; padding: 15px; font-size: 15px; font-weight: 400;">' . $estimate->registration . '</td>
                          <td style="border: 1px solid #EEEEEE; padding: 15px; font-size: 15px; font-weight: 400; text-align:center;">' . VehicleMake::where('id',$estimate->make_id)->pluck('make')->first() . '</td>
                          <td style="border: 1px solid #EEEEEE; padding: 15px; font-size: 15px; font-weight: 400; text-align:center;">' . VehicleModel::where('id',$estimate->model_id)->pluck('model')->first() . '</td>
                          <td style="border: 1px solid #EEEEEE; padding: 15px; font-size: 15px; font-weight: 400; text-align:center;">' . $service->quantity . '</td>
                          <td style="border: 1px solid #EEEEEE; padding: 15px; font-size: 15px; font-weight: 400; text-align:right;">£' . $service->rate . '</td>
                        </tr>';
            }
        } else {
            $htmlContent .= '
                <tr>
                    <td style="border: 1px solid #EEEEEE; padding: 15px; font-size: 15px; font-weight: 400;" colspan="4">No service found.</td>
                </tr>';
        }
        $htmlContent .= '
              </tbody>
            </table>
        
            <table style="width: 100%; border-collapse: collapse; border: 1px solid #eaeaea; border-top: 0px; margin-bottom: 32px;">
              <tbody>
                <tr>
                  <td colspan="6"></td>
                  <td style="text-align: right; padding: 15px; font-size: 17px; font-weight: 500; border: 1px solid #EEEEEE; border-left:0px;">Subtotal</td>
                  <td style="text-align: right; padding: 15px; font-size: 17px; font-weight: 400; border: 1px solid #EEEEEE; border-left:0px;">£
                  '. number_format(number_format($estimate->grand_total ?? 0, 2) - number_format($estimate->net_vat ?? 0, 2),2).'
                  </td>
                </tr>
                <tr>
                  <td colspan="6"></td>
                  <td style="text-align: right; padding: 15px; font-size: 17px; font-weight: 500; border: 1px solid #EEEEEE; border-left:0px;">Discount</td>
                  <td style="text-align: right; padding: 15px; font-size: 17px; font-weight: 400; border: 1px solid #EEEEEE; border-left:0px;">£'.  number_format($estimate->net_discount ?? 0, 2) .'</td>
                </tr>
                <tr>
                  <td colspan="6"></td>
                  <td style="text-align: right; padding: 15px; font-size: 17px; font-weight: 500; border: 1px solid #EEEEEE; border-left:0px;">Total</td>
                  <td style="text-align: right; padding: 15px; font-size: 20px; font-weight: 400; border: 1px solid #EEEEEE; border-left:0px;">£'. number_format($estimate->net_total ?? 0, 2) .' </td>
                </tr>
              </tbody>
            </table>
        
           <table style="margin: 0 auto; text-align: center;">
          <tr>
            <td style="font-family: "Roboto", serif;">
              <b style="font-size:18px; font-weight:500; color:#3b4b85; font-family: "Roboto", serif;">
                Thankyou!
              </b> 
              for choosing Almys Auto
            </td>
          </tr>
        </table>
          
        
          </div>
        </body>
        </html>
        
        ';
        return $htmlContent;
    }

    // Estimate Edit.
    public static function EditEstimate($request, $id)
    {
        // Check Permission.
        $permission = ['Add/Edit Estimate'];
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
        if ($request->has('draft') && $request->draft == 1) {
            $request['status'] = Estimate::DRAFT;
        } elseif ($request->has('draft') && $request->draft == 0) {
            $request['status'] = Estimate::PENDING;
        }
        // Update Estimate.
        if (Estimate::where('id', $id)->exists()) {
            // Get make and model ids.
            $ids = EstimateRepository::getMakeAndModelIds($request->make, $request->model);

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
            // Add log for update.
            $data = [
                'activity' => 1,
                'instance_id' => $estimate->id,
                'user_id' => Auth::id(),
                'action' => log::ACTION_UPDATE,
            ];
            // Call log function.
            get_log($data);
            if ($request->has('draft') && $request->draft == 0) {
                $data = [
                    'customer_f_name' => $customer->first_name,
                    'customer_l_name' => $customer->last_name
                ];
                // Mail a customer.
                EmailRepository::SendEmail($customer->email, "Estimate Mail", "/Estimate/Email/EstimateSend", $data, $attachments);
            }
            if ($estimate->draft == 0) {
                // Add log for pending.
                $data = [
                    'activity' => 1,
                    'instance_id' => $estimate->id,
                    'user_id' => Auth::id(),
                    'action' => Estimate::PENDING,
                ];
                // Call log function.
                get_log($data);
            } elseif ($estimate->draft == 1) {
                // Add log for draft.
                $data = [
                    'activity' => 1,
                    'instance_id' => $estimate->id,
                    'user_id' => Auth::id(),
                    'action' => Estimate::DRAFT,
                ];
                // Call log function.
                get_log($data);
            }
            return response()->json(['data' => [], 'status' => 1, 'message' => 'Estimate Update Successfuly!!'], 200);
        } else {
            return response()->json(['data' => [], 'status' => 0, 'message' => 'Somethign Wrong!!'], 500);
        }
    }

    // Estimate List.
    public static function AllList($request, $id)
    {
        // Check Permission.
        $permission = ['Show Estimate'];
        if (PermissionCheck($permission) === true) {
        } else {
            return PermissionCheck($permission);
        }
        // Set default per page.
        if ($id) {
            //Details page.
            return (new self)->ListById($id);
        } elseif (($request->has('customer') && !empty($request->customer)) || ($request->has('status') && !empty($request->status)) || ($request->has('tab_status') && !empty($request->tab_status))) {
            return (new self)->ListByName($request);
        } else {
            if ($request->has('per_page') && !empty($request->per_page)) {
                $estimates = Estimate::orderBy('id', 'desc')->paginate($request->per_page);
            } else {
                $estimates = Estimate::orderBy('id', 'desc')->all();
            }
            if (count($estimates) > 0) {
                return response()->json(['data' => $estimates, 'status' => 1, 'message' => 'Estimate Data!!'], 200);
            } else {
                return response()->json(['data' => [], 'status' => 1, 'message' => 'No Data Found!!'], 200);
            }
        }
    }

    // Estimate lisy by id.
    public static function ListById($id)
    {
        $estimate = Estimate::find($id);
        //Get estimate log.
        $data = [
            'activity' => [1],
            'instance_id' => $id,
        ];
        $logs = LogRepository::GetLog($data);

        // Get Comments.
        $commentDatas = [
            'activity' => 1,
            'instance_id' => $id
        ];
        $comments = CommentRepository::GetComment($commentDatas);

        if (!$estimate) {
            return response()->json(['data' => [], 'status' => 1, 'message' => 'No Data Found!!'], 200);
        }

        // Make
        if (!empty($estimate->make_id)) {
            $make = VehicleMake::find($estimate->make_id);
            $estimate['make'] = [
                'id' => $make->id,
                'make' => $make->make,
            ];
        } else {
            $estimate['make'] = null;
        }

        // Model
        if (!empty($estimate->model_id)) {
            $model = VehicleModel::find($estimate->model_id);
            $estimate['model'] = [
                'id' => $model->id,
                'model' => $model->model,
            ];
        } else {
            $estimate['model'] = null;
        }


        // files
        $files = explode(",", $estimate->files);
        if (!empty($estimate->files)) {
            foreach ($files as $file) {
                $filedata = File::find($file);
                if (!empty($filedata)) {
                    $image[] = [
                        'id' => $filedata->id,
                        'path' => url(Storage::url($filedata->path)),
                        "type" => $filedata->extension
                    ];
                }
            }
            if (!empty($image)) {
                $estimate['Files'] = $image;
            }
        }

        // Services.
        $estimateServices = EstimateService::where('estimate_id', $estimate->id)->get();
        if (count($estimateServices) > 0) {
            foreach ($estimateServices as $estimateService) {
                if (!empty($estimateService->service_id)) {
                    $service = Service::find($estimateService->service_id);
                    unset($estimateService['service_id']);
                    $estimateService['service_id'] = ['id' => $service->id, 'service' => $service->service];
                } else {
                    if (!empty($estimateService->temp_service) && $estimateService->temp_service != null) {
                        $estimateService['service_id'] = [];
                    }
                }
                $estimateData[] = $estimateService;
            }
        } else {
            $estimateData = [];
        }

        $estimate['services'] = $estimateData;

        // Customers.
        $customerData = Customer::where('id', $estimate->user_id)->get();
        $estimate['customer'] = $customerData;
        // Make message for log show.
        foreach ($logs as $log) {
            $user = User::find($log->user_id);
            $roleName = $user->getRoleNames();
            $module = Module::find($log->activity);
            $log['message'] = $roleName[0] . " has " .  $log->action . " " . $module->module;
        }
        $estimate['logs'] = $logs;

        // Comments
        $estimate['comments'] = $comments;

        // Is Estimate already conver to job.
        $isConvert = Job::where('estimate_id', $estimate->id)->exists();
        $estimate->already_convert = $isConvert;

        if ($estimate) {
            return response()->json(['data' => $estimate, 'status' => 1, 'message' => 'Estimate Data!!'], 200);
        } else {
            return response()->json(['data' => [], 'status' => 1, 'message' => 'No Data Found!!'], 200);
        }
    }

    // Estimate list by customer-name.
    public static function ListByName($request)
    {
        // query.
        $estimates = Estimate::select('estimates.*')
            ->leftJoin('customer', 'customer.id', '=', 'estimates.user_id')
            ->where('module', Module::MODULE_ESTIMATE);
        if ($request->has('customer') && !empty($request->customer)) {
            $estimates = $estimates->where(function ($query) use ($request) {
                $query->where('first_name', 'like', "%{$request->customer}%")
                    ->orWhere('last_name', 'like', "%{$request->customer}%");
            });
        }

        if ($request->has('tab_status') && $request->tab_status == Estimate::APPROVED) {
            $estimates = $estimates->where(function ($query) use ($request) {
                $query->where('status', "Approved");
            })->orderBy('approved_on', 'desc');
        } else {
            if ($request->has('status') && !empty($request->status) && $request->status != "all") {
                $estimates = $estimates->where(function ($query) use ($request) {
                    $query->where('status', $request->status);
                })->orderBy('updated_at', 'desc');
            } else {
                $estimates = $estimates->where(function ($query) use ($request) {
                    $query->whereIn('status', [Estimate::PENDING, Estimate::DRAFT, Estimate::REVIEW]);
                })->orderBy('updated_at', 'desc');
            }
        }
        if ($request->has('per_page') && !empty($request->per_page)) {
            $estimates = $estimates->paginate($request->per_page);
        } else {
            $estimates = $estimates->get();
        }
        if (!empty($estimates)) {
            foreach ($estimates as $estimate) {
                if (!empty($estimate->user_id)) {
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

        if ($estimates) {
            return response()->json(['data' => $estimates, 'status' => 1, 'message' => 'Estimate Data!!'], 200);
        } else {
            return response()->json(['data' => [], 'status' => 1, 'message' => 'No Data Found!!'], 200);
        }
    }

    // Remove Estimate.
    public static function RemoveEstimate($id)
    {
        // Check Permission.
        $permission = ['Delete Estimate'];
        if (PermissionCheck($permission) === true) {
        } else {
            return PermissionCheck($permission);
        }
        $estimate = Estimate::find($id);
        if (!$estimate) {
            return response()->json(['data' => [], 'status' => 1, 'message' => 'Un-progressable data!!'], 422);
        }
        if ($estimate) {
            // check if this estimate already convert to job.
            $isConvert = Job::where('estimate_id', $estimate->id)->exists();
            if ($isConvert) {
                return response()->json(['data' => [], 'status' => 1, 'message' => 'Estimate already convert to job. Please remove job first.'], 422);
            }
            // remove images.
            if (!empty($estimate->files)) {
                $files = explode(",", $estimate['files']);
                foreach ($files as $file) {
                    $files = File::find($file);
                    if ($files) {
                        $files->delete();
                    }
                }
            }

            // remove estimate services.
            $estimatServices = EstimateService::where('estimate_id', $estimate->id)->get();
            if (count($estimatServices) > 0) {
                foreach ($estimatServices as $estimatService) {
                    EstimateService::find($estimatService->id)->delete();
                }
            }

            // Remove comment
            $comment = Comment::where(['instance_id' => $id, 'activity' => 1])->delete();
            // remove estimate.
            $estimate->delete();
            if ($estimate) {
                return response()->json(['data' => [], 'status' => 1, 'message' => 'Estimate Delete Successfuly!!'], 200);
            } else {
                return response()->json(['data' => [], 'status' => 0, 'message' => 'Something wrong!!'], 500);
            }
        }
    }

    public static function AllStatus($request, $id)
    {
        // Check Permission.
        $permission = ['Add/Edit Estimate'];
        if (PermissionCheck($permission) === true) {
        } else {
            return PermissionCheck($permission);
        }
        // Check is estimate is present or not
        $estimate = Estimate::find($id);
        if ($estimate) {
            if ($request->status == Estimate::APPROVED) {
                $estimate->update(['status' => Estimate::APPROVED, 'approved_on' => date('Y-m-d H:i:s'), 'approved_by' => Auth::id()]);
                // Add log for approved.
                $data = [
                    'activity' => 1,
                    'instance_id' => $estimate->id,
                    'user_id' => Auth::id(),
                    'action' => Estimate::APPROVED,
                ];
                // Call log function.
                get_log($data);
            } elseif ($request->status == Estimate::PENDING) {
                // Find Customer for sending mail.
                $customer = Customer::find($estimate->user_id);
                if (!empty($estimate->files)) {
                    $image_ids = explode(",", $request['files']);
                    foreach ($image_ids as $image_id) {
                        $files = File::find($image_id);
                        $attachments[] = Attachment::fromStorage($files->path);
                    }
                } else {
                    $attachments = [];
                }
                // Mail a customer.
                EmailRepository::SendEmail($customer->email, "Estimate Mail", "/Estimate/Email/EstimateSend", $data = null, $attachments);
                // Add log for pending.
                $data = [
                    'activity' => 1,
                    'instance_id' => $estimate->id,
                    'user_id' => Auth::id(),
                    'action' => Estimate::PENDING,
                ];
                // Call log function.
                get_log($data);
                $estimate->update(['status' => Estimate::PENDING]);
            } elseif ($request->status == Estimate::DRAFT) {
                // Add log for draft.
                $data = [
                    'activity' => 1,
                    'instance_id' => $estimate->id,
                    'user_id' => Auth::id(),
                    'action' => Estimate::DRAFT,
                ];
                // Call log function.
                get_log($data);
                $estimate->update(['status' => Estimate::DRAFT]);
            }
            return response()->json(['data' => [], 'status' => 1, 'message' => 'Estimate Status Change Successfuly!!'], 200);
        } else {
            return response()->json(['data' => [], 'status' => 0, 'message' => 'Something wrong!!'], 500);
        }
    }
}
