<?php

namespace App\Http\Controllers\Invoice;

use App\Http\Repository\Invoice\InvoiceRepository;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    // create invoice.
    public function createInvoice($id) {
        return InvoiceRepository::CreateInvoice($id);
    }

    // get all invoice.
    public function allInvoice(Request $request, $id = null) {
        return InvoiceRepository::AllInvoice($request, $id);
    }

    // Invoice update.
    public function updateInvoice(Request $request, $id = null) {
        return InvoiceRepository::InvoiceUpdate($request, $id);
    }

    // Genrate invoice pdf.
    public function sendInvoice(Request $request) {
        return InvoiceRepository::SendInvoice($request);
    }

    // Delete invoice.
    public function deleteInvoice(Request $request) {
        return InvoiceRepository::DeleteInvoice($request);
    }

    // Export invoice csv.
    public function exportCsv(Request $request) {
        return InvoiceRepository::InvoiceExportCsv($request);
    }
}
