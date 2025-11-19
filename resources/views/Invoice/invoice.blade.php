<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Billing PDF</title>
</head>
<body style="font-family: Arial, sans-serif; padding: 24px; background-color: #FFFFFF;">
    <table style="width: 100%; border-collapse: collapse;">
      <tr>
        <td style="width: 50%;">
          <img src="https://almysauto.webwatt.com/assets/errors/logo/logo.jpg" style="height: 65px; width: 110px;" alt="Brand Logo">
          <p style="font-size: 12px; font-weight: 600; line-height: 1.6;">https://www.almysautos.co.uk</p>
        </td>
        <td style="width: 50%; text-align: right;">
          <div>
            <p style="font-size: 14px; font-weight: 600; line-height: 1.235;">{{$invoice->type}}-{{$invoice->invoice_number}}</p>
          </div>
        </td>
      </tr>
      <tr>
        <td style="width: 30%;">
          <p style="font-size: 15px; font-weight: 400; line-height: 1.43;">Rosedale, The Common</p>
          <p style="font-size: 15px; font-weight: 400; line-height: 1.43;">West Drayton, Hampshire,</p>
          <p style="font-size: 15px; font-weight: 400; line-height: 1.43;">UB7 7HQ United Kingdom</p>
        </td>
        <td style="width: 30%;">
          <p style="font-size: 15px; font-weight: 400; line-height: 1.43;">VAT Registration Number:</p>
          <p style="font-size: 15px; font-weight: 400; line-height: 1.43;">GB 439528562</p>
        </td>
        <td style="width: 30%;">
          <p style="font-size: 15px; font-weight: 400; line-height: 1.43;">accounts@almysautos.co.uk</p>
          <p style="font-size: 15px; font-weight: 400; line-height: 1.43;">1895431500</p>
          <p style="font-size: 15px; font-weight: 400; line-height: 1.43;">7742062378</p>
        </td>
      </tr>
      <tr>
        <td style="width: 40%;">
          <p style="font-size: 15px; font-weight: 500; line-height: 1.57; margin-bottom: 4px;">Invoice Number</p>
          <p style="font-size: 15px; font-weight: 400; line-height: 1.43;">{{$invoice->type}}-{{$invoice->invoice_number}}<</p>
        </td>
        <td style="width: 30%;">
          <p style="font-size: 15px; font-weight: 500; line-height: 1.57; margin-bottom: 4px;">Due Date</p>
          <p style="font-size: 15px; font-weight: 400; line-height: 1.43;">{{$invoice->due_date}}</p>
        </td>
        <td style="width: 30%;">
          <p style="font-size: 15px; font-weight: 500; line-height: 1.57; margin-bottom: 4px;">Date of issue</p>
          <p style="font-size: 15px; font-weight: 400; line-height: 1.43;">{{$invoice->created_at}}</p>
        </td>
      </tr>
      <tr>
        <td style="width: 40%;">
          <p style="font-size: 15px; font-weight: 500; line-height: 1.57; margin-bottom: 4px;">Billed to</p>
          <p style="font-size: 15px; font-weight: 400; line-height: 1.43;">{{$invoice->customerDetails->first_name}}</p>
          <p style="font-size: 15px; font-weight: 400; line-height: 1.43;">{{$invoice->customerDetails->company_name}}</p>
          <p style="font-size: 15px; font-weight: 400; line-height: 1.43;">{{$invoice->customerDetails->street}} {{$invoice->customerDetails->area}} {{$invoice->customerDetails->town}} {{$invoice->customerDetails->post_code}}</p>
        </td>
        <td style="width: 30%;">
          <p style="font-size: 15px; font-weight: 500; line-height: 1.57; margin-bottom: 4px;">Registration No.</p>
          <p style="font-size: 15px; font-weight: 400; line-height: 1.43;">{{$invoice->registration}}</p>
        </td>
        <td style="width: 30%;">
          <p style="font-size: 15px; font-weight: 500; line-height: 1.57; margin-bottom: 4px;">Reference No.</p>
          <p style="font-size: 15px; font-weight: 400; line-height: 1.43;">{{$invoice->ref_no}}</p>
        </td>
      </tr>
    </table>
    
    <table style="width: 100%; border-collapse: collapse; margin-top: 32px;">
      <thead>
        <tr>
          <th style="border: 1px solid #EEEEEE; padding: 6px; font-size: 15px; font-weight: 600;">S.No.</th>
          <th style="border: 1px solid #EEEEEE; padding: 6px; font-size: 15px; font-weight: 600;">Service Name</th>
          <th style="border: 1px solid #EEEEEE; padding: 6px; font-size: 15px; font-weight: 600;">Description</th>
          <th style="border: 1px solid #EEEEEE; padding: 6px; font-size: 15px; font-weight: 600;">Qty</th>
          <th style="border: 1px solid #EEEEEE; padding: 6px; font-size: 15px; font-weight: 600;">Rate</th>
          <th style="border: 1px solid #EEEEEE; padding: 6px; font-size: 15px; font-weight: 600;">Discount</th>
          <th style="border: 1px solid #EEEEEE; padding: 6px; font-size: 15px; font-weight: 600;">Net</th>
        </tr>
      </thead>
      <tbody>
        @foreach($invoice->services as $service)
        <tr>
          <td style="border: 1px solid #EEEEEE; padding: 6px; font-size: 15px; font-weight: 400;">1</td>
          <td style="border: 1px solid #EEEEEE; padding: 6px; font-size: 15px; font-weight: 400;">{{$service->service_id['service']}}</td>
          <td style="border: 1px solid #EEEEEE; padding: 6px; font-size: 15px; font-weight: 400;">{{$service->description}}</td>
          <td style="border: 1px solid #EEEEEE; padding: 6px; font-size: 15px; font-weight: 400;">{{$service->quantity}}</td>
          <td style="border: 1px solid #EEEEEE; padding: 6px; font-size: 15px; font-weight: 400;">{{$service->rate}}</td>
          <td style="border: 1px solid #EEEEEE; padding: 6px; font-size: 15px; font-weight: 400;">{{$service->total}}</td>
        </tr>
      </tbody>
      @endforeach
    </table>
  
    <table style="width: 100%; border-collapse: collapse; margin-top: 32px;">
      <tr>
        <td style="width: 76%;"></td>
        <td style="width: 12%;">
          <p style="font-size: 15px; font-weight: 400; line-height: 1.43;">Discount:</p>
        </td>
        <td style="width: 12%;">
          <p style="font-size: 15px; font-weight: 400; line-height: 1.43;">{{$invoice->net_discount}}</p>
        </td>
      </tr>
      <tr>
        <td style="width: 76%;"></td>
        <td style="width: 12%;">
          <p style="font-size: 15px; font-weight: 400; line-height: 1.43;">Total Net:</p>
        </td>
        <td style="width: 12%;">
          <p style="font-size: 15px; font-weight: 400; line-height: 1.43;">{{$invoice->net_total}}</p>
        </td>
      </tr>
      <tr>
        <td style="width: 76%;"></td>
        <td style="width: 12%;">
          <p style="font-size: 15px; font-weight: 400; line-height: 1.43;">VAT(20%):</p>
        </td>
        <td style="width: 12%;">
          <p style="font-size: 15px; font-weight: 400; line-height: 1.43;">{{$invoice->net_total}}</p>
        </td>
      </tr>
      <tr>
        <td style="width: 76%;"></td>
        <td style="width: 12%;">
          <p style="font-size: 15px; font-weight: 400; line-height: 1.43;">Total:</p>
        </td>
        <td style="width: 12%;">
          <p style="font-size: 15px; font-weight: 400; line-height: 1.43;">{{$invoice->grand_total}}</p>
        </td>
      </tr>
    </table>
  
    <div style="margin-top: 32px;">
      <p style="font-size: 12px; font-weight: 600; line-height: 1.6; margin-bottom: 4px;">Notes</p>
      <p style="font-size: 15px; font-weight: 400; line-height: 1.43;">
        Please make sure you have the right bank registration number as I had issues before and make sure you guys cover transfer expenses.
      </p>
    </div>
  </body>
  
</html>
