<?php

namespace App\Exports;

use App\Models\Invoice;
use App\Models\EstimateService;
use App\Models\Service;
use App\Models\VehicleMake;
use App\Models\VehicleModel;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;


class InvoiceExport implements FromCollection, WithHeadings, WithEvents, WithStyles
{
    protected $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    // public function headings(): array
    // {
    //     return [
    //         'Date',
    //         'Reg No',
    //         'Make',
    //         'Model',
    //         'Service Name',
    //         'Description',
    //         'Invoice / Bill No',
    //         'Type',
    //         'Rate',
    //         'Cost Price',
    //         'Total Rate',
    //         'Total Cost Price',
    //         'Due Date',
    //         'Bill',
    //         'VAT',
    //         'Total',
    //         'Due Balance'
    //     ];
    // }

    public function headings(): array
    {
        return [
            'Sr. No',
            'Date',
            'Customer Name',
            'Customer Type',
            'Reg No',
            'Make',
            'Model',
            'Service Name',
            'Description',
            'Invoice / Bill No',
            'Type',
            'Qty / hrs',
            'Rate',
            'Cost Price',
            'Total Rate',
            'Total Cost Price',
            'Due Date',
            'Bill',
            'VAT',
            'Total',
            'Due Balance'
        ];
    }

    // public function collection()
    // {
    //     $rows = new Collection();

    //     $invoices = Invoice::with(['job.estimate'])
    //         ->orderBy('created_at', 'desc')
    //         ->get();

    //     foreach ($invoices as $invoice) {
    //         // SAFETY CHECK
    //         if (!$invoice->job || !$invoice->job->estimate) {
    //             continue; // skip invalid invoice
    //         }

    //         $estimate = $invoice->job->estimate;

    //         $services = EstimateService::where('estimate_id', $estimate->id)->get();
    //         if ($services->isEmpty()) continue;

    //         $make  = optional(VehicleMake::find($invoice->job->estimate->make_id))->make;
    //         $model = optional(VehicleModel::find($invoice->job->estimate->model_id))->model;

    //         $totalRate = $services->sum('rate');
    //         $totalCost = $services->sum('cost_rate');

    //         $firstRow = true;

    //         foreach ($services as $service) {

    //             $serviceName = $service->service_id
    //                 ? optional(Service::find($service->service_id))->service
    //                 : $service->temp_service;

    //             $estimate = $invoice->job->estimate;

    //             $isBill    = (string)$invoice->type === Invoice::BILL;
    //             $isInvoice = (string)$invoice->type === Invoice::INVOICE;

    //             $netTotal   = $estimate->net_total ?? 0;
    //             $vat        = $estimate->net_vat ?? 0;
    //             $grandTotal = $estimate->grand_total ?? 0;
    //             $paid       = $estimate->amount ?? 0;
    //             $dueBalance = $grandTotal - $paid;

    //             $rows->push([
    //                 $invoice->created_at->format('Y-m-d'),
    //                 $estimate->registration,
    //                 $make,
    //                 $model,
    //                 $serviceName,
    //                 $service->description,
    //                 $invoice->invoice_number,
    //                 $isBill ? 'BILL' : 'INV',
    //                 $service->rate ?? 0,
    //                 $service->cost_rate ?? 0,
    //                 $firstRow ? $totalRate : '',
    //                 $firstRow ? $totalCost : '',

    //                 // Due Date (Invoice-level)
    //                 $firstRow && $invoice->due_date
    //                     ? \Carbon\Carbon::parse($invoice->due_date)->format('Y-m-d')
    //                     : '',

    //                 // Bill amount (Bills only)
    //                 $firstRow && $isBill ? $netTotal : '',

    //                 // VAT (Invoices only)
    //                 $firstRow && $isInvoice ? $vat : '',

    //                 // Total
    //                 $firstRow ? ($isBill ? $netTotal : $grandTotal) : '',

    //                 // Due Balance
    //                 $firstRow ? $dueBalance : '',
    //             ]);

    //             $firstRow = false;
    //         }
    //     }

    //     return $rows;
    // }

