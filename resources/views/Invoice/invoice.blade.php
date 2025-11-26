<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Invoice PDF</title>

  <style>


    body {
    font-family: Arial, sans-serif !important;
    padding: 40px;
    background: #fff;
    font-size: 14px;
    color: #000;
  }

    .top-header {
      width: 100%;
      margin-bottom: 10px;
    }

    .invoice-box td {
    border: 1px solid #ccc;
    padding: 12px;
    vertical-align: top;
    border-radius: 6px;
}


    .left-logo img {
      height: 65px;
      width: 120px;
    }

    .invoice-title {
      font-size: 28px;
      font-weight: 700;
      color: red;
      text-align: right;
      margin: 0;
    }

    table { width: 100%; border-collapse: collapse; }
    .mt-20 { margin-top: 20px; }
    .mt-40 { margin-top: 40px; }
    
    td, th {
      padding: 8px;
      vertical-align: top;
    }

    .details-title { font-size: 16px; font-weight: 600; }
    .details-value { font-size: 14px; }

    /* Table for services */
    .service-table th {
      background: #fafafa;
      border: 1px solid #ddd;
      font-weight: bold;
      text-align: left;
    }

    .service-table td {
      border: 1px solid #ddd;
    }

    /* Totals */
    .totals td {
      padding: 6px 4px;
      font-size: 14px;
    }

    .totals .label {
      text-align: right;
      font-weight: 600;
    }

    /* Notes */
    .notes {
      margin-top: 20px;
      font-size: 11px;
      font-weight: 600;
    }
  </style>
</head>

