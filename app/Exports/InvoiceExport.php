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

class InvoiceExport implements FromCollection, WithHeadings, WithEvents, WithStyles
{
    protected $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function headings(): array
    {
        return [
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
        ];
    }

    public function collection()
    {
        $rows = new Collection();

        $invoices = Invoice::with(['job.estimate'])
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($invoices as $invoice) {

            $services = EstimateService::where('estimate_id', $invoice->job->estimate->id)->get();
            if ($services->isEmpty()) continue;

            $make  = optional(VehicleMake::find($invoice->job->estimate->make_id))->make;
            $model = optional(VehicleModel::find($invoice->job->estimate->model_id))->model;

            $totalRate = $services->sum('rate');
            $totalCost = $services->sum('cost_rate');

            $firstRow = true;

            foreach ($services as $service) {

                $serviceName = $service->service_id
                    ? optional(Service::find($service->service_id))->service
                    : $service->temp_service;

                $rows->push([
                    $invoice->created_at->format('Y-m-d'),
                    $invoice->job->estimate->registration,
                    $make,
                    $model,
                    $serviceName,
                    $service->description,
                    $invoice->invoice_number,
                    $invoice->type == Invoice::BILL ? 'BILL' : 'INV',
                    $service->rate ?? 0,
                    $service->cost_rate ?? 0,
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
                            foreach (['A','B','C','D','G','H','K','L','M','N','O','P','Q'] as $col) {
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
            1 => [
                'font' => [
                    'bold' => true,
                ],
            ],
        ];
    }
}
