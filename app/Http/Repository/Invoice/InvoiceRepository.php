<?php

namespace App\Http\Repository\Invoice;

use App\Http\Repository\Email\EmailRepository;
use App\Models\EstimateService;
use App\Models\InvoiceHistory;
use App\Models\Customer;
use App\Models\Estimate;
use App\Models\Invoice;
use App\Models\Service;
use App\Models\Job;
use Carbon\Carbon;
use Auth;
use Illuminate\Support\Facades\DB;
use PDF;
use App\Models\VehicleMake;
use App\Models\VehicleModel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceRepository
{
    // Create invoice.
    public static function CreateInvoice($eventdata) {
        // Check is job id is valid.
        $jobIsExists = Job::where('id', $eventdata['job_id'])->exists();
        if(!$jobIsExists) {
            return response()->json(['data' => [], 'status' => 0, 'message' => 'Job not found.']);
        }
        // Check is invoice already created.
        $invoiceIsExists = Invoice::where('job_id', $eventdata['job_id'])->exists();
        if($invoiceIsExists) {
            return response()->json(['data' => [], 'status' => 0, 'message' => 'Invoice already created.']);
        }
        // Genrate invoice id.
        $lastInvoice = Invoice::select('invoice_number')->latest()->first(); // Get lastest invoice id from database.
        if($lastInvoice == null) {
            $invoiceId = '001';
        } elseif($lastInvoice) {
            $invoiceId = '00'.$lastInvoice->invoice_number + 1;
        } else {
            return response()->json(['data' => [], 'status' => 0, 'message' => 'Something went wrong.']);
        }
        // get job data from database.
        $jobData = Job::select('customer.*')->join('estimates', 'estimates.id', '=', 'jobs.estimate_id')->join('customer', 'customer.id', '=', 'estimates.user_id')->where('jobs.id', $eventdata['job_id'])->first();
        $currentDate = date('Y-m-d');
        $addDays = $jobData->due_date == null ? '+7 days' : '+' . $jobData->due_date . ' days';
        $dueDate = date('Y-m-d', strtotime($currentDate . $addDays));

        // Create Invoice.
        $invoiceData = [
            'job_id' => $eventdata['job_id'],
            'invoice_number' => $invoiceId,
            'pay_status' => Invoice::STATUS_UNPAID,
            'type' => $eventdata['type'],
            'due_date' => $dueDate,
        ];

        // Create invoice.
        $invoice = Invoice::create($invoiceData);
         // Create invoice history.
         $invoiceHistory = new \stdclass();
         $invoiceHistory->invoice_id = $invoice->id;
         $invoiceHistory->action = InvoiceHistory::INVOICE_CREATE;
         InvoiceRepository::InvoiceHistory($invoiceHistory);
        if($invoice) {
            return response()->json(['data' => $invoice, 'status' => 1, 'message' => 'Invoice created successfully.'],200);
        } else {
            return response()->json(['data' => [], 'status' => 0, 'message' => 'Something went wrong.'],400);
        }
    }

    // Invoice update.
    public static function InvoiceUpdate($request, $id) {
        if($request->has('ids') && !empty($request->ids) && $id == null) {
            return InvoiceRepository::InvoiceUpdateMultiple($request);
        } else {
            return InvoiceRepository::InvoiceUpdateSingle($request, $id);
        }
    }

    // Single invoice update.
    // public static function InvoiceUpdateSingle($request, $id) {
    //     // Check is job id is valid.
    //     $invoiceIsExists = Invoice::where('id', $id)->exists();
    //     if(!$invoiceIsExists) {
    //         return response()->json(['data' => [], 'status' => 0, 'message' => 'Job not found.']);
    //     }

    //     // check if invoice is already paid.
    //     $invoiceIsExists = Invoice::where('id', $id)->where('pay_status', Invoice::STATUS_PAID)->exists();
    //     if($invoiceIsExists) {
    //         return response()->json(['data' => [], 'status' => 0, 'message' => 'Invoice is already paid.'],400);
    //     }
    //     // invoice number should not be duplicates.
    //     $invoiceIsExists = Invoice::where('id', '!=', $id)->where('invoice_number', $request->invoice_number)->exists();
    //     if($invoiceIsExists) {
    //         return response()->json(['data' => [], 'status' => 0, 'message' => 'Invoice number already exists.'],400);
    //     }
    //     $invoice = Invoice::select('jobs.id as job_id', 'jobs.*', 'estimates.id as estimate_id', 'estimates.*')->join('jobs', 'jobs.id', '=', 'invoices.job_id')->join('estimates', 'estimates.id', '=', 'jobs.estimate_id')->where('invoices.id', $id)->first();

    //     if($invoice) {
    //         // Update amount.
    //         $estimate = Estimate::find($invoice->estimate_id);
    //         $estimate->amount = $estimate->amount + $request->amount;
    //         if($estimate->amount <= $estimate->grand_total) {
    //             if($estimate->amount == $estimate->grand_total) {
    //                 // update the pay status as paid.
    //                 $invoice->pay_status = Invoice::STATUS_PAID;
    //             } else {
    //                 $invoice->pay_status = Invoice::STATUS_PARTIALPAID;
    //             }
    //             $estimate->save();
    //         } else {
    //             return response()->json(['data' => [], 'status' => 0, 'message' => 'Amount limit exced!!'],400);
    //         }

    //         // check invoice number should not be duplicates.
    //         $invoiceIsExists = Invoice::where('invoice_number', $invoice->invoice_number)->where('invoice_number', $request->invoice_number)->exists();
    //         if($invoiceIsExists) {
    //             return response()->json(['data' => [], 'status' => 0, 'message' => 'Invoice number already exists.'],400);
    //         }
    //         $invoice = Invoice::find($id)->update($request->only('pay_status', 'type', 'payment_type', 'last_received', 'due_date', 'invoice_number'));

    //          // Create invoice history.
    //         $invoiceHistory = new \stdclass();
    //         $invoiceHistory->invoice_id = $id;
    //         $invoiceHistory->action = InvoiceHistory::INVOICE_UPDATE;
    //         InvoiceRepository::InvoiceHistory($invoiceHistory);
    //     }

    //     if($invoice) {
    //         return response()->json(['data' => [], 'status' => 1, 'message' => 'Invoice updated successfully.'],200);
    //     } else {
    //         return response()->json(['data' => [], 'status' => 0, 'message' => 'Something went wrong.'],400);
    //     }
    // }

    public static function InvoiceUpdateSingle($request, $id)
    {
        DB::beginTransaction();

        try {

            // ================= EXISTING LOGIC (UNCHANGED) =================
            $invoiceIsExists = Invoice::where('id', $id)->exists();
            if (!$invoiceIsExists) {
                return response()->json([
                    'data' => [],
                    'status' => 0,
                    'message' => 'Job not found.'
                ]);
            }

            if (Invoice::where('id', $id)->where('pay_status', Invoice::STATUS_PAID)->exists()) {
                return response()->json([
                    'data' => [],
                    'status' => 0,
                    'message' => 'Invoice is already paid.'
                ], 400);
            }

            if ($request->filled('invoice_number')) {
                $invoiceIsExists = Invoice::where('id', '!=', $id)
                    ->where('invoice_number', $request->invoice_number)
                    ->exists();

                if ($invoiceIsExists) {
                    return response()->json([
                        'data' => [],
                        'status' => 0,
                        'message' => 'Invoice number already exists.'
                    ], 400);
                }
            }
            // ================= EXISTING INVOICE UPDATE =================
            Invoice::where('id', $id)->update([
                'type' => $request->type,
                'due_date' => $request->due_date,
                'invoice_number' => $request->invoice_number,
            ]);

            // ================= NEW: SERVICE EDITING =================
            if ($request->filled('services')) {

                $invoice = Invoice::find($id);
                $job = Job::find($invoice->job_id);
                $estimate = Estimate::find($job->estimate_id);

                // Remove old services
                EstimateService::where('estimate_id', $estimate->id)->delete();

                $netTotal = 0;
                $netDiscount = 0;

                foreach ($request->services as $service) {

                    $qty = $service['quantity'];
                    $rate = $service['rate'];
                    $discount = $service['discount'] ?? 0;

                    $lineTotal = ($qty * $rate) - $discount;

                    EstimateService::create([
                        'estimate_id'  => $estimate->id,
                        'service_id'   => $service['service_id'] ?? null,
                        'temp_service' => $service['temp_service'] ?? null,
                        'description'  => $service['description'] ?? null,
                        'quantity'     => $qty,
                        'rate'         => $rate,
                        'cost_rate'   => $service['cost_rate'] ?? 0,
                        'discount'     => $discount,
                        'total'        => $lineTotal,
                    ]);

                    $netTotal += ($qty * $rate);
                    $netDiscount += $discount;
                }

                // Update estimate totals
                $estimate->update([
                    'net_total'    => $netTotal,
                    'net_discount' => $netDiscount,
                    'grand_total'  => $netTotal - $netDiscount,
                ]);
            }

            // ================= EXISTING HISTORY (UNCHANGED) =================
            $invoiceHistory = new \stdclass();
            $invoiceHistory->invoice_id = $id;
            $invoiceHistory->action = InvoiceHistory::INVOICE_UPDATE;
            InvoiceRepository::InvoiceHistory($invoiceHistory);

            DB::commit();

            return response()->json([
                'data' => [],
                'status' => 1,
                'message' => 'Invoice updated successfully.'
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'data' => [],
                'status' => 0,
                'message' => 'Something went wrong.'
            ], 400);
        }
    }

    // Upate multiple invoice.
    public static function InvoiceUpdateMultiple($request) {
        // Check all invoces ids are related to same customer.
        $invoiceIds = Invoice::join('jobs', 'jobs.id', '=', 'invoices.job_id')->join('estimates', 'estimates.id', '=', 'jobs.estimate_id')->whereIn('invoices.id', $request->ids)->pluck('estimates.user_id')->toArray();
        if(count(array_unique($invoiceIds)) > 1) {
            return response()->json(['data' => [], 'status' => 0, 'message' => 'All invoices must be related to same customer.'],400);
        }

        // Ckeck if ids are valid.
        $invoiceIds = Invoice::whereIn('id', $request->ids)->pluck('id')->toArray();
        if(count($invoiceIds) != count($request->ids)) {
            return response()->json(['data' => [], 'status' => 0, 'message' => 'Invalid invoice ids.'],400);
        }

        // check if invoice is already paid.
        $invoiceIsExists = Invoice::whereIn('id', $request->ids)->where('pay_status', Invoice::STATUS_PAID)->exists();
        if($invoiceIsExists) {
            return response()->json(['data' => [], 'status' => 0, 'message' => 'Invoice is already paid.'],400);
        }

        // Check invoice number should not be duplicates.
        $invoiceIsExists = Invoice::whereIn('id', $request->ids)->where('invoice_number', $request->invoice_number)->exists();
        if($invoiceIsExists) {
            return response()->json(['data' => [], 'status' => 0, 'message' => 'Invoice number already exists.'],400);
        }

        // Check is given amount is not exced with ithe selected invoices.
        $invoiceAmounts = Invoice::select('estimates.*', 'invoices.*')->join('jobs', 'jobs.id', '=', 'invoices.job_id')->join('estimates', 'estimates.id', '=', 'jobs.estimate_id')->whereIn('invoices.id', $request->ids)->get();
        $totalPending = 0;
        $balance = 0;
        foreach($invoiceAmounts as $invoiceAmount) {
            if($invoiceAmount->type == Invoice::BILL) {
                $balance = $invoiceAmount->net_total - $invoiceAmount->amount;
            } else {
                $balance = $invoiceAmount->grand_total - $invoiceAmount->amount;
            }
            $totalPending = $balance + $totalPending;
            $balance = 0;
        };
        if($request->amount > $totalPending) {
            return response()->json(['data' => [], 'status' => 0, 'message' => 'Amount limit exced!!'],400);
        }

        $invoices = Invoice::select('invoices.*', 'invoices.id as invoice_id', 'jobs.id as job_id', 'jobs.*', 'estimates.id as estimate_id', 'estimates.*')
        ->join('jobs', 'jobs.id', '=', 'invoices.job_id')
        ->join('estimates', 'estimates.id', '=', 'jobs.estimate_id')
        ->whereIn('invoices.id', $request->ids)
        ->orderByRaw("
            CASE
                WHEN invoices.pay_status = '2' THEN 1
                WHEN invoices.pay_status = '3' THEN 2
                WHEN invoices.pay_status = '0' THEN 3
                ELSE 4
            END
        ")
        ->get();
        if($invoices) {
            // Update amount.
            foreach($invoices as $invoice) {
                $estimate = Estimate::withTrashed()->find($invoice->estimate_id);
                $invoice = Invoice::find($invoice->invoice_id);
                // Collect invoice history.
                $invoiceHistory = new \stdclass();
                $invoiceHistory->invoice_id = $invoice->id;
                $invoiceHistory->pay = $request->amount;
                if($invoice->type == Invoice::BILL) {
                    $balance = $estimate->net_total - $estimate->amount;
                    if($balance >= $request->amount) {
                        $estimate->amount = $estimate->amount + $request->amount;
                        $request->amount = 0;
                    } else {
                        $request->amount = $request->amount - $balance;
                        $estimate->amount = $estimate->amount + $balance;
                    }

                    // collect invoice history.
                    $invoiceHistory->balance = $estimate->net_total - $estimate->amount;
                    $invoiceHistory->amount = $estimate->amount;

                    if($estimate->amount == $estimate->net_total) {
                        // update the pay status as paid.
                        $invoice->pay_status = Invoice::STATUS_PAID;
                    } else {
                        $invoice->pay_status = Invoice::STATUS_PARTIALPAID;
                    }
                } else {
                    $balance = $estimate->grand_total - $estimate->amount;
                    if($balance >= $request->amount) {
                        $estimate->amount = $estimate->amount + $request->amount;
                        $request->amount = 0;
                    } else {
                        $request->amount = $request->amount - $balance;
                        $estimate->amount = $estimate->amount + $balance;
                    }

                    // collect invoice history.
                    $invoiceHistory->balance = $estimate->grand_total - $estimate->amount;
                    $invoiceHistory->amount = $estimate->amount;

                    if($estimate->amount == $estimate->grand_total) {
                        // update the pay status as paid.
                        $invoice->pay_status = Invoice::STATUS_PAID;
                    } else {
                        $invoice->pay_status = Invoice::STATUS_PARTIALPAID;
                    }
                }
                if($request->has('payment_type') && $request->payment_type != '') {
                    $invoice->payment_type = $request->payment_type;
                }

                if($request->has('last_received') && $request->last_received != '') {
                    $invoice->last_received = $request->last_received;
                }
                $estimate->save();
                $invoice->save();

                // Collect invoice history.
                $invoiceHistory->action = '4';
                $invoiceHistory->recived_at = $request->last_received;
                InvoiceRepository::InvoiceHistory($invoiceHistory);
            }
            return response()->json(['data' => [], 'status' => 1, 'message' => 'Invoice updated successfully.'],200);
        } else {
            return response()->json(['data' => [], 'status' => 0, 'message' => 'Something went wrong.'],400);
        }
    }

    // Get all invoice.
    public static function AllInvoice($request, $id) {
        if(!empty($id)) {
            return InvoiceRepository::InvoiceById($id);
        } else {
            return InvoiceRepository::InvoiceByList($request);
        }

    }

    // Get invoice by list.
    public static function InvoiceByList($request) {
        // check if due date is over.
        $overDueInvoice = Invoice::whereIn('pay_status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIALPAID])->where('due_date', '<', Carbon::now())->update(['pay_status' => Invoice::STATUS_OVERDUE]);

        // Collect invoices.
        $invoices = Invoice::select('invoices.*','invoices.id as invoice_id', 'invoices.updated_at as invoice_updated_at','jobs.id as job_id', 'jobs.*', 'estimates.id as estimate_id', 'estimates.*')->join('jobs', 'jobs.id', '=', 'invoices.job_id')->join('estimates', 'estimates.id', '=', 'jobs.estimate_id')->join('customer', 'customer.id', '=', 'estimates.user_id');

        // Search for invoice, email, customer.
        if($request->has('search') && !empty($request->search)) {
            $invoices = $invoices->where(function ($query) use ($request) {
                $query->where('invoices.invoice_number', 'like', '%' . $request->search . '%')
                      ->orWhere('customer.email', 'like', '%' . $request->search . '%')
                      ->orWhere('customer.first_name', 'like', '%' . $request->search . '%')
                      ->orWhere('customer.last_name', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by customer id.
        if($request->has('customer_id') && !empty($request->customer_id) && $request->customer_id != 'all') {
            $invoices = $invoices->where('estimates.user_id', $request->customer_id);
        }

        // Filter behalf of invoice status.
        $status = (string)$request->status;
        if($request->has('status') && isset($status) && $status != 'all') {
           $invoices = $invoices->where('invoices.pay_status', $request->status);
        }

        // Flite by date between.
        if($request->has('start_date') && !empty($request->start_date) && $request->has('end_date') && !empty($request->end_date)) {
            $invoices = $invoices->whereBetween('invoices.created_at', [$request->start_date, $request->end_date]);
        }

        // Filter by type.
        if($request->has('type') && !empty($request->type) && $request->type != 'all') {
            $invoices = $invoices->where('invoices.type', $request->type);
        }

        // Get all invoice.
        if($request->has('per_page') && !empty($request->per_page)) {
            $invoices = $invoices->orderby('invoices.created_at', 'desc')->paginate($request->per_page);
        } else{
            $invoices = $invoices->orderby('invoices.created_at', 'desc')->get();
        }
        $totalPending = 0;
        $totalVat = 0;
        $totalPaid = 0;
        $totalDue = 0;
        // dd($invoices);
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

            // Get invoice history.
            $invoice->invoice_history = InvoiceHistory::select('invoice_history.*', 'invoice_history.created_at as invoice_history_created_at', 'invoice_history.updated_at as invoice_history_updated_at', 'invoice_history.id as invoice_history_id', 'users.first_name', 'users.last_name','invoices.*')->where('invoice_id', $invoice->invoice_id)->join('users', 'users.id', '=', 'invoice_history.report_by')->join('invoices', 'invoices.id', '=', 'invoice_history.invoice_id')->get();
            foreach($invoice->invoice_history as $history) {
                if($history->action == InvoiceHistory::INVOICE_CREATE) {
                    $history->message = $type.' created successfully by ' . $history->first_name . ' ' . $history->last_name . '.';
                } elseif($history->action == InvoiceHistory::INVOICE_UPDATE) {
                    $history->message = $type.' updated successfully by ' . $history->first_name . ' ' . $history->last_name . '.';
                } elseif($history->action == InvoiceHistory::INVOICE_DELETE) {
                    $history->message = $type.' deleted successfully by ' . $history->first_name . ' ' . $history->last_name . '.';
                } elseif($history->action == InvoiceHistory::INVOICE_PAY) {
                    $history->message = $type.' paid report gerated by ' . $history->first_name . ' ' . $history->last_name . '.';
                }
            }

            // Services.
            $estimateServices = EstimateService::where('estimate_id', $invoice->estimate_id)->get();
            if(count($estimateServices) > 0) {
                $estimateData = [];
                foreach($estimateServices as $estimateService) {
                    if(!empty($estimateService->service_id)) {
                        $service = Service::find($estimateService->service_id);
                        unset($estimateService['service_id']);
                        $estimateService['service_id'] = ['id' => $service->id, 'service' => $service->service];
                    } else {
                        if(!empty($estimateService->temp_service) && $estimateService->temp_service != null) {
                            $estimateService['service_id'] = [];
                        }
                    }
                    $estimateData[] = $estimateService;
                }
            } else {
                $estimateData = [];
            }
            // Set service data.
            $invoice->services = $estimateData;
        }
        // Bank details
        $bankDetails = [
            'bank_name' => 'BARCLAYS BANK',
            'account_number' => '43230643',
            'sort_code' => '20-42-76'
        ];

        $invoiceData = [
            'total_pending' => $totalPending,
            'total_vat' => $totalVat,
            'total_paid' => $totalPaid,
            'total_overdue' => $totalDue,
            'invoice_list' => $invoices,
            'bank_details' => $bankDetails,
        ];
        return response()->json(['data' => $invoiceData, 'status' => 1, 'message' => 'Invoice List.'],200);
    }

    // Get invoice by id.
    public static function InvoiceById($id) {
        $invoice =  Invoice::select('invoices.*', 'invoices.id as invoice_id', 'invoices.updated_at as invoice_updated_at','jobs.id as job_id', 'jobs.*', 'estimates.id as estimate_id', 'estimates.*')->join('jobs', 'jobs.id', '=', 'invoices.job_id')->join('estimates', 'estimates.id', '=', 'jobs.estimate_id')->join('customer', 'customer.id', '=', 'estimates.user_id')->find($id);

        // Check is invoice id is valid.
        $invoiceIsExists = Invoice::where('id', $id)->exists();
        if(!$invoiceIsExists) {
            return response()->json(['data' => [], 'status' => 0, 'message' => 'Invoice not found.'],400);
        }
        // Services.
        $estimateServices = EstimateService::where('estimate_id', $invoice->estimate_id)->get();
        if(count($estimateServices) > 0) {
            foreach($estimateServices as $estimateService) {
                if(!empty($estimateService->service_id)) {
                    $service = Service::find($estimateService->service_id);
                    unset($estimateService['service_id']);
                    $estimateService['service_id'] = ['id' => $service->id, 'service' => $service->service];
                } else {
                    if(!empty($estimateService->temp_service) && $estimateService->temp_service != null) {
                        $estimateService['service_id'] = [];
                    }
                }
                $estimateData[] = $estimateService;
            }
        } else {
            $estimateData = [];
        }

        // Balance.
        if($invoice->type == Invoice::BILL) {
            $balance = $invoice->net_total - $invoice->amount;
        } else {
            $balance = $invoice->grand_total - $invoice->amount;
        }
        $invoice->balance = $balance;

        // Customers Details.
        $invoice->customer = Customer::withTrashed()->find($invoice->user_id);
        $invoice->services = $estimateData;

        // Get invoice history.
        $invoice->invoice_history = InvoiceHistory::select('invoice_history.*', 'invoice_history.id as invoice_history_id','invoice_history.created_at as invoice_history_created_at', 'invoice_history.updated_at as invoice_history_updated_at', 'users.first_name', 'users.last_name','invoices.*')->where('invoice_id', $id)->join('users', 'users.id', '=', 'invoice_history.report_by')->join('invoices', 'invoices.id', '=', 'invoice_history.invoice_id')->get();
        foreach($invoice->invoice_history as $history) {
            if($history->action == InvoiceHistory::INVOICE_CREATE) {
                $history->message = 'Invoice created successfully by ' . $history->first_name . ' ' . $history->last_name . '.';
            } elseif($history->action == InvoiceHistory::INVOICE_UPDATE) {
                $history->message = 'Invoice updated successfully by ' . $history->first_name . ' ' . $history->last_name . '.';
            } elseif($history->action == InvoiceHistory::INVOICE_DELETE) {
                $history->message = 'Invoice deleted successfully by ' . $history->first_name . ' ' . $history->last_name . '.';
            } elseif($history->action == InvoiceHistory::INVOICE_PAY) {
                $history->message = 'Invoice paid report genrated by ' . $history->first_name . ' ' . $history->last_name . '.';
            }
        }

        // Bank Details
        $invoice->bank_details = [
            'bank_name' => 'BARCLAYS BANK',
            'account_number' => '43230643',
            'sort_code' => '20-42-76'
        ];

        if($invoice) {
            return response()->json(['data' => $invoice, 'status' => 1, 'message' => 'Invoice Data!!'], 200);
        } else {
            return response()->json(['data' => [], 'status' => 1, 'message' => 'No Data Found!!'], 200);
        }
    }

    // Create invoice history.
    public static function InvoiceHistory($request) {
        $invoiceHistory = new InvoiceHistory();
        $invoiceHistory->invoice_id = $request->invoice_id;
        $invoiceHistory->amount = isset($request->amount) ? $request->amount : null;
        $invoiceHistory->balance = isset($request->balance) ? $request->balance : null;
        $invoiceHistory->pay = isset($request->pay) ? $request->pay : null;
        $invoiceHistory->action = $request->action;
        $invoiceHistory->recived_at = isset($request->recived_at) ? $request->recived_at : null;
        $invoiceHistory->report_by = Auth::user()->id;
        $invoiceHistory->save();
        return response()->json(['data' => [], 'status' => 1, 'message' => 'Invoice History created successfully.'],200);
    }

    // Genrate invoice pdf.
    public static function GenrateInvoicePdf($id) {
        // get invoice data.
        $invoice = Invoice::join('jobs', 'jobs.id', '=', 'invoices.job_id')
            ->join('estimates', 'estimates.id', '=', 'jobs.estimate_id')
            ->where('invoices.id', $id)
            ->first();

        // get customer details.
        $customer = Customer::withTrashed()->find($invoice->user_id);
        $invoice->customerDetails = $customer;

        // get services.
        $estimateServices = EstimateService::where('estimate_id', $invoice->estimate_id)->get();
        if(count($estimateServices) > 0) {
            foreach($estimateServices as $estimateService) {
                if(!empty($estimateService->service_id)) {
                    $service = Service::find($estimateService->service_id);
                    unset($estimateService['service_id']);
                    $estimateService['service_id'] = ['id' => $service->id, 'service' => $service->service];
                } else {
                    if(!empty($estimateService->temp_service) && $estimateService->temp_service != null) {
                        $estimateService['service_id'] = ['service' => $estimateService->temp_service];
                    }
                }
                $estimateData[] = $estimateService;
            }
        } else {
            $estimateData = [];
        }
        $invoice->services = $estimateData;
        if ($invoice) {
            if($invoice->type == Invoice::BILL) {
                $invoice->type = "BILL";
            } else {
                $invoice->type = "INVOICE";
            }
            // dd($invoice);
            // Load the HTML view and pass the data
            $pdf = PDF::loadView('Invoice.invoice', ['invoice' => $invoice]);
            $pdfContent = $pdf->output();

            // Generate a unique filename
            $fileName = 'invoice_' . $id . '.pdf';

            // Store the PDF temporarily
            $tempPath = sys_get_temp_dir() . '/' . $fileName;
            file_put_contents($tempPath, $pdfContent);

            return $tempPath;
        } else {
            // Handle the case where the invoice was not found
            return response()->json(['data' => [], 'status' => 0, 'message' => 'Invoice not found.'], 404);
        }
    }


    // Send invoice through mail.
    public static function SendInvoice($request) {
        foreach($request->invoice_ids as $invoice_id) {
            $pdfPath = InvoiceRepository::GenrateInvoicePdf($invoice_id);
            // get customer details by invoice id.
           $invoice = Invoice::select('customer.*')->join('jobs', 'jobs.id', '=', 'invoices.job_id')
               ->join('estimates', 'estimates.id', '=', 'jobs.estimate_id')
               ->join('customer', 'customer.id', '=', 'estimates.user_id')
               ->where('invoices.id',$invoice_id)
               ->first();
               //send mail.
                $email = EmailRepository::SendEmail($invoice->email, 'Invoice for your Car Repair', "/Invoice/Email/sendinvoice", ['invoice' => $invoice], [$pdfPath]);
        }
        if($email) {
            return response()->json(['data' => [], 'status' => 1, 'message' => 'Invoice sent successfully.'],200);
        } else {
            return response()->json(['data' => [], 'status' => 0, 'message' => 'Invoice not sent.'],200);
        }
    }

    // Delete invoice.
    public static function DeleteInvoice($request)
    {
        $ids = $request->ids ?? []; // expecting ['ids' => [1,2,3,...]]

        if (empty($ids)) {
            return response()->json(['data' => [], 'status' => 0, 'message' => 'No invoice IDs provided.'], 400);
        }

        // Find existing invoices
        $existing = Invoice::whereIn('id', $ids)->get();

        if ($existing->isEmpty()) {
            return response()->json(['data' => [], 'status' => 0, 'message' => 'No matching invoices found.'], 404);
        }

        // Delete only those that exist
        Invoice::whereIn('id', $existing->pluck('id'))->delete();

        return response()->json([
            'data' => ['deleted_ids' => $existing->pluck('id')],
            'status' => 1,
            'message' => count($existing) . ' invoice(s) deleted successfully.'
        ], 200);
    }

    // public static function InvoiceExportCsv($request)
    // {
    //     $fileName = 'invoice_export_' . now()->format('Ymd_His') . '.csv';

    //     $response = new StreamedResponse(function () use ($request) {

    //         $handle = fopen('php://output', 'w');

    //         // ================= CSV HEADER =================
    //         fputcsv($handle, [
    //             'Customer Name',
    //             'Date',
    //             'Reg No',
    //             'Make',
    //             'Model',
    //             'Service Name',
    //             'Description',
    //             'Invoice / Bill No',
    //             'Type',
    //             'Cost Price'
    //         ]);

    //         // ================= SAME FILTER LOGIC =================
    //         $invoices = Invoice::select(
    //                 'invoices.*',
    //                 'estimates.registration',
    //                 'estimates.make_id',
    //                 'estimates.model_id',
    //                 'estimates.id as estimate_id',
    //                 'estimates.user_id'
    //             )
    //             ->join('jobs', 'jobs.id', '=', 'invoices.job_id')
    //             ->join('estimates', 'estimates.id', '=', 'jobs.estimate_id')
    //             ->join('customer', 'customer.id', '=', 'estimates.user_id');

    //         if ($request->filled('search')) {
    //             $invoices->where(function ($q) use ($request) {
    //                 $q->where('invoices.invoice_number', 'like', "%{$request->search}%")
    //                 ->orWhere('customer.first_name', 'like', "%{$request->search}%")
    //                 ->orWhere('customer.last_name', 'like', "%{$request->search}%")
    //                 ->orWhere('customer.email', 'like', "%{$request->search}%");
    //             });
    //         }

    //         if ($request->filled('customer_id') && $request->customer_id !== 'all') {
    //             $invoices->where('estimates.user_id', $request->customer_id);
    //         }

    //         if ($request->filled('status') && $request->status !== 'all') {
    //             $invoices->where('invoices.pay_status', $request->status);
    //         }

    //         if ($request->filled('type') && $request->type !== 'all') {
    //             $invoices->where('invoices.type', $request->type);
    //         }

    //         if ($request->filled('start_date') && $request->filled('end_date')) {
    //             $invoices->whereBetween('invoices.created_at', [
    //                 $request->start_date,
    //                 $request->end_date
    //             ]);
    //         }

    //         $invoices = $invoices->orderBy('invoices.created_at', 'desc')->get();

    //         // ================= DATA ROWS =================
    //         foreach ($invoices as $invoice) {

    //             $customer = Customer::withTrashed()->find($invoice->user_id);
    //             $services = EstimateService::where('estimate_id', $invoice->estimate_id)->get();

    //             foreach ($services as $service) {

    //                 $serviceName = $service->service_id
    //                     ? optional(Service::find($service->service_id))->service
    //                     : $service->temp_service;

    //                 $make = optional(VehicleMake::find($invoice->make_id))->make;
    //                 $model = optional(VehicleModel::find($invoice->model_id))->model;

    //                 fputcsv($handle, [
    //                     trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')),
    //                     $invoice->created_at->format('Y-m-d'),
    //                     $invoice->registration,
    //                     $make,
    //                     $model,
    //                     $serviceName,
    //                     $service->description,
    //                     $invoice->invoice_number,
    //                     $invoice->type == Invoice::BILL ? 'BILL' : 'INV',
    //                     $service->cost_rate ?? 0
    //                 ]);
    //             }
    //         }

    //         fclose($handle);
    //     });

    //     $response->headers->set('Content-Type', 'text/csv');
    //     $response->headers->set('Content-Disposition', "attachment; filename={$fileName}");
    //     $response->headers->set('Pragma', 'no-cache');
    //     $response->headers->set('Cache-Control', 'must-revalidate');

    //     return $response;

    // }

    public static function InvoiceExportCsv($request)
    {
        $fileName = 'invoice_export_' . now()->format('Ymd_His') . '.csv';

        $response = new StreamedResponse(function () use ($request) {

            $handle = fopen('php://output', 'w');

            // ================= CSV HEADER =================
            fputcsv($handle, [
                'Date',
                'Reg No',
                'Make',
                'Model',
                'Service Name',
                'Description',
                'Invoice / Bill No',
                'Type',
                'Rate',
                'Cost Price',
                'Total Rate',
                'Total Cost Price',
                'Due Date',
                'Bill',
                'VAT',
                'Total',
                'Due Balance'
            ]);

            // ================= FILTER LOGIC =================
            $invoices = Invoice::select(
                    'invoices.*',
                    'estimates.registration',
                    'estimates.make_id',
                    'estimates.model_id',
                    'estimates.id as estimate_id'
                )
                ->join('jobs', 'jobs.id', '=', 'invoices.job_id')
                ->join('estimates', 'estimates.id', '=', 'jobs.estimate_id')
                ->join('customer', 'customer.id', '=', 'estimates.user_id');

            if ($request->filled('search')) {
                $invoices->where(function ($q) use ($request) {
                    $q->where('invoices.invoice_number', 'like', "%{$request->search}%")
                    ->orWhere('customer.first_name', 'like', "%{$request->search}%")
                    ->orWhere('customer.last_name', 'like', "%{$request->search}%")
                    ->orWhere('customer.email', 'like', "%{$request->search}%");
                });
            }

            if ($request->filled('customer_id') && $request->customer_id !== 'all') {
                $invoices->where('estimates.user_id', $request->customer_id);
            }

            if ($request->filled('status') && $request->status !== 'all') {
                $invoices->where('invoices.pay_status', $request->status);
            }

            if ($request->filled('type') && $request->type !== 'all') {
                $invoices->where('invoices.type', $request->type);
            }

            if ($request->filled('start_date') && $request->filled('end_date')) {
                $invoices->whereBetween('invoices.created_at', [
                    $request->start_date,
                    $request->end_date
                ]);
            }

            $invoices = $invoices->orderBy('invoices.created_at', 'desc')->get();

            // ================= DATA ROWS =================
            foreach ($invoices as $invoice) {

                $services = EstimateService::where('estimate_id', $invoice->estimate_id)->get();

                if ($services->isEmpty()) {
                    continue;
                }

                $make  = optional(VehicleMake::find($invoice->make_id))->make;
                $model = optional(VehicleModel::find($invoice->model_id))->model;

                // Totals calculated once per invoice
                $totalRate = $services->sum('rate');
                $totalCost = $services->sum('cost_rate');

                $firstRow = true;

                foreach ($services as $service) {

                    $serviceName = $service->service_id
                        ? optional(Service::find($service->service_id))->service
                        : $service->temp_service;

                    fputcsv($handle, [
                        $invoice->created_at->format('Y-m-d'),
                        $invoice->registration,
                        $make,
                        $model,
                        $serviceName,
                        $service->description,
                        $invoice->invoice_number,
                        $invoice->type == Invoice::BILL ? 'BILL' : 'INV',
                        $service->rate ?? 0,
                        $service->cost_rate ?? 0,

                        // Totals only once per invoice
                        $firstRow ? $totalRate : '',
                        $firstRow ? $totalCost : '',
                        $firstRow ? optional($invoice->due_date)->format('Y-m-d') : '',
                        $firstRow ? ($invoice->bill_amount ?? 0) : '',
                        $firstRow ? ($invoice->vat_amount ?? 0) : '',
                        $firstRow ? ($invoice->grand_total ?? 0) : '',
                        $firstRow ? ($invoice->due_balance ?? 0) : '',
                    ]);

                    $firstRow = false;
                }
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', "attachment; filename={$fileName}");
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Cache-Control', 'must-revalidate');

        return $response;
    }

}