<body>

  <!-- TOP HEADER -->
  <table class="top-header">
    <tr>
      <td class="left-logo">
        <img src="{{env('WEB_URL')}}/assets/errors/logo/logo.jpg">

        <p style="font-size: 12px; font-weight: 600; margin-top: 4px;">
          www.almysautos.co.uk
        </p>
      </td>

      <td style="text-align: right;">
        <p class="invoice-title">OVERDUE</p>
        <p style="font-size: 14px; font-weight: 600;">
          {{$invoice->type}}-{{$invoice->invoice_number}}
        </p>
      </td>
    </tr>
  </table>

  <!-- ADDRESS BLOCK -->
  <table>
    <tr>
      <td>
        <p class="details-value">Rosedale, The Common</p>
        <p class="details-value">West Drayton, Hampshire</p>
        <p class="details-value">UB7 7HQ United Kingdom</p>
      </td>

      <td>
        <p class="details-title">VAT Registration Number:</p>
        <p class="details-value">GB 439528562</p>
      </td>

      <td style="text-align: right;">
        <p style="text-align: right;" class="details-value">accounts@almysautos.co.uk</p>
        <p style="text-align: right;" class="details-value">1895431500</p>
        <p style="text-align: right;" class="details-value">7742062378</p>
    </td>
    </tr>
  </table>

  <!-- INFO BLOCK -->
  <table class="mt-20">
    <tr>
      <td>
        <p class="details-title">Invoice Number</p>
        <p class="details-value">{{$invoice->type}}-{{$invoice->invoice_number}}</p>
      </td>

      <td>
        <p class="details-title">Due date</p>
        <p class="details-value">{{$invoice->due_date}}</p>
      </td>

      <td style="text-align: right;">
        <p class="details-title">Date of issue</p>
        <p class="details-value">{{$invoice->created_at}}</p>
      </td>
    </tr>

    <tr class="mt-20">
      <td>
        <p class="details-title">Billed to</p>
        <p class="details-value">{{$invoice->customerDetails->first_name}}</p>
        <p class="details-value">{{$invoice->customerDetails->company_name}}</p>

        <p class="details-value">
          {{$invoice->customerDetails->street}}
          {{$invoice->customerDetails->area}}
          {{$invoice->customerDetails->town}}
          {{$invoice->customerDetails->post_code}}
        </p>
      </td>

      <td>
        <p style="font-family: Arial, sans-serif !important;" class="details-title">Registration No.</p>
        <p class="details-value">{{$invoice->registration}}</p>
      </td>

       <td style="text-align: right;">
        <p class="details-title">Reference No.</p>
        <p class="details-value">{{$invoice->ref_no}}</p>
      </td>
    </tr>
  </table>

 <table class="full-table" style="width:100%; border-collapse: collapse; margin-top: 30px;">

  <!-- SERVICES HEADER -->
  <tr>
    <th style="border:1px solid #E5E5E5; padding:6px; background: #f5f5f5;">S.No</th>
    <th style="border:1px solid #E5E5E5; padding:6px; background: #f5f5f5;">Service Name</th>
    <th style="border:1px solid #E5E5E5; padding:6px; background: #f5f5f5;">Description</th>
    <th style="border:1px solid #E5E5E5; padding:6px; background: #f5f5f5;">Qty / Hrs.</th>
    <th style="border:1px solid #E5E5E5; padding:6px; background: #f5f5f5;">Rate</th>
    <th style="border:1px solid #E5E5E5; padding:6px; background: #f5f5f5;">Net</th>
  </tr>

  <!-- SERVICES ROWS -->
  @foreach($invoice->services as $index => $service)
  <tr>
    <td style="border:1px solid #E5E5E5; padding:6px;">{{ $index + 1 }}</td>
    <td style="border:1px solid #E5E5E5; padding:6px; text-align: center;">{{ $service->service_id['service'] }}</td>
    <td style="border:1px solid #E5E5E5; padding:6px; text-align: center;">{{ $service->description }}</td>
    <td style="border:1px solid #E5E5E5; padding:6px; text-align: center;">{{ $service->quantity }}</td>
    <td style="border:1px solid #E5E5E5; padding:6px; text-align: center;">£{{ number_format($service->rate, 2) }}</td>
    <td style="border:1px solid #E5E5E5; padding:6px; text-align: center;">£{{ number_format($service->total, 2) }}</td>
  </tr>
  @endforeach

  <!-- PAYMENT DETAILS + TOTALS -->
  <tr>
    <!-- LEFT PAYMENT DETAILS (spans 4 columns) -->
    <td colspan="4" style="border:1px solid #E5E5E5; padding:10px; vertical-align: top;">
      <p style="font-size:16px; font-weight:600; margin-bottom:8px;">Payment Details</p>

      <p><b>Bank Name:</b> BARCLAYS BANK</p>
      <p><b>Account Number:</b> 43230643</p>
      <p><b>Sort Code:</b> 20-42-76</p>
    </td>

    <!-- RIGHT TOTALS (spans 2 columns) -->
    <td colspan="2" style="border:1px solid #E5E5E5; padding:0px;">
      <table style="width:100%; border-collapse: collapse;">
        <tr>
          <td style="text-align:left; padding:9px; border: 1px solid #ccc;"><b>Discount:</b></td>
          <td style="padding:9px; border: 1px solid #ccc;">£{{ number_format($invoice->net_discount, 2) }}</td>
        </tr>

        <tr>
          <td style="text-align:left; padding:9px; border: 1px solid #ccc;"><b>Total Net:</b></td>
          <td style="padding:9px; border: 1px solid #ccc;">£{{ number_format($invoice->net_total, 2) }}</td>
        </tr>

        <tr>
          <td style="text-align:left; padding:9px; border: 1px solid #ccc;"><b>VAT (20%):</b></td>
          <td style="padding:9px; border: 1px solid #ccc;">£{{ number_format(($invoice->net_total * 0.20), 2) }}</td>
        </tr>

        <tr>
          <td style="text-align:left; padding:9px; border: 1px solid #ccc;"><b>Total:</b></td>
          <td style="padding:9px; border: 1px solid #ccc;">£{{ number_format($invoice->grand_total, 2) }}</td>
        </tr>
      </table>
    </td>
  </tr>

</table>


  <!-- NOTES -->
  <div class="notes mt-20">
    <p style="font-size: 16px;">Notes</p>
    <p style="font-size: 14px; font-weight: 400;">
      Please make sure you have the right bank registration number as I had issues before
      and make sure you guys cover transfer expenses.
    </p>
  </div>

</body>
</html>