    public function collection()
    {
        $rows = new Collection();
        $srNo = 1;

        $invoices = Invoice::with(['job.estimate'])
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($invoices as $invoice) {

            if (!$invoice->job || !$invoice->job->estimate) {
                continue;
            }

            $estimate = $invoice->job->estimate;

            // ✅ FIX: customer via user_id ONLY
            $customer = \App\Models\Customer::find($estimate->user_id);

            $services = EstimateService::where('estimate_id', $estimate->id)->get();
            if ($services->isEmpty()) continue;

            $make  = optional(VehicleMake::find($estimate->make_id))->make;
            $model = optional(VehicleModel::find($estimate->model_id))->model;

            $totalRate = $services->sum(fn($s) => ($s->rate ?? 0) * ($s->quantity ?? 1));
            $totalCost = $services->sum(fn($s) => ($s->cost_rate ?? 0) * ($s->quantity ?? 1));

            $isBill    = (string)$invoice->type === Invoice::BILL;
            $isInvoice = (string)$invoice->type === Invoice::INVOICE;

            $netTotal   = $estimate->net_total ?? 0;
            $vat        = $estimate->net_vat ?? 0;
            $grandTotal = $estimate->grand_total ?? 0;
            $paid       = $estimate->amount ?? 0;
            $dueBalance = $grandTotal - $paid;

            $firstRow = true;

            foreach ($services as $service) {

                $serviceName = $service->service_id
                    ? optional(Service::find($service->service_id))->service
                    : $service->temp_service;

                $rows->push([
                    $srNo,
                    $invoice->created_at->format('Y-m-d'),
                    trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')),
                    $customer->company_type == 1
                        ? 'Private Insurance'
                        : ($customer->company_type == 2 ? 'Trade Contact' : ''),
                    $estimate->registration,
                    $make,
                    $model,
                    $serviceName,
                    $service->description,
                    $invoice->invoice_number,
                    $isBill ? 'BILL' : 'INV',
                    $service->quantity ?? 1,
                    $service->rate ?? 0,
                    $service->cost_rate ?? 0,
                    $firstRow ? $totalRate : '',
                    $firstRow ? $totalCost : '',
                    $firstRow && $invoice->due_date
                        ? \Carbon\Carbon::parse($invoice->due_date)->format('Y-m-d')
                        : '',
                    $firstRow ? $netTotal : '',
                    $firstRow && $isInvoice ? $vat : '',
                    $firstRow ? ($isBill ? $netTotal : $grandTotal) : '',
                    $firstRow ? $dueBalance : '',
                ]);

                $firstRow = false;
            }

            $srNo++;
        }

        return $rows;
    }

    /**
     * Merge invoice-level cells
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();
                // Fix row height for clip effect
                $sheet->getDefaultRowDimension()->setRowHeight(20);

                // Ensure alignment for merged cells too
                $sheet->getStyle('A:Z')->getAlignment()
                    ->setVertical(Alignment::VERTICAL_TOP)
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                    ->setWrapText(true);
                $rowStart = 2;
                $currentInvoice = null;

                for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {

                    $invoiceNo = $sheet->getCell("G{$row}")->getValue();

                    if ($currentInvoice !== $invoiceNo) {
                        $rowStart = $row;
                        $currentInvoice = $invoiceNo;
                    }

                    if (
                        $row == $sheet->getHighestRow() ||
                        $sheet->getCell("G" . ($row + 1))->getValue() !== $invoiceNo
                    ) {
                        if ($rowStart !== $row) {
                            // foreach (['A','B','C','D','G','H','K','L','M','N','O','P','Q'] as $col) {
                            //     $sheet->mergeCells("{$col}{$rowStart}:{$col}{$row}");
                            // }
                            foreach ([
                                'A','B','C','D','E','F','G','J','K',
                                'O','P','Q','R','S','T','U'
                            ] as $col) {
                                $sheet->mergeCells("{$col}{$rowStart}:{$col}{$row}");
                            }

                        }
                    }
                }
            }
        ];
    }

    /**
     * Styling
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Header row
            1 => [
                'font' => [
                    'bold' => true,
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical'   => Alignment::VERTICAL_TOP,
                    'wrapText'   => true,
                ],
            ],

            // All cells
            'A:Z' => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical'   => Alignment::VERTICAL_TOP,
                    'wrapText'   => true,   // 🔹 Wrap text
                    'shrinkToFit'=> false,  // 🔹 Prevent shrinking
                ],
            ],
        ];
    }
}
