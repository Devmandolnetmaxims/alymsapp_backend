<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #222;
        }

        /* HEADER */
        .header {
            width: 100%;
            margin-bottom: 10px;
        }

        .header-left {
            float: left;
            width: 60%;
        }

        .header-right {
            float: right;
            width: 40%;
            text-align: right;
            font-size: 10px;
        }

        .company {
            font-size: 18px;
            font-weight: bold;
        }

        .sub-title {
            font-size: 11px;
            color: #666;
        }

        .clear {
            clear: both;
        }

        /* TABLE */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }

        th {
            background: #f2f2f2;
            font-weight: bold;
            font-size: 10px;
            border-bottom: 2px solid #000;
            padding: 6px;
            text-align: left;
        }

        td {
            border-bottom: 1px solid #ddd;
            padding: 6px;
            vertical-align: top;
        }

        .small {
            font-size: 9px;
            color: #666;
        }

        .paid {
            color: green;
            font-weight: bold;
        }

        .due {
            color: red;
            font-weight: bold;
        }

        /* SUMMARY BOX */
        .summary-box {
            width: 35%;
            float: right;
            margin-top: 20px;
            border: 1px solid #ccc;
            padding: 8px;
            font-size: 11px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .summary-total {
            color: red;
            font-weight: bold;
            border-top: 1px solid #ccc;
            padding-top: 5px;
        }

        /* FOOTER */
        .footer-line {
            border-top: 2px solid #000;
            margin: 25px 0 8px 0;
        }

        .footer-text {
            text-align: center;
            font-size: 9px;
            color: #444;
            margin-bottom: 5px;
        }

        .footer-note {
            text-align: center;
            font-size: 9px;
            color: #666;
        }
    </style>
</head>

<body>

<!-- HEADER -->
<div class="header">
    <div class="header-left">
        <div class="company">ALMYS AUTOS</div>
        <div class="sub-title">CUSTOMER WORK HISTORY</div>
    </div>

    <div class="header-right">
        <div><strong>Customer:</strong> {{ $customer->first_name }} {{ $customer->last_name }}</div>
        <div><strong>Email:</strong> {{ $customer->email }}</div>
        <div><strong>Generated:</strong> {{ $generatedAt }}</div>
    </div>
</div>

<div class="clear"></div>

<!-- WORK HISTORY TABLE -->
<table>
    <thead>
        <tr>
            <th>ISSUED</th>
            <th>BILL</th>
            <th>VEHICLE</th>
            <th>SERVICE & DESCRIPTION</th>
            <th>NET BILL</th>
            <th>VAT</th>
            <th>TOTAL</th>
            <th>BALANCE</th>
        </tr>
    </thead>
    <tbody>
        @foreach($workHistory as $row)
        <tr>
            <td>
                {{ $row['date'] }}<br>
                <span class="small">({{ $row['due_date'] }})</span>
            </td>
            <td>
                {{ $row['invoice_no'] }}<br>
                <span class="small">Reg: {{ $row['registration_no'] }}</span>
            </td>
            <td>
                • <strong>{{ $row['service'] }}</strong><br>
                <span class="small">{{ $row['description'] }}</span>
            </td>
            <td>{{ $row['bill'] }}</td>
            <td>{{ $row['vat'] }}</td>
            <td><strong>{{ $row['total'] }}</strong></td>
            <td>
                @if($row['due_balance'] == '0.00')
                    <span class="paid">PAID</span>
                @else
                    <span class="due">{{ $row['due_balance'] }}</span>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<!-- SUMMARY -->
<div class="summary-box">
    <div class="summary-row">
        <span>Total Net:</span>
        <span>£{{ $totalBill }}</span>
    </div>
    <div class="summary-row">
        <span>Total VAT:</span>
        <span>£{{ $totalVat }}</span>
    </div>
    <div class="summary-row summary-total">
        <span>TOTAL BALANCE DUE:</span>
        <span>£{{ $totalDue }}</span>
    </div>
</div>

<div class="clear"></div>

<!-- FOOTER -->
<div class="footer-line"></div>

<div class="footer-text">
    <strong>BANK DETAILS:</strong>
    ALMYS AUTOS Business Account |
    Sort: 12-34-56 |
    Acc: 12345678 |
    IBAN: GB29 NWBK 123456789012 34
</div>

<div class="footer-note">
    All work completed as per agreed specifications. Payment terms: Net 30 days.
</div>

</body>
</html>
