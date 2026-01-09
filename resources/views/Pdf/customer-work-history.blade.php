<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }
        h1 {
            text-align: center;
            margin-bottom: 5px;
        }
        h3 {
            margin-top: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
        }
        th {
            background-color: #1f6fb2;
            color: #fff;
        }
        .no-border td {
            border: none;
        }
        .footer {
            text-align: center;
            font-size: 10px;
            margin-top: 30px;
        }
    </style>
</head>
<body>

<h1>ALMYS AUTOS</h1>
<p style="text-align:center;">Customer Work History Report</p>

<h3>CUSTOMER DETAILS:</h3>
<table class="no-border">
    <tr>
        <td><strong>Customer Name:</strong></td>
        <td>{{ $customer->first_name }} {{ $customer->last_name }}</td>
    </tr>
    <tr>
        <td><strong>Customer Email:</strong></td>
        <td>{{ $customer->email }}</td>
    </tr>
    <tr>
        <td><strong>Report Generated:</strong></td>
        <td>{{ now()->format('d/m/Y') }}</td>
    </tr>
</table>

<h3>WORK HISTORY:</h3>
<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>INV NUMBER</th>
            <th>Description</th>
            <th>Rate</th>
            <th>Cost Price</th>
            <th>Amount</th>
            <th>VAT</th>
        </tr>
    </thead>
    <tbody>
        @foreach($workHistory as $row)
            <tr>
                <td>{{ $row['date'] }}</td>
                <td>{{ $row['invoice_no'] }}</td>
                <td>{{ $row['description'] }}</td>
                <td>£{{ $row['rate'] }}</td>
                <td>£{{ $row['cost_price'] }}</td>
                <td>£{{ $row['amount'] }}</td>
                <td>£{{ $row['vat'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<h3>NOTES:</h3>
<p>
    All work completed as per agreed specifications.<br>
    Payment terms: Net 30 days from invoice date.<br>
    For any queries, please contact us using the details below.
</p>

<h3>BANK DETAILS:</h3>
<p>
    <strong>Bank Name:</strong> ALMYS AUTOS Business Account<br>
    <strong>Account Number:</strong> 12345678<br>
    <strong>Sort Code:</strong> 12-34-56<br>
    <strong>IBAN:</strong> GB29 NWBK 1234 5678 9012 34
</p>

<div class="footer">
    Generated on {{ $generatedAt }}
</div>

</body>
</html>
