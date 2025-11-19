<?php

namespace App\Listeners\Invoice;

use App\Http\Repository\Invoice\InvoiceRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\MakeInvoiceEvent;

class MakeInvoiceListner
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle($event): void
    {
        // dd($event->event);
        InvoiceRepository::CreateInvoice($event->event);
    }
}
