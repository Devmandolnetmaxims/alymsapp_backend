<?php

namespace App\Http\Repository\Customer;

use App\Models\Estimate;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Invoice;
use App\Models\VehicleMake;
use App\Models\VehicleModel;
use App\Models\Job;
use Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\EstimateService;

class CustomerRepository
{
    // Add Customer.
    public static function AddCustomer($request) {
        // Check Permission.
        $permission = ['Add/Edit Customer'];
        if(PermissionCheck($permission) === true) {
            
        } else {
            return PermissionCheck($permission);
        }
        $customer = Customer::create($request->all());
        if($customer) {
            return response()->json(['data' => [], 'status' => 1, 'message' => 'Customer Created Sucessfuly!!'], 200);
        } else {
            return response()->json(['data' => [], 'status' => 0, 'message' => 'Something Wrong!!'], 500);
        }
    }

    // Edit Customer.
    public static function EditCustomer($request, $id) {
        // Check Permission.
        $permission = ['Add/Edit Customer'];
        if(PermissionCheck($permission) === true) {
            
        } else {
            return PermissionCheck($permission);
        }
        // Find Customer Details.
        $customer = Customer::find($id);
        if($customer) {
            $customer->update($request->all());
            return response()->json(['data' => [], 'status' => 1, 'message' => 'Customer Update Sucessfuly!!'], 200);
        } else {
            return response()->json(['data' => [], 'status' => 0, 'message' => 'Something Wrong!!'], 500);
        }
    }

    // Delete Customer.
    // public static function RemoveCustomer($id) {
    //       // Check Permission.
    //       $permission = ['Delete Customer'];
    //       if(PermissionCheck($permission) === true) {
              
    //       } else {
    //           return PermissionCheck($permission);
    //       }
    //     // Check Customer is present or not.
    //     $customer = Customer::find($id);
    //     if($customer) {
    //         if(Estimate::where("user_id", $id)->exists()) {
    //             return response()->json(['data' => [], 'status' => 0, 'message' => 'Customer Already in used. Please delete that first.'], 422);
    //         }
    //         $customer->delete();
    //         return response()->json(['data' => [], 'status' => 1, 'message' => 'Customer Delete Sucessfuly!!'], 200);
    //     } else {
    //         return response()->json(['data' => [], 'status' => 0, 'message' => 'Un-processable data!!'], 422);
    //     }
    // }

    public static function RemoveCustomer($id)
    {
        // ✅ Check Permission
        $permission = ['Delete Customer'];
        if (PermissionCheck($permission) !== true) {
            return PermissionCheck($permission);
        }

        // ✅ Check Customer existence
        $customer = Customer::find($id);
        if (!$customer) {
            return response()->json([
                'data' => [],
                'status' => 0,
                'message' => 'Un-processable data!!'
            ], 422);
        }

        // ✅ Prevent delete if used in estimates
        if (Estimate::where("user_id", $id)->exists()) {
            return response()->json([
                'data' => [],
                'status' => 0,
                'message' => 'Customer already in use. Please delete that first.'
            ], 422);
        }

        // ✅ Prepare unique suffix
        $suffix = '_' . ($customer->phone ?? 'NA') . '_' . $customer->id;

        // ✅ Update phone/email before soft delete
        $customer->update([
            'phone' => '0',
            'email' => $customer->email ? $customer->email . $suffix : null,
        ]);

        // ✅ Now soft delete
        $customer->delete();

        return response()->json([
            'data' => [],
            'status' => 1,
            'message' => 'Customer deleted successfully!'
        ], 200);
    }


    // Customer List.
    public static function AllList($request, $id) {
        // Check Permission.
        $permission = ['Show Customer'];
        if(PermissionCheck($permission) === true) {
            
        } else {
            return PermissionCheck($permission);
        }
        // Set default per page.
        if($id) {
            return (new self)->ListById($id);
        } elseif(($request->has('customer') && !empty($request->customer) || ($request->has('company_type') && !empty($request->company_type)))) {
            return (new self)->ListByName($request);
        } else {
            if(!empty($request->per_page) || $request->per_page != "") {
                $customers = Customer::select('customer.*','company_types.name as company_type_name')
            ->leftjoin('company_types', 'company_types.id', '=', 'customer.company_type')
            ->orderBy('id', 'desc')
            ->paginate($request->per_page);
            } else {
                $customers = Customer::select('customer.*','company_types.name as company_type_name')
                ->leftjoin('company_types', 'company_types.id', '=', 'customer.company_type')
                ->orderBy('id', 'desc')
                ->get();
                foreach($customers as $customer) {
                    $allServices = Service::where('company_type', $customer->company_type)->get();
                    $customer['services'] = $allServices;
                }
            }
            return response()->json(['data' => $customers, 'status' => 1, 'message' => 'Company Data!!'], 200);
        }
    }

    // Customer lisy by id.
    public static function ListById($id) {
        $customer = Customer::select('customer.*','company_types.name as company_type_name')
        ->leftjoin('company_types', 'company_types.id', '=', 'customer.company_type')->where('customer.id', $id)->first();

        // get invoice.
        $invoices = Invoice::select('invoices.id as invoice_id', 'invoices.*', 'estimates.id as estimate_id', 'estimates.*')->join('jobs', 'jobs.id', '=', 'invoices.job_id')->join('estimates', 'estimates.id', '=', 'jobs.estimate_id')->join('customer', 'customer.id', '=', 'estimates.user_id')->where('customer.id', $id)->where('invoices.deleted_at', null)->get();

        // set balance.
        foreach($invoices as $invoice) {
            if($invoice->type == Invoice::BILL) {
                $balance = $invoice->net_total - $invoice->amount;
            } else {
                $balance = $invoice->grand_total - $invoice->amount;
            }
            $invoice->balance = $balance;
        }
        $customer->invoices = $invoices;

        // Worj history.
        $customer->work_history = Estimate::join('jobs', 'jobs.estimate_id', '=', 'estimates.id')->where('user_id', $id)->whereIn('jobs.status', [Job::JOB_DONE, Job::JOB_COLLECT])->where('jobs.deleted_at', null)->orderBy('jobs.id', 'desc')->get();
        foreach($customer->work_history as $history) {
            $history->make_name = VehicleMake::where('id', $history->make_id)->first();
            $history->model_name = VehicleModel::where('id', $history->model_id)->first();
        }
        if($customer) {
            return response()->json(['data' => $customer, 'status' => 1, 'message' => 'Company Data!!'], 200);
        } else {
            return response()->json(['data' => [], 'status' => 0, 'message' => 'Un-progressable data'], 422);
        }
    }

    // Employee list by employee-name.
    public static function ListByName($request) {
        $customer = Customer::select('customer.*', 'company_types.name as company_type_name')
        ->leftJoin('company_types', 'company_types.id', '=', 'customer.company_type');

        // get data from customer name
        if($request->has('customer') && !empty($request->customer)) {
            $customer = $customer->where(function ($query) use ($request) {
                $query->where('first_name', 'like', "%{$request->customer}%")
                    ->orWhere('last_name', 'like', "%{$request->customer}%")
                    ->orWhere('company_name', 'like', "%{$request->customer}%");
            });
        }

        // get dat from company type
        if($request->has('company_type') && !empty($request->company_type)) {
            if($request->company_type == "all") {
                //
            } else {
                $customer = $customer->where('company_types.id', $request->company_type);
            }
        }
        if(!empty($request->per_page) || $request->per_page != "") {
            $customer = $customer->orderBy('id', 'desc')
            ->paginate($request->per_page);
        } else {
            $customer = $customer->orderBy('id', 'desc')->get();
        }
        if($customer) {
            return response()->json(['data' => $customer, 'status' => 1, 'message' => 'Company Data!!'], 200);
        } else {
            return response()->json(['data' => [], 'status' => 0, 'message' => 'Un-progressable data'], 422);
        }
    }

    public static function workHistoryPdf($customer)
    {
        // Fetch invoices for this customer
        $invoices = Invoice::select(
                'invoices.*',
                'estimates.id as estimate_id'
            )
            ->join('jobs', 'jobs.id', '=', 'invoices.job_id')
            ->join('estimates', 'estimates.id', '=', 'jobs.estimate_id')
            ->where('estimates.user_id', $customer->id)
            ->orderBy('invoices.created_at', 'desc')
            ->get();

        // Prepare work history rows
        $workHistory = [];

        foreach ($invoices as $invoice) {
            $services = EstimateService::where('estimate_id', $invoice->estimate_id)->get();

            foreach ($services as $service) {
                $workHistory[] = [
                    'date'        => $invoice->created_at->format('d/m/Y'),
                    'invoice_no'  => $invoice->invoice_number,
                    'description' => $service->description,
                    'rate'  => number_format($service->rate, 2),
                    'cost_price'  => number_format($service->cost_rate, 2),
                    'amount'      => number_format($service->cost_rate * $service->quantity, 2),
                    'vat'         => number_format(($service->cost_rate * $service->quantity) * 0.2, 2),
                ];
            }
        }

        $pdf = Pdf::loadView('pdf.customer-work-history', [
            'customer'    => $customer,
            'workHistory' => $workHistory,
            'generatedAt' => now()->format('d/m/Y H:i:s')
        ]);

        return $pdf->download(
            'customer_work_history_' . $customer->id . '.pdf'
        );
    }
}
